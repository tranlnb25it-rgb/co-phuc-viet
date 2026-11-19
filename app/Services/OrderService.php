<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException};
use App\Support\{Cart, Customer};
use PDO;

final class OrderService
{
    public const PAYMENT_METHODS = ['COD', 'BANK_TRANSFER'];

    /**
     * Validate checkout fields.
     * @return array{0: array, 1: array<string,string>} [clean data, errors by field]
     */
    public static function validateCustomer(array $in): array
    {
        $s = fn(string $k, int $max) => mb_substr(trim(preg_replace('/\s+/u', ' ', (string) ($in[$k] ?? ''))), 0, $max);
        $data = [
            'customer_name' => $s('customer_name', 120),
            'phone'         => preg_replace('/[\s.\-()]/', '', (string) ($in['phone'] ?? '')),
            'email'         => $s('email', 190),
            'address'       => $s('address', 180),
            'city'          => $s('city', 70),
            'note'          => mb_substr(trim((string) ($in['note'] ?? '')), 0, 500),
            'payment_method'=> (string) ($in['payment_method'] ?? ''),
        ];

        $errors = [];
        if (mb_strlen($data['customer_name']) < 2) $errors['customer_name'] = t('checkout.errors.name');
        if (!preg_match('/^(0|\+84)(3|5|7|8|9)\d{8}$/', $data['phone'])) $errors['phone'] = t('checkout.errors.phone');
        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = t('checkout.errors.email');
        if (mb_strlen($data['address']) < 8) $errors['address'] = t('checkout.errors.address');
        if (mb_strlen($data['city']) < 2) $errors['city'] = t('checkout.errors.city');
        if (!in_array($data['payment_method'], self::PAYMENT_METHODS, true)) $errors['payment_method'] = t('checkout.errors.payment');

        return [$data, $errors];
    }

    /**
     * Create the order from the session cart in one transaction:
     * lock variant rows, check + decrement stock (ready-made only), insert order + item snapshots, clear cart.
     * @return string order code
     * @throws HttpException 422 when the cart is empty or stock ran out
     */
    public static function place(array $customer): string
    {
        // Snapshot names in Vietnamese — that's what the shop's admin reads.
        $cart = Cart::summary('vi');
        if (!$cart['lines']) throw HttpException::badRequest(t('checkout.errors.emptyCart'));

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT stock FROM product_variants WHERE id = ? FOR UPDATE');
            $dec  = $pdo->prepare('UPDATE product_variants SET stock = stock - ? WHERE id = ?');

            $needed = [];   // a variant may appear in several lines
            foreach ($cart['lines'] as $line) {
                if (!$line['custom']) $needed[$line['variant_id']] = ($needed[$line['variant_id']] ?? 0) + $line['qty'];
            }
            ksort($needed);   // consistent lock order avoids deadlocks between concurrent orders
            foreach ($needed as $variantId => $qty) {
                $lock->execute([$variantId]);
                $stock = $lock->fetchColumn();
                if ($stock === false || (int) $stock < $qty) {
                    $name = current(array_filter($cart['lines'], fn($l) => $l['variant_id'] === $variantId))['name'] ?? '';
                    throw HttpException::badRequest(t('checkout.errors.stock', ['name' => $name]));
                }
                $dec->execute([$qty, $variantId]);
            }

            // Coupon: re-checked under a row lock (incl. per-phone limit) and one use taken
            $discount = 0;
            $couponCode = null;
            if ($cart['coupon']) {
                if (!$cart['coupon']['ok']) throw HttpException::badRequest($cart['coupon']['error'], ['coupon' => $cart['coupon']['error']]);
                $r = Coupons::redeem($cart['coupon']['code'], $cart['subtotal'], $customer['phone']);
                $discount = $r['discount'];
                $couponCode = $r['code'];
            }
            $total = $cart['subtotal'] - $discount + $cart['shipping'];

            $code = self::newOrderCode($pdo);
            $pdo->prepare(
                'INSERT INTO orders (user_id, order_code, customer_name, phone, email, shipping_address, note,
                                     payment_method, subtotal, shipping_fee, coupon_code, discount, total)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                Customer::id(), $code, $customer['customer_name'], $customer['phone'], $customer['email'] ?: null,
                $customer['address'] . ', ' . $customer['city'], $customer['note'] ?: null,
                $customer['payment_method'], $cart['subtotal'], $cart['shipping'], $couponCode, $discount, $total,
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $item = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, variant_id, product_name, size, fabric_name, color,
                                          unit_price, original_price, quantity, custom_measurements)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            foreach ($cart['lines'] as $line) {
                $item->execute([
                    $orderId, $line['product_id'], $line['variant_id'], $line['name'], $line['size'],
                    $line['fabric_name'], $line['color_name'], $line['unit_price'],
                    $line['on_sale'] ? $line['original_unit_price'] : null, $line['qty'],
                    $line['measurements'] ? json_encode($line['measurements'], JSON_UNESCAPED_UNICODE) : null,
                ]);
            }

            foreach ($needed as $variantId => $qty) {
                Inventory::record($variantId, -$qty, 'order', null, $orderId, OrderEvents::CUSTOMER);
            }

            $customCount = count(array_filter($cart['lines'], fn($l) => $l['custom']));
            OrderEvents::log($orderId, 'created', sprintf('Khách đặt hàng — %s%s',
                $customer['payment_method'] === 'COD' ? 'thanh toán khi nhận hàng' : 'chuyển khoản',
                $customCount ? " · $customCount sản phẩm may đo" : '') . ($couponCode ? " · mã $couponCode (−" . money($discount) . ')' : ''), OrderEvents::CUSTOMER);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Cart::clear();
        // Let this browser view its own confirmation page (keep the last 10 orders)
        $_SESSION['orders'] = array_slice(array_merge([$code], $_SESSION['orders'] ?? []), 0, 10);
        return $code;
    }

    /** Order + items, for the confirmation page. */
    public static function find(string $code): ?array
    {
        $order = Database::one('SELECT * FROM orders WHERE order_code = ?', [$code]);
        if (!$order) return null;
        $order['items'] = Database::all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$order['id']]);
        return $order;
    }

    /** e.g. CPV251115-7K3Q */
    private static function newOrderCode(PDO $pdo): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   // no 0/O/1/I to avoid confusion when read aloud
        $check = $pdo->prepare('SELECT 1 FROM orders WHERE order_code = ?');
        do {
            $suffix = '';
            for ($i = 0; $i < 4; $i++) $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            $code = 'CPV' . date('ymd') . '-' . $suffix;
            $check->execute([$code]);
        } while ($check->fetchColumn());
        return $code;
    }
}
