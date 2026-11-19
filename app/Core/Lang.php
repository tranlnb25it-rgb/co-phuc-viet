<?php
declare(strict_types=1);

namespace App\Core;

/** Current UI language (vi | en), stored in a cookie, plus translation lookup. */
final class Lang
{
    public const SUPPORTED = ['vi', 'en'];
    private const COOKIE = 'cpv_lang';

    private static string $current = 'vi';
    private static array $strings = [];

    public static function init(): void
    {
        // ?lang= overrides the cookie for a single request (handy for API calls)
        $lang = $_GET['lang'] ?? $_COOKIE[self::COOKIE] ?? 'vi';
        self::$current = in_array($lang, self::SUPPORTED, true) ? $lang : 'vi';
        self::$strings = require dirname(__DIR__) . '/lang/' . self::$current . '.php';
    }

    public static function current(): string { return self::$current; }

    public static function set(string $lang): void
    {
        if (!in_array($lang, self::SUPPORTED, true)) return;
        setcookie(self::COOKIE, $lang, [
            'expires' => time() + 60 * 60 * 24 * 365,
            'path' => (Config::get('base_path') ?: '') . '/',
            'samesite' => 'Lax',
        ]);
    }

    /** Dot-notation lookup with :placeholder replacement. Returns the key if missing. */
    public static function get(string $key, array $replace = []): string
    {
        $value = self::$strings;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) return $key;
            $value = $value[$part];
        }
        if (!is_string($value)) return $key;
        // strtr matches the longest key first, so :total is not clobbered by :to
        $map = [];
        foreach ($replace as $k => $v) $map[':' . $k] = (string) $v;
        return $map ? strtr($value, $map) : $value;
    }

    /** Raw value (arrays allowed) — for lists of content such as the cultural story timeline. */
    public static function raw(string $key): mixed
    {
        $value = self::$strings;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) return null;
            $value = $value[$part];
        }
        return $value;
    }
}
