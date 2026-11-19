<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Scheduled sale prices. A sale replaces the product's base price while it runs;
 * fabric surcharges and the tailoring fee are still added on top.
 * The PHP and SQL versions below must agree.
 */
final class Pricing
{
    /** SQL expression: is the sale of products alias `p` running right now? */
    public const SQL_ON_SALE = "(p.sale_price IS NOT NULL AND p.sale_price < p.base_price
        AND (p.sale_starts_at IS NULL OR p.sale_starts_at <= NOW())
        AND (p.sale_ends_at IS NULL OR p.sale_ends_at >= NOW()))";

    /** SQL expression: the price customers pay right now (before fabric / tailoring). */
    public const SQL_PRICE = '(CASE WHEN ' . self::SQL_ON_SALE . ' THEN p.sale_price ELSE p.base_price END)';

    /**
     * @param array $p row with base_price, sale_price, sale_starts_at, sale_ends_at
     * @return array{price:int, original:int, on_sale:bool, percent:int, ends_at:?string, starts_at:?string, scheduled:bool}
     */
    public static function info(array $p): array
    {
        $base = (int) $p['base_price'];
        $sale = isset($p['sale_price']) && $p['sale_price'] !== null ? (int) $p['sale_price'] : null;
        $now = time();
        $starts = !empty($p['sale_starts_at']) ? strtotime($p['sale_starts_at']) : null;
        $ends = !empty($p['sale_ends_at']) ? strtotime($p['sale_ends_at']) : null;

        $valid = $sale !== null && $sale < $base && $sale >= 0;
        $running = $valid && ($starts === null || $starts <= $now) && ($ends === null || $ends >= $now);

        return [
            'price'     => $running ? $sale : $base,
            'original'  => $base,
            'on_sale'   => $running,
            'percent'   => $running && $base > 0 ? (int) round(($base - $sale) / $base * 100) : 0,
            'ends_at'   => $running ? ($p['sale_ends_at'] ?? null) : null,
            'starts_at' => $p['sale_starts_at'] ?? null,
            'scheduled' => $valid && $starts !== null && $starts > $now,   // sale set up for later
        ];
    }
}
