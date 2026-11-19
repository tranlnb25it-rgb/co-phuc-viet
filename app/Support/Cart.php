<?php
declare(strict_types=1);

namespace App\Support;

use App\Core\HttpException;
use App\Repositories\ProductRepository;
use App\Services\Coupons;

/**
 * Session cart. Only the customer's choices are stored (slug, fabric, colour, size, measurements, qty);
 * prices and stock are recomputed with ProductQuote every time, so the cart never shows stale prices.
 */
final class Cart
{

    /** @return array<string, array{slug:string, fabric:?string, color:?string, size:string, measurements:?array, qty:int}> */
    private static function &lines(): array
    {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
        return $_SESSION['cart'];
    }

    public static function count(): int
    {
        return array_sum(array_column(self::lines(), 'qty'));
    }

    public static function clear(): void
    {
        $_SESSION['cart'] = [];
        unset($_SESSION['coupon']);
    }

    /** Apply a coupon to the current cart. @throws HttpException 422 {coupon} */
    public static function applyCoupon(string $code, string $l): void
    {
        $code = Coupons::normalize($code);
        if ($code === '') throw HttpException::badRequest(t('coupon.errors.empty'), ['coupon' => t('coupon.errors.empty')]);
        $c = Coupons::findByCode($code);
        if (!$c) throw HttpException::badRequest(t('coupon.errors.invalid'), ['coupon' => t('coupon.errors.invalid')]);
        $r = Coupons::evaluate($c, self::summary($l)['subtotal']);
        if (!$r['ok']) throw HttpException::badRequest($r['error'], ['coupon' => $r['error']]);
        $_SESSION['coupon'] = $c['code'];
    }

    public static function removeCoupon(): void
    {
        unset($_SESSION['coupon']);
    }

    /**
     * Validate a selection and add it. Same product + options (+ same measurements) merge into one line.
     * @throws HttpException 422 with field errors (measurements, _stock)
     */
    public static function add(array $product, array $input): void
    {
        $quote = ProductQuote::quote($product, $input);
        $sel = $quote['selection'];
        $custom = $sel['size'] === Tailoring::CUSTOM;

        $measurements = null;
        if ($custom) {
            [$measurements, $errors] = Tailoring::validate((array) ($input['measurements'] ?? []));
            if ($errors) throw HttpException::badRequest(t('pdp.errors.fix'), $errors);
        }
        if ($quote['variant_id'] === null) {
            throw HttpException::badRequest(t('pdp.errors.fix'), ['_stock' => t('pdp.stock.out')]);
        }

        $key = substr(sha1(json_encode([$product['slug'], $sel['fabric'], $sel['color'], $sel['size'], $measurements])), 0, 12);
        $lines = &self::lines();
        $qty = ($lines[$key]['qty'] ?? 0) + $sel['qty'];

        if (!$custom && $qty > (int) $quote['stock']) {
            throw HttpException::badRequest(t('pdp.errors.fix'), [
                '_stock' => (int) $quote['stock'] > 0 ? t('cart.onlyLeft', ['count' => $quote['stock']]) : t('pdp.stock.out'),
            ]);
        }

        $lines[$key] = [
            'slug'         => $product['slug'],
            'fabric'       => $sel['fabric'],
            'color'        => $sel['color'],
            'size'         => $sel['size'],
            'measurements' => $measurements,
            'qty'          => min(ProductQuote::MAX_QTY, $qty),
        ];
    }

    /** Set quantity (0 removes). Clamped to what's available. */
    public static function update(string $key, int $qty): void
    {
        $lines = &self::lines();
        if (!isset($lines[$key])) throw HttpException::notFound(t('cart.lineNotFound'));
        if ($qty <= 0) { unset($lines[$key]); return; }
        $lines[$key]['qty'] = min(ProductQuote::MAX_QTY, $qty);
        // stock clamp happens in summary() so the customer sees the corrected number
    }

    public static function remove(string $key): void
    {
        $lines = &self::lines();
        unset($lines[$key]);
    }

