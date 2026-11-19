<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException};
use App\Support\{Customer, Tailoring};

/** "My account": profile, password, address book, saved measurements and order history. */
final class AccountService
{
    public const MAX_ADDRESSES = 10;
    public const MAX_MEASUREMENTS = 10;
    public const ORDERS_PER_PAGE = 8;

    /** Customer-facing order filter tabs => statuses. */
    public const ORDER_TABS = [
        'all'        => [],
        'processing' => ['pending', 'confirmed', 'tailoring'],
        'shipping'   => ['shipping'],
        'completed'  => ['completed'],
        'cancelled'  => ['cancelled'],
    ];

    private static function clean(array $in, string $k, int $max): string
    {
        return mb_substr(trim(preg_replace('/\s+/u', ' ', (string) ($in[$k] ?? ''))), 0, $max);
    }

    private static function phone(array $in, string $k = 'phone'): string
    {
        return preg_replace('/[\s.\-()]/', '', (string) ($in[$k] ?? ''));
    }

    private static function validPhone(string $p): bool
    {
        return (bool) preg_match('/^(0|\+84)(3|5|7|8|9)\d{8}$/', $p);
    }

    // ---------------------------------------------------------------- profile

    /** @return string the saved name */
    public static function updateProfile(int $uid, array $in): string
    {
        $name = self::clean($in, 'full_name', 120);
        $phone = self::phone($in);
        $errors = [];
        if (mb_strlen($name) < 2) $errors['full_name'] = t('checkout.errors.name');
        if ($phone !== '' && !self::validPhone($phone)) $errors['phone'] = t('checkout.errors.phone');
        if ($errors) throw HttpException::badRequest(t('pdp.errors.fix'), $errors);
        Database::exec('UPDATE users SET full_name = ?, phone = ? WHERE id = ?', [$name, $phone ?: null, $uid]);
        return $name;
    }

