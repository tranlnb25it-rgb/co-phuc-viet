<?php
declare(strict_types=1);

use App\Core\{Config, Lang};

/** HTML-escape. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Site URL for a path, e.g. url('/bo-suu-tap', ['category' => 'ao-tac']). */
function url(string $path = '/', array $query = []): string
{
    $base = rtrim((string) Config::get('base_path', ''), '/');
    $qs = $query ? '?' . http_build_query(array_filter($query, fn($v) => $v !== null && $v !== '')) : '';
    return $base . '/' . ltrim($path, '/') . $qs;
}

/** Static asset URL with a cache-busting version from the file's mtime. */
function asset(string $path): string
{
    $file = dirname(__DIR__) . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? filemtime($file) : 0;
    return url('/assets/' . ltrim($path, '/')) . '?v=' . $v;
}

function t(string $key, array $replace = []): string
{
    return Lang::get($key, $replace);
}

function lang(): string
{
    return Lang::current();
}

/** 4500000 → "4.500.000 ₫" */
function money(int|float|string $value): string
{
    return number_format((float) $value, 0, ',', '.') . ' ₫';
}

/** Current request path without the base folder, e.g. "/bo-suu-tap". */
function current_path(): string
{
    $base = rtrim((string) Config::get('base_path', ''), '/');
    $p = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if ($base !== '' && str_starts_with($p, $base)) $p = substr($p, strlen($base));
    return '/' . trim($p, '/');
}

/**
 * Inline SVG icon (paths from Lucide, ISC licence). Inherits currentColor.
 */
function icon(string $name, string $class = 'h-5 w-5'): string
{
    static $paths = [
        'shopping-bag' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        'search'       => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'menu'         => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>',
        'x'            => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'arrow-right'  => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'chevron-right'=> '<path d="m9 18 6-6-6-6"/>',
        'map-pin'      => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'phone'        => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'mail'         => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'scissors'     => '<circle cx="6" cy="6" r="3"/><path d="M8.12 8.12 12 12"/><path d="M20 4 8.12 15.88"/><circle cx="6" cy="18" r="3"/><path d="M14.8 14.8 20 20"/>',
        'truck'        => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
        'rotate-ccw'   => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>',
        'award'        => '<circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>',
        'book-open'    => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
        'globe'        => '<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/>',
        'loader'       => '<path d="M21 12a9 9 0 1 1-6.219-8.56"/>',
        'trash'        => '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>',
        'minus'        => '<path d="M5 12h14"/>',
        'plus'         => '<path d="M5 12h14"/><path d="M12 5v14"/>',
        'check'        => '<path d="M20 6 9 17l-5-5"/>',
        'lock'         => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'banknote'     => '<rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/>',
        'landmark'     => '<path d="M3 22h18"/><path d="M6 18v-7"/><path d="M10 18v-7"/><path d="M14 18v-7"/><path d="M18 18v-7"/><path d="M12 2 20 7H4z"/>',
        'copy'         => '<rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
        'alert'        => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
        'star'         => '<path d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.6L12 17.3l-5.9 3.3 1.3-6.6-4.9-4.6 6.6-.8z"/>',
        'heart'        => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
        'user'         => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'log-out'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'package'      => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
        'ruler'        => '<path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/>',
        'pencil'       => '<path d="M21.17 6.81a1 1 0 0 0-3.99-3.99L3.84 16.17a2 2 0 0 0-.5.83l-1.32 4.35a.5.5 0 0 0 .62.62l4.35-1.32a2 2 0 0 0 .83-.5z"/><path d="m15 5 4 4"/>',
        'layout'       => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
    ];
    $svg = $paths[$name] ?? '';
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"'
         . ' stroke-linecap="round" stroke-linejoin="round" class="' . e($class) . '" aria-hidden="true">' . $svg . '</svg>';
}

/** Lotus motif (custom). */
function lotus(string $class = 'h-6 w-6'): string
{
    return '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round" class="'
         . e($class) . '" aria-hidden="true"><path d="M32 12c6 7 6 20 0 29-6-9-6-22 0-29z"/>'
         . '<path d="M32 41c-4-9-12-14-21-13 1 9 10 14 21 13z"/><path d="M32 41c4-9 12-14 21-13-1 9-10 14-21 13z"/>'
         . '<path d="M14 49h36"/></svg>';
}

