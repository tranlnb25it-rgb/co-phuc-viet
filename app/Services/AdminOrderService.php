<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException};

final class AdminOrderService
{
    /** status => [label, badge classes] */
    public const STATUSES = [
        'pending'   => ['Chờ xác nhận', 'bg-gold/20 text-gold-dark'],
        'confirmed' => ['Đã xác nhận',  'bg-sky-100 text-sky-800'],
        'tailoring' => ['Đang may',     'bg-violet-100 text-violet-800'],
        'shipping'  => ['Đang giao',    'bg-indigo-100 text-indigo-800'],
        'completed' => ['Hoàn thành',   'bg-emerald-100 text-emerald-800'],
        'cancelled' => ['Đã huỷ',       'bg-crimson/10 text-crimson'],
    ];
    public const PAYMENT_STATUSES = [
        'pending'  => ['Chưa thanh toán', 'bg-gold/20 text-gold-dark'],
        'paid'     => ['Đã thanh toán',   'bg-emerald-100 text-emerald-800'],
        'refunded' => ['Đã hoàn tiền',    'bg-ink/10 text-ink'],
    ];
    public const PAYMENT_METHODS = ['COD' => 'COD', 'BANK_TRANSFER' => 'Chuyển khoản'];
    public const PER_PAGE = 15;
    public const EXPORT_LIMIT = 5000;

    // ---------------------------------------------------------------- queries

    /** WHERE clause shared by the list, the export and bulk "select all matching". */
    private static function where(array $f): array
    {
        $where = ['1 = 1'];
        $params = [];
        if (isset(self::STATUSES[$f['status'] ?? ''])) { $where[] = 'o.status = :st'; $params['st'] = $f['status']; }
        if (($f['q'] ?? '') !== '') {
            $where[] = '(o.order_code LIKE :q1 OR o.phone LIKE :q2 OR o.customer_name LIKE :q3)';
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }
        if (!empty($f['from'])) { $where[] = 'o.created_at >= :from'; $params['from'] = $f['from'] . ' 00:00:00'; }
        if (!empty($f['to']))   { $where[] = 'o.created_at <= :to';   $params['to']   = $f['to'] . ' 23:59:59'; }
        return ['FROM orders o WHERE ' . implode(' AND ', $where), $params];
    }

    public static function list(array $f): array
    {
        [$sql, $params] = self::where($f);
        $total = (int) Database::one("SELECT COUNT(*) n $sql", $params)['n'];
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min($pages, (int) ($f['page'] ?? 1)));
        $offset = ($page - 1) * self::PER_PAGE;
        $items = Database::all(
            "SELECT o.*, (SELECT SUM(quantity) FROM order_items i WHERE i.order_id = o.id) AS item_count,
                    (SELECT COUNT(*) FROM order_items i WHERE i.order_id = o.id AND i.size = 'CUSTOM') AS custom_count
             $sql ORDER BY o.created_at DESC, o.id DESC LIMIT " . self::PER_PAGE . " OFFSET $offset", $params);

        return compact('items', 'total', 'page', 'pages');
    }

    /** Every matching order with its items, for the Excel export. */
    public static function exportRows(array $f): array
    {
        [$sql, $params] = self::where($f);
        $orders = Database::all("SELECT o.* $sql ORDER BY o.created_at DESC, o.id DESC LIMIT " . self::EXPORT_LIMIT, $params);
        if (!$orders) return [];
        $ids = implode(',', array_map('intval', array_column($orders, 'id')));
        $items = Database::all("SELECT * FROM order_items WHERE order_id IN ($ids) ORDER BY id");
        $byOrder = [];
        foreach ($items as $it) $byOrder[$it['order_id']][] = $it;
        foreach ($orders as &$o) $o['items'] = $byOrder[$o['id']] ?? [];
        return $orders;
    }

    /** Number of orders per status (for filter tabs). */
    public static function statusCounts(): array
    {
        $rows = Database::all('SELECT status, COUNT(*) n FROM orders GROUP BY status');
        $out = array_fill_keys(array_keys(self::STATUSES), 0);
        foreach ($rows as $r) $out[$r['status']] = (int) $r['n'];
        return ['' => array_sum($out)] + $out;
    }

    public static function find(string $code): ?array
    {
        $o = Database::one('SELECT o.*, u.email AS account_email, u.full_name AS account_name FROM orders o
                             LEFT JOIN users u ON u.id = o.user_id AND u.role = \'customer\' WHERE o.order_code = ?', [$code]);
        if (!$o) return null;
        $o['items'] = Database::all(
            'SELECT i.*, p.slug FROM order_items i LEFT JOIN products p ON p.id = i.product_id WHERE i.order_id = ? ORDER BY i.id',
            [$o['id']]);
        $o['events'] = OrderEvents::forOrder((int) $o['id']);
        return $o;
    }

