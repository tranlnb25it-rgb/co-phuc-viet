<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Per-session CSRF token. Ajax sends it in the X-CSRF-Token header (read from <meta name="csrf-token">),
 * classic forms send it as a hidden _token field.
 */
final class Csrf
{
    public static function token(): string
    {
        return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }

    /** Throws 403 unless the request carries the session token (419 is non-standard; Apache turns it into 500). */
    public static function verify(): void
    {
        $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_token'] ?? '';
        if (!is_string($sent) || !hash_equals(self::token(), $sent)) {
            throw new HttpException(403, t('errors.csrf'));
        }
    }
}