/**
 * Product/category image. URLs of the form "placeholder:<label>" (seed data) render as a
 * labelled silk-toned box until real photos are uploaded.
 */
function product_image(?string $url, string $alt, ?string $tint = null, string $class = ''): string
{
    if ($url && !str_starts_with($url, 'placeholder:')) {
        $src = preg_match('#^(https?:)?//#', $url) ? $url : url($url);
        return '<img src="' . e($src) . '" alt="' . e($alt) . '" loading="lazy" class="h-full w-full object-cover ' . e($class) . '">';
    }
    $tint  = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $tint) ? $tint : '#8B1E1E';
    $label = $url ? substr($url, strlen('placeholder:')) : '';
    return '<div role="img" aria-label="' . e($alt) . '" class="palette-light relative flex h-full w-full flex-col items-center justify-center gap-3 overflow-hidden ' . e($class) . '"'
         . ' style="background:linear-gradient(160deg,' . $tint . '33 0%,' . $tint . '12 55%,#F3EBDD 100%)">'
         . '<div class="absolute inset-3 border border-gold/40"></div>'
         . lotus('h-10 w-10 text-gold/70')
         . '<span class="px-6 text-center font-serif text-lg leading-tight text-ink/75">' . e($alt) . '</span>'
         . ($label !== '' ? '<span class="text-[10px] uppercase tracking-[0.3em] text-ink-soft/70">' . e($label) . '</span>' : '')
         . '</div>';
}

/** Hidden CSRF field for classic (non-Ajax) forms. */
function csrf_field(): string
{
    return App\Core\Csrf::field();
}

/** Bank account shown for transfers (config 'bank', with demo defaults). */
function bank_info(): array
{
    return App\Services\Settings::get('bank');
}

/** "Cao 160cm · Nặng 50kg · …" for custom-measurement lines. */
function measurements_text(?array $m): string
{
    if (!$m) return '';
    $labels = Lang::raw('pdp.custom.fields') ?? [];
    $parts = [];
    foreach (App\Support\Tailoring::FIELDS as $k => [, , $unit]) {
        if (isset($m[$k])) $parts[] = ($labels[$k] ?? $k) . ' ' . rtrim(rtrim(number_format((float) $m[$k], 1, ',', ''), '0'), ',') . $unit;
    }
    return implode(' · ', $parts);
}

/**
 * Illustrative QR-style SVG (NOT a scannable code) generated deterministically from $seed.
 * Placeholder until a real VietQR image is wired to the bank account.
 */
function qr_placeholder(string $seed, string $class = 'h-44 w-44'): string
{
    $n = 25;
    $bits = '';
    for ($i = 0; strlen($bits) < $n * $n; $i++) {
        foreach (str_split(hash('sha256', $seed . $i, true)) as $byte) $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
    }
    $finder = fn($x, $y) => ($x < 7 && $y < 7) || ($x >= $n - 7 && $y < 7) || ($x < 7 && $y >= $n - 7);
    $rects = '';
    for ($y = 0; $y < $n; $y++) {
        for ($x = 0; $x < $n; $x++) {
            if ($finder($x, $y)) continue;
            if ($bits[$y * $n + $x] === '1') $rects .= "<rect x=\"$x\" y=\"$y\" width=\"1\" height=\"1\"/>";
        }
    }
    $eye = fn($x, $y) => "<rect x=\"" . ($x + .5) . "\" y=\"" . ($y + .5) . "\" width=\"6\" height=\"6\" fill=\"none\" stroke=\"currentColor\"/>"
                       . "<rect x=\"" . ($x + 2) . "\" y=\"" . ($y + 2) . "\" width=\"3\" height=\"3\"/>";
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 ' . ($n + 2) . ' ' . ($n + 2) . '" class="' . e($class) . '" fill="currentColor" shape-rendering="crispEdges" role="img" aria-label="QR">'
         . '<rect x="-1" y="-1" width="' . ($n + 2) . '" height="' . ($n + 2) . '" fill="#fff"/>'
         . $eye(0, 0) . $eye($n - 7, 0) . $eye(0, $n - 7) . $rects . '</svg>';
}