    /** Several orders (for printing), in the given order; unknown codes are skipped. */
    public static function findMany(array $codes): array
    {
        $out = [];
        foreach (array_slice(array_unique($codes), 0, 100) as $code) {
            if (is_string($code) && ($o = self::find($code))) $out[] = $o;
        }
        return $out;
    }

    // ---------------------------------------------------------------- changes

    /**
     * Change order and/or payment status, logging each change on the timeline.
     * Cancelling returns ready-made stock (in a transaction); a cancelled order is final.
     * $byCustomer: the customer cancels from their account. Only allowed while the order is still
     * pending (checked under the row lock); events are attributed to the customer, not to staff.
     */
    public static function update(string $code, array $in, bool $byCustomer = false): array
    {
        $actor = $byCustomer ? OrderEvents::CUSTOMER : null;
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $o = Database::one('SELECT * FROM orders WHERE order_code = ? FOR UPDATE', [$code]);
            if (!$o) throw HttpException::notFound('Không tìm thấy đơn hàng');

            $status = $in['status'] ?? $o['status'];
            $payment = $in['payment_status'] ?? $o['payment_status'];
            if (!isset(self::STATUSES[$status])) throw HttpException::badRequest('Trạng thái không hợp lệ');
            if (!isset(self::PAYMENT_STATUSES[$payment])) throw HttpException::badRequest('Trạng thái thanh toán không hợp lệ');
            if ($o['status'] === 'cancelled' && $status !== 'cancelled') {
                throw HttpException::badRequest('Đơn đã huỷ không thể mở lại');
            }
            if ($byCustomer && ($o['status'] !== 'pending' || $status !== 'cancelled' || $payment !== $o['payment_status'])) {
                throw HttpException::badRequest(t('account.orders.cannotCancel'));
            }

            if ($status !== $o['status']) {
                OrderEvents::log((int) $o['id'], 'status', ($byCustomer ? 'Khách tự huỷ đơn: ' : '')
                    . self::STATUSES[$o['status']][0] . ' → ' . self::STATUSES[$status][0], $actor);
            }
            if ($payment !== $o['payment_status']) {
                OrderEvents::log((int) $o['id'], 'payment',
                    self::PAYMENT_STATUSES[$o['payment_status']][0] . ' → ' . self::PAYMENT_STATUSES[$payment][0]);
            }

            if ($status === 'cancelled' && $o['status'] !== 'cancelled') {
                // put ready-made items back in stock (custom-made lines never took stock)
                $items = Database::all("SELECT variant_id, quantity FROM order_items
                                        WHERE order_id = ? AND size <> 'CUSTOM' AND variant_id IS NOT NULL", [$o['id']]);
                $inc = $pdo->prepare('UPDATE product_variants SET stock = stock + ? WHERE id = ?');
                $restored = 0;
                foreach ($items as $it) {
                    $inc->execute([$it['quantity'], $it['variant_id']]);
                    if ($inc->rowCount()) Inventory::record((int) $it['variant_id'], (int) $it['quantity'], 'cancel', null, (int) $o['id'], $actor);
                    $restored += (int) $it['quantity'];
                }
                if ($restored) OrderEvents::log((int) $o['id'], 'stock', "Hoàn lại $restored sản phẩm vào kho", $actor);
                if ($o['coupon_code']) {
                    Coupons::release($o['coupon_code']);   // the customer may use the code again
                    OrderEvents::log((int) $o['id'], 'status', "Trả lại 1 lượt dùng mã {$o['coupon_code']}", $actor);
                }
            }

            Database::exec('UPDATE orders SET status = ?, payment_status = ? WHERE id = ?', [$status, $payment, $o['id']]);
            $pdo->commit();
            if ($byCustomer) return ['status' => $status, 'payment_status' => $payment];   // not a staff action
            if ($status !== $o['status']) Activity::log('order.status', "$code: " . self::STATUSES[$o['status']][0] . ' → ' . self::STATUSES[$status][0], 'order', $code);
            if ($payment !== $o['payment_status']) Activity::log('order.payment', "$code: " . self::PAYMENT_STATUSES[$payment][0], 'order', $code);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return ['status' => $status, 'payment_status' => $payment];
    }

    /**
     * Apply one status to many orders. Each order is its own transaction, so one failure
     * (e.g. re-opening a cancelled order) doesn't block the rest.
     * @return array{updated: int, skipped: list<string>}
     */
    public static function bulkUpdate(array $codes, string $status): array
    {
        if (!isset(self::STATUSES[$status])) throw HttpException::badRequest('Trạng thái không hợp lệ');
        $updated = 0; $skipped = [];
        foreach (array_slice(array_unique($codes), 0, 200) as $code) {
            if (!is_string($code)) continue;
            try {
                $before = Database::one('SELECT status FROM orders WHERE order_code = ?', [$code]);
                if (!$before) { $skipped[] = "$code: không tồn tại"; continue; }
                if ($before['status'] === $status) continue;
                self::update($code, ['status' => $status]);
                $updated++;
            } catch (HttpException $e) {
                $skipped[] = "$code: " . $e->getMessage();
            }
        }
        return ['updated' => $updated, 'skipped' => $skipped];
    }

    public static function addNote(string $code, string $text): void
    {
        $o = Database::one('SELECT id FROM orders WHERE order_code = ?', [$code]);
        if (!$o) throw HttpException::notFound('Không tìm thấy đơn hàng');
        $text = trim($text);
        if ($text === '') throw HttpException::badRequest('Nhập nội dung ghi chú', ['note' => 'Nhập nội dung ghi chú']);
        OrderEvents::log((int) $o['id'], 'note', mb_substr($text, 0, 1000));
        Activity::log('order.note', "$code: " . mb_substr($text, 0, 200), 'order', $code);
    }

    /** Edit recipient / address (not allowed once completed or cancelled). Logs what changed. */
    public static function updateCustomer(string $code, array $in): void
    {
        $o = Database::one('SELECT * FROM orders WHERE order_code = ?', [$code]);
        if (!$o) throw HttpException::notFound('Không tìm thấy đơn hàng');
        if (in_array($o['status'], ['completed', 'cancelled'], true)) {
            throw HttpException::badRequest('Không thể sửa đơn đã hoàn thành hoặc đã huỷ');
        }

        $d = [
            'customer_name'    => mb_substr(trim((string) ($in['customer_name'] ?? '')), 0, 120),
            'phone'            => preg_replace('/[\s.\-()]/', '', (string) ($in['phone'] ?? '')),
            'email'            => mb_substr(trim((string) ($in['email'] ?? '')), 0, 190),
            'shipping_address' => mb_substr(trim((string) ($in['shipping_address'] ?? '')), 0, 255),
        ];
        $e = [];
        if (mb_strlen($d['customer_name']) < 2) $e['customer_name'] = 'Nhập họ tên';
        if (!preg_match('/^(0|\+84)(3|5|7|8|9)\d{8}$/', $d['phone'])) $e['phone'] = 'Số điện thoại không hợp lệ';
        if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $e['email'] = 'Email không hợp lệ';
        if (mb_strlen($d['shipping_address']) < 8) $e['shipping_address'] = 'Nhập địa chỉ đầy đủ';
        if ($e) throw HttpException::badRequest('Vui lòng kiểm tra lại', $e);

        $labels = ['customer_name' => 'Tên', 'phone' => 'SĐT', 'email' => 'Email', 'shipping_address' => 'Địa chỉ'];
        $changes = [];
        foreach ($d as $k => $v) {
            if ((string) $o[$k] !== $v) $changes[] = $labels[$k] . ': “' . ($o[$k] ?? '') . '” → “' . $v . '”';
        }
        if (!$changes) return;

        Database::exec('UPDATE orders SET customer_name = ?, phone = ?, email = ?, shipping_address = ? WHERE id = ?',
                       [$d['customer_name'], $d['phone'], $d['email'] ?: null, $d['shipping_address'], $o['id']]);
        OrderEvents::log((int) $o['id'], 'shipping', implode('; ', $changes));
        Activity::log('order.customer', "$code: " . implode('; ', $changes), 'order', $code);
    }

    // ---------------------------------------------------------------- notifications

    /** Pending orders for the admin bell: count + the newest few. */
    public static function pendingSummary(int $limit = 6): array
    {
        return [
            'count'     => (int) Database::one("SELECT COUNT(*) n FROM orders WHERE status = 'pending'")['n'],
            'latest_id' => (int) (Database::one('SELECT MAX(id) m FROM orders')['m'] ?? 0),
            'items'     => Database::all("SELECT id, order_code, customer_name, total, created_at FROM orders
                                          WHERE status = 'pending' ORDER BY id DESC LIMIT $limit"),
        ];
    }
}