    public static function changePassword(int $uid, array $in): void
    {
        $current = (string) ($in['current_password'] ?? '');
        $new = (string) ($in['new_password'] ?? '');
        $row = Database::one('SELECT password_hash FROM users WHERE id = ?', [$uid]);
        $errors = [];
        if (!$row || !password_verify($current, $row['password_hash'])) $errors['current_password'] = t('auth.errors.currentWrong');
        if (mb_strlen($new) < Customer::MIN_PASSWORD) $errors['new_password'] = t('auth.errors.passwordShort', ['min' => Customer::MIN_PASSWORD]);
        elseif ($new !== (string) ($in['new_password_confirm'] ?? '')) $errors['new_password_confirm'] = t('auth.errors.passwordMismatch');
        elseif ($new === $current) $errors['new_password'] = t('auth.errors.passwordSame');
        if ($errors) throw HttpException::badRequest(t('pdp.errors.fix'), $errors);
        Database::exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $uid]);
        session_regenerate_id(true);
    }

    // ---------------------------------------------------------------- overview

    public static function stats(int $uid): array
    {
        $r = Database::one(
            "SELECT COUNT(*) AS orders,
                    COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total END), 0) AS spent,
                    SUM(status IN ('pending','confirmed','tailoring','shipping')) AS active
               FROM orders WHERE user_id = ?", [$uid]);
        return [
            'orders' => (int) $r['orders'], 'spent' => (int) $r['spent'], 'active' => (int) $r['active'],
            'wishlist' => (int) Database::one('SELECT COUNT(*) n FROM wishlists WHERE user_id = ?', [$uid])['n'],
            'addresses' => (int) Database::one('SELECT COUNT(*) n FROM customer_addresses WHERE user_id = ?', [$uid])['n'],
            'measurements' => (int) Database::one('SELECT COUNT(*) n FROM customer_measurements WHERE user_id = ?', [$uid])['n'],
        ];
    }

    // ---------------------------------------------------------------- orders

    public static function orders(int $uid, string $tab = 'all', int $page = 1, int $perPage = self::ORDERS_PER_PAGE): array
    {
        $tab = isset(self::ORDER_TABS[$tab]) ? $tab : 'all';
        $where = 'o.user_id = ?';
        $params = [$uid];
        if ($statuses = self::ORDER_TABS[$tab]) {
            $where .= ' AND o.status IN (' . implode(',', array_fill(0, count($statuses), '?')) . ')';
            array_push($params, ...$statuses);
        }
        $total = (int) Database::one("SELECT COUNT(*) n FROM orders o WHERE $where", $params)['n'];
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($pages, $page));
        $offset = ($page - 1) * $perPage;
        $items = Database::all(
            "SELECT o.*, (SELECT SUM(quantity) FROM order_items i WHERE i.order_id = o.id) AS item_count
               FROM orders o WHERE $where ORDER BY o.created_at DESC, o.id DESC LIMIT $perPage OFFSET $offset", $params);

        // first 3 item names + product slugs for a preview line
        if ($items) {
            $ids = array_column($items, 'id');
            $rows = Database::all('SELECT i.order_id, i.product_name, i.quantity, p.slug, p.id AS pid,
                                          (SELECT color_hex FROM product_variants v WHERE v.id = i.variant_id) AS tint,
                                          (SELECT url FROM product_images im WHERE im.product_id = i.product_id AND im.view_type = \'front\' ORDER BY im.sort_order LIMIT 1) AS image
                                     FROM order_items i LEFT JOIN products p ON p.id = i.product_id
                                    WHERE i.order_id IN (' . implode(',', array_map('intval', $ids)) . ') ORDER BY i.id');
            $byOrder = [];
            foreach ($rows as $r) $byOrder[$r['order_id']][] = $r;
            foreach ($items as &$o) $o['lines'] = $byOrder[$o['id']] ?? [];
            unset($o);
        }

        $counts = ['all' => 0];
        foreach (Database::all('SELECT status, COUNT(*) n FROM orders WHERE user_id = ? GROUP BY status', [$uid]) as $r) {
            $counts['all'] += (int) $r['n'];
            foreach (self::ORDER_TABS as $k => $sts) if ($sts && in_array($r['status'], $sts, true)) $counts[$k] = ($counts[$k] ?? 0) + (int) $r['n'];
        }
        return compact('items', 'total', 'page', 'pages', 'tab', 'counts');
    }

    /** One of the customer's orders, with items and the step timestamps for the progress tracker. */
    public static function order(int $uid, string $code): ?array
    {
        $o = Database::one('SELECT * FROM orders WHERE order_code = ? AND user_id = ?', [$code, $uid]);
        return $o ? self::withDetails($o) : null;
    }

    /** Public lookup by order code + phone (the phone works as the "password"). */
    public static function lookup(string $code, string $phone): ?array
    {
        $code = strtoupper(trim($code));
        $phone = preg_replace('/[\s.\-()]/', '', $phone);
        if ($code === '' || $phone === '') return null;
        $o = Database::one('SELECT * FROM orders WHERE order_code = ? AND phone = ?', [$code, $phone]);
        return $o ? self::withDetails($o) : null;
    }

    private static function withDetails(array $o): array
    {
        $o['items'] = Database::all(
            "SELECT i.*, p.slug, p.is_active AS product_active,
                    (SELECT url FROM product_images im WHERE im.product_id = i.product_id AND im.view_type = 'front' ORDER BY im.sort_order LIMIT 1) AS image,
                    (SELECT color_hex FROM product_variants v WHERE v.id = i.variant_id) AS tint
               FROM order_items i LEFT JOIN products p ON p.id = i.product_id
              WHERE i.order_id = ? ORDER BY i.id", [$o['id']]);
        $o['has_custom'] = (bool) array_filter($o['items'], fn($i) => $i['size'] === 'CUSTOM');

        // When each status was reached, read back from the timeline ("A → B" messages)
        $reached = ['pending' => $o['created_at']];
        $paidAt = null;
        foreach (Database::all("SELECT type, message, created_at FROM order_events
                                 WHERE order_id = ? AND type IN ('status','payment') ORDER BY created_at, id", [$o['id']]) as $ev) {
            if ($ev['type'] === 'payment') {
                if (str_ends_with($ev['message'], '→ ' . AdminOrderService::PAYMENT_STATUSES['paid'][0])) $paidAt = $ev['created_at'];
                continue;
            }
            foreach (AdminOrderService::STATUSES as $st => [$label]) {
                if (str_ends_with($ev['message'], '→ ' . $label)) $reached[$st] = $ev['created_at'];
            }
        }
        $o['reached'] = $reached;
        $o['paid_at'] = $paidAt;
        $o['updated_at'] = max($reached);
        return $o;
    }

    public static function cancel(int $uid, string $code): void
    {
        if (!Database::one('SELECT 1 FROM orders WHERE order_code = ? AND user_id = ?', [$code, $uid])) {
            throw HttpException::notFound(t('account.orders.notFound'));
        }
        AdminOrderService::update($code, ['status' => 'cancelled'], true);
    }

    // ---------------------------------------------------------------- addresses

    public static function addresses(int $uid): array
    {
        return Database::all('SELECT * FROM customer_addresses WHERE user_id = ? ORDER BY is_default DESC, updated_at DESC, id DESC', [$uid]);
    }

    public static function defaultAddress(int $uid): ?array
    {
        return Database::one('SELECT * FROM customer_addresses WHERE user_id = ? ORDER BY is_default DESC, updated_at DESC LIMIT 1', [$uid]);
    }

    /** Validate address fields (same rules as checkout). @return array{0: array, 1: array} */
    public static function validateAddress(array $in): array
    {
        $d = [
            'label'          => self::clean($in, 'label', 40),
            'recipient_name' => self::clean($in, 'recipient_name', 120),
            'phone'          => self::phone($in),
            'address'        => self::clean($in, 'address', 180),
            'city'           => self::clean($in, 'city', 70),
        ];
        $e = [];
        if (mb_strlen($d['recipient_name']) < 2) $e['recipient_name'] = t('checkout.errors.name');
        if (!self::validPhone($d['phone'])) $e['phone'] = t('checkout.errors.phone');
        if (mb_strlen($d['address']) < 8) $e['address'] = t('checkout.errors.address');
        if (mb_strlen($d['city']) < 2) $e['city'] = t('checkout.errors.city');
        return [$d, $e];
    }

    public static function saveAddress(int $uid, ?int $id, array $in): int
    {
        [$d, $errors] = self::validateAddress($in);
        if ($errors) throw HttpException::badRequest(t('pdp.errors.fix'), $errors);
        $makeDefault = !empty($in['is_default']);

        if ($id) {
            if (!Database::one('SELECT 1 FROM customer_addresses WHERE id = ? AND user_id = ?', [$id, $uid])) {
                throw HttpException::notFound(t('account.addresses.notFound'));
            }
            Database::exec('UPDATE customer_addresses SET label = ?, recipient_name = ?, phone = ?, address = ?, city = ? WHERE id = ?',
                           [$d['label'] ?: null, $d['recipient_name'], $d['phone'], $d['address'], $d['city'], $id]);
        } else {
            $count = (int) Database::one('SELECT COUNT(*) n FROM customer_addresses WHERE user_id = ?', [$uid])['n'];
            if ($count >= self::MAX_ADDRESSES) throw HttpException::badRequest(t('account.addresses.limit', ['max' => self::MAX_ADDRESSES]));
            Database::exec('INSERT INTO customer_addresses (user_id, label, recipient_name, phone, address, city) VALUES (?, ?, ?, ?, ?, ?)',
                           [$uid, $d['label'] ?: null, $d['recipient_name'], $d['phone'], $d['address'], $d['city']]);
            $id = (int) Database::pdo()->lastInsertId();
            if ($count === 0) $makeDefault = true;   // the first address is the default
        }
        if ($makeDefault) self::setDefaultAddress($uid, $id);
        return $id;
    }

    /** Save the checkout address to the book unless the same one is already there. */
    public static function rememberAddress(int $uid, array $customer): void
    {
        $exists = Database::one('SELECT 1 FROM customer_addresses WHERE user_id = ? AND phone = ? AND address = ? AND city = ?',
                                [$uid, $customer['phone'], $customer['address'], $customer['city']]);
        if ($exists) return;
        try {
            self::saveAddress($uid, null, ['recipient_name' => $customer['customer_name'], 'phone' => $customer['phone'],
                                           'address' => $customer['address'], 'city' => $customer['city']]);
        } catch (HttpException) {
            // book full or invalid — the order itself is already placed, so just skip
        }
    }

    public static function setDefaultAddress(int $uid, int $id): void
    {
        if (!Database::one('SELECT 1 FROM customer_addresses WHERE id = ? AND user_id = ?', [$id, $uid])) {
            throw HttpException::notFound(t('account.addresses.notFound'));
        }
        Database::exec('UPDATE customer_addresses SET is_default = (id = ?) WHERE user_id = ?', [$id, $uid]);
    }

    public static function deleteAddress(int $uid, int $id): void
    {
        $row = Database::one('SELECT is_default FROM customer_addresses WHERE id = ? AND user_id = ?', [$id, $uid]);
        if (!$row) throw HttpException::notFound(t('account.addresses.notFound'));
        Database::exec('DELETE FROM customer_addresses WHERE id = ?', [$id]);
        if ($row['is_default']) {   // promote the most recent remaining address
            Database::exec('UPDATE customer_addresses SET is_default = 1 WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1', [$uid]);
        }
    }

    // ---------------------------------------------------------------- measurements

    public static function measurements(int $uid): array
    {
        return Database::all('SELECT * FROM customer_measurements WHERE user_id = ? ORDER BY updated_at DESC, id DESC', [$uid]);
    }

    public static function saveMeasurement(int $uid, ?int $id, array $in): int
    {
        [$m, $errors] = Tailoring::validate($in);
        $name = self::clean($in, 'name', 60);
        if ($name === '') $errors['name'] = t('account.measurements.nameRequired');
        if ($errors) throw HttpException::badRequest(t('pdp.errors.fix'), $errors);
        $vals = [$name, $m['height'], $m['weight'], $m['bust'], $m['waist'], $m['hip'], $m['note'] ?? null];

        if ($id) {
            if (!Database::one('SELECT 1 FROM customer_measurements WHERE id = ? AND user_id = ?', [$id, $uid])) {
                throw HttpException::notFound(t('account.measurements.notFound'));
            }
            Database::exec('UPDATE customer_measurements SET name = ?, height = ?, weight = ?, bust = ?, waist = ?, hip = ?, note = ? WHERE id = ?',
                           [...$vals, $id]);
            return $id;
        }
        $count = (int) Database::one('SELECT COUNT(*) n FROM customer_measurements WHERE user_id = ?', [$uid])['n'];
        if ($count >= self::MAX_MEASUREMENTS) throw HttpException::badRequest(t('account.measurements.limit', ['max' => self::MAX_MEASUREMENTS]));
        Database::exec('INSERT INTO customer_measurements (user_id, name, height, weight, bust, waist, hip, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                       [$uid, ...$vals]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function deleteMeasurement(int $uid, int $id): void
    {
        if (!Database::exec('DELETE FROM customer_measurements WHERE id = ? AND user_id = ?', [$id, $uid])) {
            throw HttpException::notFound(t('account.measurements.notFound'));
        }
    }

    /** Measurement row → the plain array Tailoring / measurements_text() use. */
    public static function measurementValues(array $row): array
    {
        $out = [];
        foreach (array_keys(Tailoring::FIELDS) as $k) $out[$k] = (float) $row[$k];
        if (!empty($row['note'])) $out['note'] = $row['note'];
        return $out;
    }
}
