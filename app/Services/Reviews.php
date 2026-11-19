<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException};
use App\Support\{Auth, Customer};

/**
 * Product reviews. Only customers with a completed order containing the product can review it
 * (one review per product). New reviews wait for approval unless "auto approve" is on in settings.
 * products.rating_avg / rating_count cache the approved reviews for cards and sorting.
 */
final class Reviews
{
    public const PER_PAGE = 5;
    public const ADMIN_PER_PAGE = 20;
    public const MAX_IMAGES = 3;
    public const MIN_BODY = 10;
    public const MAX_BODY = 2000;
    private const MAX_UPLOAD = 5 * 1024 * 1024;
    private const MIME_EXT = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    public const FITS = ['small', 'true', 'large'];
    public const SORTS = [
        'newest'  => 'r.created_at DESC, r.id DESC',
        'helpful' => 'r.helpful_count DESC, r.created_at DESC',
        'highest' => 'r.rating DESC, r.created_at DESC',
        'lowest'  => 'r.rating ASC, r.created_at DESC',
    ];
    /** status => [label, badge classes] (admin) */
    public const STATUSES = [
        'pending'  => ['Chờ duyệt', 'bg-gold/20 text-gold-dark'],
        'approved' => ['Đang hiển thị', 'bg-emerald-100 text-emerald-800'],
        'rejected' => ['Đã ẩn', 'bg-ink/10 text-ink-soft'],
    ];

    // ---------------------------------------------------------------- who may review

