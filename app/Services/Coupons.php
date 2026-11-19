<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException};

/**
 * Coupon codes: validation and discount maths (storefront) + CRUD (admin).
 * Discounts apply to the items subtotal; free_shipping zeroes the shipping fee.
 */
final class Coupons
{
    public const TYPES = ['percent' => 'Giảm %', 'fixed' => 'Giảm số tiền', 'free_shipping' => 'Miễn phí vận chuyển'];
    public const PER_PAGE = 20;

    public static function normalize(string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', $code));
    }

    public static function findByCode(string $code, bool $forUpdate = false): ?array
    {
        return Database::one('SELECT * FROM coupons WHERE code = ?' . ($forUpdate ? ' FOR UPDATE' : ''), [self::normalize($code)]);
    }

    /**
     * Check a coupon against a cart. Phone-based limits are only checked when $phone is known (at checkout).
     * @return array{ok:bool, error:?string, discount:int, free_shipping:bool}
     */
    public static function evaluate(array $c, int $subtotal, ?string $phone = null): array
    {
        $fail = fn(string $key, array $r = []) => ['ok' => false, 'error' => t('coupon.errors.' . $key, $r), 'discount' => 0, 'free_shipping' => false];
        $now = time();

        if (!$c['is_active']) return $fail('invalid');
        if ($c['starts_at'] && strtotime($c['starts_at']) > $now) return $fail('notStarted', ['date' => date('d/m/Y', strtotime($c['starts_at']))]);
        if ($c['ends_at'] && strtotime($c['ends_at']) < $now) return $fail('expired');
        if ($c['usage_limit'] !== null && (int) $c['used_count'] >= (int) $c['usage_limit']) return $fail('usedUp');
        if ($subtotal < (int) $c['min_order']) return $fail('minOrder', ['amount' => money($c['min_order']), 'more' => money((int) $c['min_order'] - $subtotal)]);
        if ($phone !== null && $c['per_phone_limit'] !== null) {
            $used = (int) Database::one("SELECT COUNT(*) n FROM orders WHERE coupon_code = ? AND phone = ? AND status <> 'cancelled'",
                                        [$c['code'], $phone])['n'];
            if ($used >= (int) $c['per_phone_limit']) return $fail('perPhone');
        }

        $discount = match ($c['type']) {
            'percent' => (int) floor($subtotal * (int) $c['value'] / 100),
            'fixed'   => (int) $c['value'],
            default   => 0,
        };
        if ($c['type'] === 'percent' && $c['max_discount'] !== null) $discount = min($discount, (int) $c['max_discount']);
        $discount = max(0, min($discount, $subtotal));

        return ['ok' => true, 'error' => null, 'discount' => $discount, 'free_shipping' => $c['type'] === 'free_shipping'];
    }

    /** Human description, e.g. "Giảm 10% (tối đa 200.000 ₫) cho đơn từ 1.000.000 ₫". */
    public static function describe(array $c): string
    {
        $what = match ($c['type']) {
            'percent' => t('coupon.desc.percent', ['value' => (int) $c['value']]) . ($c['max_discount'] !== null ? ' ' . t('coupon.desc.max', ['amount' => money($c['max_discount'])]) : ''),
            'fixed'   => t('coupon.desc.fixed', ['amount' => money($c['value'])]),
            default   => t('coupon.desc.freeShip'),
        };
        return $what . ((int) $c['min_order'] > 0 ? ' ' . t('coupon.desc.min', ['amount' => money($c['min_order'])]) : '');
    }

    /**
     * Inside the order transaction: lock the coupon row, re-check everything (incl. per-phone limit)
     * and take one use. @throws HttpException 422 when it can no longer be used.
     */
    public static function redeem(string $code, int $subtotal, string $phone): array
    {
        $c = self::findByCode($code, true);
        if (!$c) throw HttpException::badRequest(t('coupon.errors.invalid'), ['coupon' => t('coupon.errors.invalid')]);
        $r = self::evaluate($c, $subtotal, $phone);
        if (!$r['ok']) throw HttpException::badRequest($r['error'], ['coupon' => $r['error']]);
        Database::exec('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?', [$c['id']]);
        return $r + ['code' => $c['code']];
    }