    /**
     * Priced cart for display and checkout.
     * @return array{lines: list<array>, count:int, subtotal:int, shipping:int, total:int, all_available:bool,
     *               free_shipping_from:int, free_shipping_remaining:int}
     */
    public static function summary(string $l): array
    {
        $lines = &self::lines();
        $out = [];
        foreach ($lines as $key => $line) {
            $product = ProductRepository::findBySlug($line['slug'], $l);
            if (!$product) { unset($lines[$key]); continue; }   // product removed or deactivated

            $custom = $line['size'] === Tailoring::CUSTOM;
            $quote = ProductQuote::quote($product, $line);
            $maxQty = $custom ? ProductQuote::MAX_QTY : min(ProductQuote::MAX_QTY, (int) $quote['stock']);
            if (!$custom && $maxQty > 0 && $line['qty'] > $maxQty) {
                $lines[$key]['qty'] = $line['qty'] = $maxQty;   // stock dropped since it was added
                $quote = ProductQuote::quote($product, $line);
            }

            $front = null;
            foreach ($product['images'] as $img) { if ($img['view_type'] === 'front') { $front = $img['url']; break; } }

            $out[] = [
                'key'          => $key,
                'product_id'   => (int) $product['id'],
                'slug'         => $product['slug'],
                'name'         => $product['name'],
                'url'          => url('/san-pham/' . $product['slug']),
                'image'        => $front,
                'tint'         => $quote['selection']['color'],
                'variant_id'   => $quote['variant_id'],
                'fabric_name'  => $quote['fabric_name'],
                'color_name'   => $quote['color_name'],
                'size'         => $quote['selection']['size'],
                'custom'       => $custom,
                'measurements' => $line['measurements'],
                'qty'          => (int) $line['qty'],
                'max_qty'      => max(1, $maxQty),
                'unit_price'   => $quote['unit_price'],
                'on_sale'      => $quote['on_sale'],
                'original_unit_price' => $quote['original_unit_price'],
                'line_total'   => $quote['unit_price'] * (int) $line['qty'],
                'available'    => $quote['available'],
                'status_text'  => $quote['status_text'],
            ];
        }

        $subtotal = array_sum(array_column($out, 'line_total'));
        ['fee' => $fee, 'free_from' => $freeFrom] = \App\Services\Settings::get('shipping');   // edited in /admin/cai-dat
        $shipping = ($subtotal === 0 || ($freeFrom > 0 && $subtotal >= $freeFrom)) ? 0 : (int) $fee;

        // Coupon (kept in the session; re-checked every time so the customer sees why it stopped applying)
        $coupon = null;
        $discount = 0;
        if ($out && !empty($_SESSION['coupon'])) {
            $c = Coupons::findByCode($_SESSION['coupon']);
            if (!$c) {
                unset($_SESSION['coupon']);
            } else {
                $r = Coupons::evaluate($c, $subtotal);
                $coupon = ['code' => $c['code'], 'description' => $c['description'] ?: Coupons::describe($c),
                           'ok' => $r['ok'], 'error' => $r['error'], 'discount' => $r['discount'], 'free_shipping' => $r['free_shipping']];
                if ($r['ok']) {
                    $discount = $r['discount'];
                    if ($r['free_shipping']) $shipping = 0;
                }
            }
        }
        $savings = array_sum(array_map(fn($l) => ($l['original_unit_price'] - $l['unit_price']) * $l['qty'], $out));

        return [
            'lines'         => $out,
            'count'         => array_sum(array_column($out, 'qty')),
            'subtotal'      => $subtotal,
            'shipping'      => $shipping,
            'discount'      => $discount,
            'coupon'        => $coupon,
            'sale_savings'  => $savings,   // from running sale prices (already inside subtotal)
            'total'         => $subtotal - $discount + $shipping,
            'all_available' => !in_array(false, array_column($out, 'available'), true),
            'free_shipping_from'      => (int) $freeFrom,
            'free_shipping_remaining' => max(0, (int) $freeFrom - $subtotal),
        ];
    }
}