    /**
     * What the current visitor can do for $productId:
     * state = guest | not_purchased | reviewed | can; plus the proving order / variant, or their existing review.
     */
    public static function eligibility(int $productId): array
    {
        $uid = Customer::id();
        if (!$uid) return ['state' => 'guest'];
        $mine = Database::one('SELECT id, status, rating FROM product_reviews WHERE user_id = ? AND product_id = ?', [$uid, $productId]);
        if ($mine) return ['state' => 'reviewed', 'review' => $mine];
        $line = Database::one(
            "SELECT o.id AS order_id, i.fabric_name, i.color, i.size
               FROM orders o JOIN order_items i ON i.order_id = o.id
              WHERE o.user_id = ? AND o.status = 'completed' AND i.product_id = ?
              ORDER BY o.created_at DESC LIMIT 1", [$uid, $productId]);
        if (!$line) return ['state' => 'not_purchased'];
        return ['state' => 'can', 'order_id' => (int) $line['order_id'], 'variant' => self::variantLabel($line)];
    }

    private static function variantLabel(array $line): string
    {
        $size = $line['size'] === 'CUSTOM' ? t('cart.customMade') : ($line['size'] !== 'FREE' ? 'Size ' . $line['size'] : null);
        return mb_substr(implode(' · ', array_filter([$line['fabric_name'], $line['color'], $size])), 0, 160);
    }

    /** "Nguyễn Thị Hoa" → "Nguyễn T. H." */
    public static function displayName(string $full): string
    {
        $parts = preg_split('/\s+/u', trim($full)) ?: [];
        if (count($parts) <= 1) return $full;
        $first = array_shift($parts);
        return $first . ' ' . implode(' ', array_map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)) . '.', $parts));
    }

    // ---------------------------------------------------------------- write (customer)

    /**
     * Validate and store a review with up to 3 photos.
     * @return string the new status (pending | approved)
     * @throws HttpException 403 when not eligible, 422 with field errors
     */
    public static function submit(int $productId, array $in, array $files): string
    {
        $el = self::eligibility($productId);
        if ($el['state'] !== 'can') throw HttpException::forbidden(t('reviews.errors.' . $el['state']));

        $rating = (int) ($in['rating'] ?? 0);
        $title = mb_substr(trim(preg_replace('/\s+/u', ' ', (string) ($in['title'] ?? ''))), 0, 120);
        $body = trim(str_replace("\r\n", "\n", (string) ($in['body'] ?? '')));
        $fit = in_array($in['fit'] ?? '', self::FITS, true) ? $in['fit'] : null;

        $errors = [];
        if ($rating < 1 || $rating > 5) $errors['rating'] = t('reviews.errors.rating');
        $len = mb_strlen($body);
        if ($len < self::MIN_BODY) $errors['body'] = t('reviews.errors.bodyShort', ['min' => self::MIN_BODY]);
        elseif ($len > self::MAX_BODY) $errors['body'] = t('reviews.errors.bodyLong', ['max' => self::MAX_BODY]);
        $uploads = self::collectUploads($files['images'] ?? [], $errors);
        if ($errors) throw HttpException::badRequest(t('pdp.errors.fix'), $errors);

        $status = Settings::value('reviews', 'auto_approve') ? 'approved' : 'pending';
        $user = Customer::user();
        $dir = dirname(__DIR__, 2) . '/uploads/reviews';
        if ($uploads && !is_dir($dir)) mkdir($dir, 0775, true);

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        $moved = [];
        try {
            Database::exec(
                'INSERT INTO product_reviews (product_id, user_id, order_id, author_name, rating, title, body, variant_label, fit, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$productId, $user['id'], $el['order_id'], $user['name'], $rating, $title ?: null, $body, $el['variant'] ?: null, $fit, $status]);
            $reviewId = (int) $pdo->lastInsertId();
            foreach ($uploads as $i => [$tmp, $ext]) {
                $file = sprintf('%d-%s.%s', $reviewId, bin2hex(random_bytes(6)), $ext);
                if (!move_uploaded_file($tmp, "$dir/$file")) throw new \RuntimeException('upload move failed');
                $moved[] = "$dir/$file";
                Database::exec('INSERT INTO review_images (review_id, url, sort_order) VALUES (?, ?, ?)', [$reviewId, "uploads/reviews/$file", $i]);
            }
            if ($status === 'approved') self::recalc($productId);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            foreach ($moved as $f) @unlink($f);
            if ($e instanceof \PDOException && $e->getCode() === '23000') throw HttpException::forbidden(t('reviews.errors.reviewed'));
            throw $e;
        }
        return $status;
    }

    /** Check $_FILES['images'] (multiple): real JPEG/PNG/WebP by content, ≤ 5MB, at most 3. */
    private static function collectUploads(array $files, array &$errors): array
    {
        $list = [];
        foreach ((array) ($files['name'] ?? []) as $i => $name) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            $list[] = ['name' => $name, 'tmp' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
        }
        if (count($list) > self::MAX_IMAGES) { $errors['images'] = t('reviews.errors.tooManyImages', ['max' => self::MAX_IMAGES]); return []; }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $ok = [];
        foreach ($list as $f) {
            if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp'])) { $errors['images'] = t('reviews.errors.upload'); continue; }
            if ($f['size'] > self::MAX_UPLOAD) { $errors['images'] = t('reviews.errors.imageSize'); continue; }
            $ext = self::MIME_EXT[$finfo->file($f['tmp'])] ?? null;
            if (!$ext) { $errors['images'] = t('reviews.errors.imageType'); continue; }
            $ok[] = [$f['tmp'], $ext];
        }
        return $ok;
    }

    /** "Helpful" vote, once per account / browser session; not on your own review. Returns the new count. */
    public static function vote(int $reviewId): int
    {
        $r = Database::one("SELECT id, user_id, helpful_count FROM product_reviews WHERE id = ? AND status = 'approved'", [$reviewId]);
        if (!$r) throw HttpException::notFound(t('reviews.errors.notFound'));
        $uid = Customer::id();
        if ($uid && (int) $r['user_id'] === $uid) throw HttpException::badRequest(t('reviews.errors.ownReview'));
        if (Database::exec('INSERT IGNORE INTO review_votes (review_id, voter) VALUES (?, ?)', [$reviewId, self::voterKey()])) {
            Database::exec('UPDATE product_reviews SET helpful_count = helpful_count + 1 WHERE id = ?', [$reviewId]);
            return (int) $r['helpful_count'] + 1;
        }
        return (int) $r['helpful_count'];
    }

    private static function voterKey(): string
    {
        if ($uid = Customer::id()) return 'u:' . $uid;
        $_SESSION['voter'] ??= bin2hex(random_bytes(16));   // stable across session id regeneration
        return 's:' . $_SESSION['voter'];
    }

    // ---------------------------------------------------------------- read (storefront)

    /** Rating summary for the product page. */
    public static function summary(int $productId): array
    {
        $dist = array_fill_keys([5, 4, 3, 2, 1], 0);
        foreach (Database::all("SELECT rating, COUNT(*) n FROM product_reviews WHERE product_id = ? AND status = 'approved' GROUP BY rating", [$productId]) as $r) {
            $dist[(int) $r['rating']] = (int) $r['n'];
        }
        $count = array_sum($dist);
        $avg = $count ? array_sum(array_map(fn($s, $n) => $s * $n, array_keys($dist), $dist)) / $count : 0;
        $fit = array_fill_keys(self::FITS, 0);
        foreach (Database::all("SELECT fit, COUNT(*) n FROM product_reviews WHERE product_id = ? AND status = 'approved' AND fit IS NOT NULL GROUP BY fit", [$productId]) as $r) {
            $fit[$r['fit']] = (int) $r['n'];
        }
        $photos = Database::all(
            "SELECT ri.url, ri.review_id FROM review_images ri JOIN product_reviews r ON r.id = ri.review_id
              WHERE r.product_id = ? AND r.status = 'approved' ORDER BY r.created_at DESC, ri.sort_order LIMIT 8", [$productId]);
        $photoReviews = (int) Database::one(
            "SELECT COUNT(DISTINCT r.id) n FROM product_reviews r JOIN review_images ri ON ri.review_id = r.id
              WHERE r.product_id = ? AND r.status = 'approved'", [$productId])['n'];
        return ['count' => $count, 'avg' => round($avg, 1), 'dist' => $dist, 'fit' => $fit, 'fit_total' => array_sum($fit),
                'photos' => $photos, 'photo_reviews' => $photoReviews];
    }

    /** Approved reviews with filters: rating (1–5), photos (bool), sort, page. */
    public static function forProduct(int $productId, array $f): array
    {
        $where = "r.product_id = :pid AND r.status = 'approved'";
        $p = ['pid' => $productId];
        $rating = (int) ($f['rating'] ?? 0);
        if ($rating >= 1 && $rating <= 5) { $where .= ' AND r.rating = :rating'; $p['rating'] = $rating; }
        if (!empty($f['photos'])) $where .= ' AND EXISTS (SELECT 1 FROM review_images ri WHERE ri.review_id = r.id)';
        $sort = isset(self::SORTS[$f['sort'] ?? '']) ? $f['sort'] : 'newest';

        $total = (int) Database::one("SELECT COUNT(*) n FROM product_reviews r WHERE $where", $p)['n'];
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min($pages, (int) ($f['page'] ?? 1)));
        $offset = ($page - 1) * self::PER_PAGE;
        $items = Database::all("SELECT r.* FROM product_reviews r WHERE $where ORDER BY " . self::SORTS[$sort]
                               . ' LIMIT ' . self::PER_PAGE . " OFFSET $offset", $p);
        self::attachImages($items);
        if ($items) {
            $ids = implode(',', array_map('intval', array_column($items, 'id')));
            $voted = array_column(Database::all("SELECT review_id FROM review_votes WHERE voter = ? AND review_id IN ($ids)", [self::voterKey()]), 'review_id');
            foreach ($items as &$it) $it['voted'] = in_array($it['id'], $voted);
            unset($it);
        }
        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages,
                'filters' => ['rating' => $rating ?: null, 'photos' => !empty($f['photos']), 'sort' => $sort]];
    }

    private static function attachImages(array &$items): void
    {
        if (!$items) return;
        $ids = implode(',', array_map('intval', array_column($items, 'id')));
        $by = [];
        foreach (Database::all("SELECT review_id, url FROM review_images WHERE review_id IN ($ids) ORDER BY sort_order, id") as $img) {
            $by[$img['review_id']][] = $img['url'];
        }
        foreach ($items as &$it) $it['images'] = $by[$it['id']] ?? [];
        unset($it);
    }

    // ---------------------------------------------------------------- customer account

    /** Products from completed orders that the customer hasn't reviewed yet. */
    public static function toReview(int $uid, string $l): array
    {
        return Database::all(
            "SELECT p.id, p.slug, p.name_$l AS name, MAX(o.created_at) AS bought_at,
                    (SELECT url FROM product_images im WHERE im.product_id = p.id AND im.view_type = 'front' ORDER BY im.sort_order LIMIT 1) AS image,
                    (SELECT color_hex FROM product_variants v WHERE v.product_id = p.id ORDER BY v.id LIMIT 1) AS tint
               FROM orders o JOIN order_items i ON i.order_id = o.id JOIN products p ON p.id = i.product_id AND p.is_active = 1
              WHERE o.user_id = ? AND o.status = 'completed'
                AND NOT EXISTS (SELECT 1 FROM product_reviews r WHERE r.user_id = o.user_id AND r.product_id = p.id)
              GROUP BY p.id ORDER BY bought_at DESC", [$uid]);
    }

    /** The customer's own reviews (any status). */
    public static function mine(int $uid, string $l): array
    {
        $items = Database::all(
            "SELECT r.*, p.slug, p.name_$l AS product_name FROM product_reviews r JOIN products p ON p.id = r.product_id
              WHERE r.user_id = ? ORDER BY r.created_at DESC", [$uid]);
        self::attachImages($items);
        return $items;
    }

    /** Product ids the customer has already reviewed (for "write a review" links). */
    public static function reviewedIds(int $uid): array
    {
        return array_map('intval', array_column(Database::all('SELECT product_id FROM product_reviews WHERE user_id = ?', [$uid]), 'product_id'));
    }

    // ---------------------------------------------------------------- admin

    public static function adminList(array $f): array
    {
        $where = ['1 = 1'];
        $p = [];
        if (isset(self::STATUSES[$f['status'] ?? ''])) { $where[] = 'r.status = :st'; $p['st'] = $f['status']; }
        if (($r = (int) ($f['rating'] ?? 0)) >= 1 && $r <= 5) { $where[] = 'r.rating = :rt'; $p['rt'] = $r; }
        if (($f['q'] ?? '') !== '') {
            $where[] = '(r.body LIKE :q1 OR r.title LIKE :q2 OR r.author_name LIKE :q3 OR p.name_vi LIKE :q4)';
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            $p += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }
        $whereSql = ' WHERE ' . implode(' AND ', $where);
        $total = (int) Database::one("SELECT COUNT(*) n FROM product_reviews r JOIN products p ON p.id = r.product_id $whereSql", $p)['n'];
        $pages = max(1, (int) ceil($total / self::ADMIN_PER_PAGE));
        $page = max(1, min($pages, (int) ($f['page'] ?? 1)));
        $offset = ($page - 1) * self::ADMIN_PER_PAGE;
        $items = Database::all(
            "SELECT r.*, p.name_vi AS product_name, p.slug, o.order_code, u.email, a.full_name AS replier
               FROM product_reviews r
               JOIN products p ON p.id = r.product_id
               LEFT JOIN orders o ON o.id = r.order_id
               LEFT JOIN users u ON u.id = r.user_id
               LEFT JOIN users a ON a.id = r.replied_by
             $whereSql
              ORDER BY FIELD(r.status, 'pending') DESC, r.created_at DESC, r.id DESC
              LIMIT " . self::ADMIN_PER_PAGE . " OFFSET $offset", $p);
        self::attachImages($items);
        return compact('items', 'total', 'page', 'pages');
    }

    public static function counts(): array
    {
        $c = ['' => 0] + array_fill_keys(array_keys(self::STATUSES), 0);
        foreach (Database::all('SELECT status, COUNT(*) n FROM product_reviews GROUP BY status') as $r) {
            $c[$r['status']] = (int) $r['n'];
            $c[''] += (int) $r['n'];
        }
        return $c;
    }

    public static function pendingCount(): int
    {
        try {
            return (int) Database::one("SELECT COUNT(*) n FROM product_reviews WHERE status = 'pending'")['n'];
        } catch (\PDOException) {
            return 0;   // table not migrated yet
        }
    }

    public static function find(int $id): ?array
    {
        $items = Database::all('SELECT r.*, p.name_vi AS product_name, p.slug, o.order_code, u.email, a.full_name AS replier
                                  FROM product_reviews r JOIN products p ON p.id = r.product_id
                                  LEFT JOIN orders o ON o.id = r.order_id LEFT JOIN users u ON u.id = r.user_id
                                  LEFT JOIN users a ON a.id = r.replied_by WHERE r.id = ?', [$id]);
        self::attachImages($items);
        return $items[0] ?? null;
    }

    /** Approve / hide many reviews. Returns how many changed. */
    public static function setStatus(array $ids, string $status): int
    {
        if (!isset(self::STATUSES[$status]) || $status === 'pending') throw HttpException::badRequest('Trạng thái không hợp lệ');
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) throw HttpException::badRequest('Chưa chọn đánh giá nào');
        $in = implode(',', array_slice($ids, 0, 200));
        $products = array_column(Database::all("SELECT DISTINCT product_id FROM product_reviews WHERE id IN ($in) AND status <> ?", [$status]), 'product_id');
        $n = Database::exec("UPDATE product_reviews SET status = ? WHERE id IN ($in) AND status <> ?", [$status, $status]);
        foreach ($products as $pid) self::recalc((int) $pid);
        if ($n) Activity::log('review.' . ($status === 'approved' ? 'approve' : 'hide'),
                              ($status === 'approved' ? 'Duyệt ' : 'Ẩn ') . "$n đánh giá (#" . implode(', #', array_slice($ids, 0, 10)) . ')', 'review', $ids[0]);
        return $n;
    }

    /** Public answer from the shop; empty text removes it. */
    public static function reply(int $id, string $text): void
    {
        $r = Database::one('SELECT id, product_id FROM product_reviews WHERE id = ?', [$id]);
        if (!$r) throw HttpException::notFound('Không tìm thấy đánh giá');
        $text = trim(str_replace("\r\n", "\n", $text));
        if (mb_strlen($text) > 1000) throw HttpException::badRequest('Tối đa 1000 ký tự', ['reply' => 'Tối đa 1000 ký tự']);
        Database::exec('UPDATE product_reviews SET reply = ?, replied_at = ?, replied_by = ? WHERE id = ?',
                       [$text ?: null, $text ? date('Y-m-d H:i:s') : null, $text ? (Auth::user()['id'] ?? null) : null, $id]);
        Activity::log('review.reply', ($text ? 'Trả lời' : 'Xoá trả lời') . " đánh giá #$id", 'review', $id);
    }

    public static function delete(int $id): void
    {
        $r = Database::one('SELECT id, product_id, author_name FROM product_reviews WHERE id = ?', [$id]);
        if (!$r) throw HttpException::notFound('Không tìm thấy đánh giá');
        $root = dirname(__DIR__, 2);
        foreach (Database::all('SELECT url FROM review_images WHERE review_id = ?', [$id]) as $img) {
            if (str_starts_with($img['url'], 'uploads/reviews/') && !str_contains($img['url'], '..') && is_file("$root/{$img['url']}")) {
                unlink("$root/{$img['url']}");
            }
        }
        Database::exec('DELETE FROM product_reviews WHERE id = ?', [$id]);
        self::recalc((int) $r['product_id']);
        Activity::log('review.delete', "Xoá đánh giá #$id của {$r['author_name']}", 'review', $id);
    }

    /** Refresh the cached average / count on the product. */
    public static function recalc(int $productId): void
    {
        Database::exec(
            "UPDATE products p SET
                rating_avg   = (SELECT COALESCE(ROUND(AVG(rating), 2), 0) FROM product_reviews WHERE product_id = p.id AND status = 'approved'),
                rating_count = (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id AND status = 'approved')
              WHERE p.id = ?", [$productId]);
    }
}
