<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Price + availability for a product selection (fabric × colour × size × quantity).
 * Single source of truth for the product page (initial render) and the Ajax quote endpoint;
 * the cart (step 5) will use it too.
 */
final class ProductQuote
{
    public const MAX_QTY = 10;

    /** Distinct option lists derived from the product's variants. */
    public static function options(array $product): array
    {
        $fabrics = $colors = $sizes = [];
        foreach ($product['variants'] as $v) {
            if ($v['fabric_slug'] !== null && !isset($fabrics[$v['fabric_slug']])) {
                $fabrics[$v['fabric_slug']] = ['slug' => $v['fabric_slug'], 'name' => $v['fabric_name'],
                                               'price_modifier' => (int) $v['price_modifier']];
            }
            $colors[$v['color_hex']] ??= ['hex' => $v['color_hex'], 'name' => $v['color']];
            $sizes[$v['size']] = true;
        }
        $sizes = array_keys($sizes);
        return [
            'fabrics'    => array_values($fabrics),
            'colors'     => array_values($colors),
            'sizes'      => $sizes,
            'free_size'  => $sizes === ['FREE'],
            'custom'     => (bool) $product['allow_custom_tailoring'],
        ];
    }

    /** Default selection: first fabric/colour, size M (or the first size in stock). */
    public static function defaults(array $product): array
    {
        $o = self::options($product);
        $fabric = $o['fabrics'][0]['slug'] ?? null;
        $color = $o['colors'][0]['hex'] ?? null;
        $stock = self::sizeStock($product, $fabric, $color);
        $size = ($stock['M'] ?? 0) > 0 ? 'M' : (array_key_first(array_filter($stock)) ?? ($o['sizes'][0] ?? null));
        return ['fabric' => $fabric, 'color' => $color, 'size' => $size, 'qty' => 1];
    }

    /** Stock per size for a fabric + colour. */
    public static function sizeStock(array $product, ?string $fabric, ?string $color): array
    {
        $out = [];
        foreach ($product['variants'] as $v) {
            if ($v['fabric_slug'] === $fabric && $v['color_hex'] === $color) $out[$v['size']] = (int) $v['stock'];
        }
        return $out;
    }

    public static function quote(array $product, array $sel): array
    {
        $o = self::options($product);
        $d = self::defaults($product);

        // ---- normalise the selection against what actually exists ----
        $fabric = in_array($sel['fabric'] ?? null, array_column($o['fabrics'], 'slug'), true) ? $sel['fabric'] : $d['fabric'];
        $color  = in_array(strtoupper((string) ($sel['color'] ?? '')), array_map('strtoupper', array_column($o['colors'], 'hex')), true)
                    ? self::matchHex($o['colors'], $sel['color']) : $d['color'];
        $size   = $sel['size'] ?? null;
        $custom = $size === Tailoring::CUSTOM && $o['custom'];
        if (!$custom && !in_array($size, $o['sizes'], true)) $size = $d['size'];
        if ($custom) $size = Tailoring::CUSTOM;
        $qty = max(1, min(self::MAX_QTY, (int) ($sel['qty'] ?? 1)));

        $sizeStock = self::sizeStock($product, $fabric, $color);

        // ---- matching variant (custom orders reference any size of the same fabric + colour) ----
        $variant = null;
        foreach ($product['variants'] as $v) {
            if ($v['fabric_slug'] === $fabric && $v['color_hex'] === $color && ($custom || $v['size'] === $size)) {
                $variant = $v;
                break;
            }
        }

        // ---- price ----
        $sale = Pricing::info($product);   // a running sale replaces the base price
        $base = $sale['price'];
        $fabricFee = (int) ($variant['price_modifier'] ?? 0);
        $tailoringFee = $custom ? (int) $product['custom_tailoring_fee'] : 0;
        $unit = $base + $fabricFee + $tailoringFee;
        $originalUnit = $sale['original'] + $fabricFee + $tailoringFee;   // what it would cost without the sale

        // ---- availability ----
        if ($custom) {
            $stock = null;
            $status = 'made_to_order';
            $available = true;
            $statusText = t('pdp.stock.madeToOrder', ['days' => Tailoring::LEAD_TIME_DAYS]);
        } else {
            $stock = (int) ($variant['stock'] ?? 0);
            $available = $variant !== null && $stock >= $qty;
            $status = $stock <= 0 ? 'out' : ($stock <= low_stock() ? 'low' : 'in_stock');
            $statusText = match ($status) {
                'out'   => t('pdp.stock.out'),
                'low'   => t('pdp.stock.low', ['count' => $stock]),
                default => t('pdp.stock.in', ['count' => $stock]),
            };
            if ($stock > 0 && $stock < $qty) $statusText = t('pdp.stock.notEnough', ['count' => $stock]);
        }

        return [
            'selection'  => ['fabric' => $fabric, 'color' => $color, 'size' => $size, 'qty' => $qty],
            'variant_id' => $variant['id'] ?? null,
            'sku'        => $variant['sku'] ?? null,
            'fabric_name'=> $variant['fabric_name'] ?? null,
            'color_name' => $variant['color'] ?? null,
            'breakdown'  => ['base' => $base, 'fabric' => $fabricFee, 'tailoring' => $tailoringFee],
            'unit_price' => $unit,
            'total'      => $unit * $qty,
            'unit_price_text' => money($unit),
            'on_sale'    => $sale['on_sale'],
            'original_unit_price' => $originalUnit,
            'original_unit_price_text' => $sale['on_sale'] ? money($originalUnit) : null,
            'sale_percent' => $sale['percent'],
            'sale_ends_at' => $sale['ends_at'],
            'total_text' => money($unit * $qty),
            'stock'      => $stock,
            'status'     => $status,
            'status_text'=> $statusText,
            'available'  => $available,
            'size_stock' => $sizeStock,
        ];
    }

    private static function matchHex(array $colors, string $hex): string
    {
        foreach ($colors as $c) if (strcasecmp($c['hex'], $hex) === 0) return $c['hex'];
        return $colors[0]['hex'];
    }
}