    /** Give a use back (order cancelled). */
    public static function release(?string $code): void
    {
        if ($code) Database::exec('UPDATE coupons SET used_count = GREATEST(used_count - 1, 0) WHERE code = ?', [$code]);
    }

    // ---------------------------------------------------------------- admin

    /** status => [label, badge classes] */
    public static function status(array $c): array
    {
        $now = time();
        return match (true) {
            !$c['is_active']                                                          => ['Đã tắt', 'bg-line text-ink-soft'],
            $c['ends_at'] && strtotime($c['ends_at']) < $now                          => ['Hết hạn', 'bg-ink/10 text-ink'],
            $c['usage_limit'] !== null && (int) $c['used_count'] >= (int) $c['usage_limit'] => ['Hết lượt', 'bg-ink/10 text-ink'],
            $c['starts_at'] && strtotime($c['starts_at']) > $now                      => ['Chưa bắt đầu', 'bg-sky-100 text-sky-800'],
            default                                                                   => ['Đang chạy', 'bg-emerald-100 text-emerald-800'],
        };
    }

    public static function list(array $f): array
    {
        $where = ['1 = 1']; $p = [];
        if (($f['q'] ?? '') !== '') { $where[] = '(c.code LIKE :q1 OR c.description LIKE :q2)'; $p['q1'] = $p['q2'] = '%' . addcslashes($f['q'], '%_\\') . '%'; }
        match ($f['status'] ?? '') {
            'running' => $where[] = "c.is_active = 1 AND (c.starts_at IS NULL OR c.starts_at <= NOW()) AND (c.ends_at IS NULL OR c.ends_at >= NOW())
                                     AND (c.usage_limit IS NULL OR c.used_count < c.usage_limit)",
            'ended'   => $where[] = '(c.is_active = 0 OR c.ends_at < NOW() OR (c.usage_limit IS NOT NULL AND c.used_count >= c.usage_limit))',
            default   => null,
        };
        $sql = 'FROM coupons c WHERE ' . implode(' AND ', $where);
        $total = (int) Database::one("SELECT COUNT(*) n $sql", $p)['n'];
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min($pages, (int) ($f['page'] ?? 1)));
        $items = Database::all(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM orders o WHERE o.coupon_code = c.code AND o.status <> 'cancelled') AS order_count,
                    (SELECT COALESCE(SUM(o.discount), 0) FROM orders o WHERE o.coupon_code = c.code AND o.status <> 'cancelled') AS discount_total,
                    (SELECT COALESCE(SUM(o.total), 0) FROM orders o WHERE o.coupon_code = c.code AND o.status <> 'cancelled') AS revenue
             $sql ORDER BY c.created_at DESC, c.id DESC LIMIT " . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE), $p);
        return compact('items', 'total', 'page', 'pages');
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM coupons WHERE id = ?', [$id]);
    }

    /** Create ($id null) or update. @return int id */
    public static function save(?int $id, array $in): int
    {
        $int = fn(string $k) => ($in[$k] ?? '') === '' ? null : (int) preg_replace('/\D/', '', (string) $in[$k]);
        $dt = function (string $k) use ($in): ?string {
            $v = trim((string) ($in[$k] ?? ''));
            if ($v === '') return null;
            $ts = strtotime(str_replace('T', ' ', $v));
            return $ts ? date('Y-m-d H:i:00', $ts) : 'invalid';
        };
        $d = [
            'code'            => self::normalize((string) ($in['code'] ?? '')),
            'description'     => mb_substr(trim((string) ($in['description'] ?? '')), 0, 255) ?: null,
            'type'            => (string) ($in['type'] ?? ''),
            'value'           => $int('value') ?? 0,
            'max_discount'    => $int('max_discount'),
            'min_order'       => $int('min_order') ?? 0,
            'starts_at'       => $dt('starts_at'),
            'ends_at'         => $dt('ends_at'),
            'usage_limit'     => $int('usage_limit'),
            'per_phone_limit' => $int('per_phone_limit'),
            'is_active'       => !empty($in['is_active']) ? 1 : 0,
        ];

        $e = [];
        if (!preg_match('/^[A-Z0-9_-]{3,40}$/', $d['code'])) $e['code'] = '3–40 ký tự: chữ không dấu, số, - hoặc _';
        elseif (Database::one('SELECT id FROM coupons WHERE code = ? AND id <> ?', [$d['code'], $id ?? 0])) $e['code'] = 'Mã đã tồn tại';
        if (!isset(self::TYPES[$d['type']])) $e['type'] = 'Chọn loại giảm giá';
        if ($d['type'] === 'percent' && ($d['value'] < 1 || $d['value'] > 100)) $e['value'] = 'Từ 1 đến 100 (%)';
        if ($d['type'] === 'fixed' && ($d['value'] < 1000 || $d['value'] > 100_000_000)) $e['value'] = 'Từ 1.000 ₫';
        if ($d['type'] === 'free_shipping') { $d['value'] = 0; $d['max_discount'] = null; }
        if ($d['type'] === 'fixed') $d['max_discount'] = null;
        if ($d['starts_at'] === 'invalid') $e['starts_at'] = 'Ngày giờ không hợp lệ';
        if ($d['ends_at'] === 'invalid') $e['ends_at'] = 'Ngày giờ không hợp lệ';
        if (!$e && $d['starts_at'] && $d['ends_at'] && $d['ends_at'] <= $d['starts_at']) $e['ends_at'] = 'Phải sau thời điểm bắt đầu';
        if ($d['usage_limit'] === 0) $e['usage_limit'] = 'Để trống nếu không giới hạn';
        if ($d['per_phone_limit'] === 0) $e['per_phone_limit'] = 'Để trống nếu không giới hạn';
        if ($e) throw HttpException::badRequest('Vui lòng kiểm tra lại', $e);

        if ($id) {
            if (!self::find($id)) throw HttpException::notFound('Không tìm thấy mã');
            $set = implode(', ', array_map(fn($c) => "$c = :$c", array_keys($d)));
            Database::exec("UPDATE coupons SET $set WHERE id = :id", $d + ['id' => $id]);
            Activity::log('promo.coupon', "Sửa mã {$d['code']}", 'coupon', $id);
        } else {
            $cols = array_keys($d);
            Database::exec('INSERT INTO coupons (' . implode(',', $cols) . ') VALUES (:' . implode(',:', $cols) . ')', $d);
            $id = (int) Database::pdo()->lastInsertId();
            Activity::log('promo.coupon', "Tạo mã {$d['code']} — " . self::describe($d), 'coupon', $id);
        }
        return $id;
    }

    /** Unused codes are deleted; used ones are only switched off (orders keep referring to them). */
    public static function delete(int $id): string
    {
        $c = self::find($id);
        if (!$c) throw HttpException::notFound('Không tìm thấy mã');
        if ((int) Database::one('SELECT COUNT(*) n FROM orders WHERE coupon_code = ?', [$c['code']])['n'] > 0) {
            Database::exec('UPDATE coupons SET is_active = 0 WHERE id = ?', [$id]);
            Activity::log('promo.coupon', "Tắt mã {$c['code']} (đã có đơn dùng nên không xoá)", 'coupon', $id);
            return 'Mã đã có đơn sử dụng nên được tắt thay vì xoá';
        }
        Database::exec('DELETE FROM coupons WHERE id = ?', [$id]);
        Activity::log('promo.coupon', "Xoá mã {$c['code']}", 'coupon', $id);
        return 'Đã xoá mã';
    }

    /** Random readable code, e.g. CPV-7K3QX2 */
    public static function generateCode(string $prefix = 'CPV'): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $s = '';
            for ($i = 0; $i < 6; $i++) $s .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            $code = $prefix . '-' . $s;
        } while (Database::one('SELECT id FROM coupons WHERE code = ?', [$code]));
        return $code;
    }
}