/** "Áo Nhật Bình Phượng Hoàng" → "ao-nhat-binh-phuong-hoang" (works without the intl extension). */
function slugify(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $map = [
        'a' => 'àáạảãâầấậẩẫăằắặẳẵ', 'e' => 'èéẹẻẽêềếệểễ', 'i' => 'ìíịỉĩ',
        'o' => 'òóọỏõôồốộổỗơờớợởỡ', 'u' => 'ùúụủũưừứựửữ', 'y' => 'ỳýỵỷỹ', 'd' => 'đ',
    ];
    foreach ($map as $ascii => $chars) {
        $text = preg_replace('/[' . $chars . ']/u', $ascii, $text);
    }
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim(substr($text, 0, 150), '-');
}

/** Compact VND for chart axes and tiles: 950 → "950", 12 500 → "12,5k", 2 500 000 → "2,5 tr", 1.2e9 → "1,2 tỷ". */
function money_compact(int|float $v): string
{
    $fmt = fn(float $n, string $unit) => rtrim(rtrim(number_format($n, 1, ',', '.'), '0'), ',') . $unit;
    $a = abs($v);
    return match (true) {
        $a >= 1e9 => $fmt($v / 1e9, ' tỷ'),
        $a >= 1e6 => $fmt($v / 1e6, ' tr'),
        $a >= 1e3 => $fmt($v / 1e3, 'k'),
        default   => (string) (int) $v,
    };
}

/** "Nice" axis ticks from 0 to ≥ $max, about $count steps (1/2/5 × 10^n). @return list<int|float> */
function nice_ticks(float $max, int $count = 4): array
{
    if ($max <= 0) return [0, 1];
    $raw = $max / $count;
    $mag = 10 ** floor(log10($raw));
    $step = $mag * (($raw / $mag) <= 1 ? 1 : (($raw / $mag) <= 2 ? 2 : (($raw / $mag) <= 5 ? 5 : 10)));
    $ticks = [];
    for ($t = 0; $t < $max + $step * 0.999; $t += $step) $ticks[] = $t;
    return $ticks;
}

/** Shop details for invoices (config 'store', with defaults). */
function store_info(): array
{
    return App\Services\Settings::get('store');
}

/** Stock level at or below which a variant counts as "low" (setting, default 3). */
function low_stock(): int
{
    return (int) App\Services\Settings::value('inventory', 'low_stock');
}

/**
 * Five stars filled to $rating (fractions allowed, e.g. 4.5), gold on a pale track.
 * The number is announced to screen readers via $label (defaults to "4.5/5").
 */
function stars(float $rating, string $class = 'h-4 w-4', ?string $label = null): string
{
    $pct = max(0, min(100, $rating / 5 * 100));
    $star = '<path d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.6L12 17.3l-5.9 3.3 1.3-6.6-4.9-4.6 6.6-.8z"/>';
    $row = fn(string $cls) => '<span class="flex shrink-0 ' . $cls . '">' . str_repeat(
        '<svg viewBox="0 0 24 24" fill="currentColor" class="' . e($class) . ' shrink-0" aria-hidden="true">' . $star . '</svg>', 5) . '</span>';
    $label ??= rtrim(rtrim(number_format($rating, 1, ',', ''), '0'), ',') . '/5';
    return '<span class="relative inline-flex align-middle" role="img" aria-label="' . e($label) . '">'
         . $row('text-line')
         . '<span class="absolute inset-y-0 left-0 overflow-hidden" style="width:' . round($pct, 1) . '%">' . $row('text-gold') . '</span>'
         . '</span>';
}

/**
 * Lý-dynasty motif — a dragon coiled in a bodhi leaf (lá đề). Same drawing as assets/img/motif/ly-dragon.svg
 * (the faint page pattern), but full strength and in currentColor for dividers and ornaments.
 */
function ly_dragon(string $class = 'h-8 w-8'): string
{
    static $svg = null;
    if ($svg === null) {
        $raw = (string) file_get_contents(dirname(__DIR__) . '/assets/img/motif/ly-dragon.svg');
        $raw = preg_replace('/<!--.*?-->/s', '', $raw);
        // keep only the leaf + dragon (viewBox crop), draw in currentColor at full strength
        $raw = preg_replace(['/stroke="#5A3420" stroke-opacity="[\d.]+"/', '/fill="#5A3420" fill-opacity="[\d.]+"/'],
                            ['stroke="currentColor"', 'fill="currentColor" fill-opacity="0.18"'], $raw);
        $svg = preg_replace('/viewBox="[^"]*"/', 'viewBox="50 10 100 150"', $raw, 1);
    }
    return preg_replace('/^<svg /', '<svg class="' . e($class) . '" aria-hidden="true" ', $svg, 1);
}
