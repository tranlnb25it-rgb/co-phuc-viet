<?php
declare(strict_types=1);

namespace App\Support;

use App\Core\{Database, HttpException, Request, Response};

/**
 * Session-based customer accounts (users.role = 'customer'). Independent of the staff login
 * in Auth: the session keys differ, so a shop owner can be signed in to both in one browser.
 * Like Auth, only the id is kept in the session and the row is re-read on every request.
 */
final class Customer
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 300;
    public const MIN_PASSWORD = 8;

    private static ?array $user = null;
    private static bool $loaded = false;

    /** Logged-in customer (id, name, email, phone) or null. */
    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = (int) ($_SESSION['customer']['id'] ?? 0);
            if ($id) {
                $u = Database::one("SELECT id, full_name, email, phone, created_at FROM users
                                    WHERE id = ? AND is_active = 1 AND role = 'customer'", [$id]);
                if ($u) self::$user = ['id' => (int) $u['id'], 'name' => $u['full_name'], 'email' => $u['email'],
                                       'phone' => $u['phone'], 'created_at' => $u['created_at']];
                else unset($_SESSION['customer']);   // account disabled since login
            }
        }
        return self::$user;
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    /** Seconds left before another login attempt is allowed (0 = not locked). */
    public static function lockedFor(): int
    {
        return max(0, (int) ($_SESSION['cust_lock_until'] ?? 0) - time());
    }

    public static function attempt(string $email, string $password): bool
    {
        if (self::lockedFor() > 0) return false;

        $u = Database::one("SELECT id, password_hash FROM users WHERE email = ? AND is_active = 1 AND role = 'customer'",
                           [mb_strtolower(trim($email))]);
        // dummy hash when the email is unknown, so response time doesn't reveal registered emails
        $hash = $u['password_hash'] ?? '$2y$10$3Pqd2.kaWnLPVTDGH/2Z.ORSXOx34LmJfrn07nkw2FJfuz8wcGYdq';
        if (!password_verify($password, $hash) || !$u) {
            $_SESSION['cust_fails'] = ($_SESSION['cust_fails'] ?? 0) + 1;
            if ($_SESSION['cust_fails'] >= self::MAX_ATTEMPTS) {
                $_SESSION['cust_lock_until'] = time() + self::LOCK_SECONDS;
                $_SESSION['cust_fails'] = 0;
            }
            return false;
        }
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            Database::exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $u['id']]);
        }
        unset($_SESSION['cust_fails'], $_SESSION['cust_lock_until']);
        self::login((int) $u['id']);
        return true;
    }

    /**
     * Validate and create an account, then sign in.
     * @return array<string,string> errors by field (empty on success)
     */
    public static function register(array $in): array
    {
        $name  = mb_substr(trim(preg_replace('/\s+/u', ' ', (string) ($in['full_name'] ?? ''))), 0, 120);
        $email = mb_strtolower(trim((string) ($in['email'] ?? '')));
        $phone = preg_replace('/[\s.\-()]/', '', (string) ($in['phone'] ?? ''));
        $pass  = (string) ($in['password'] ?? '');

        $errors = [];
        if (mb_strlen($name) < 2) $errors['full_name'] = t('checkout.errors.name');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) $errors['email'] = t('checkout.errors.email');
        elseif (Database::one('SELECT 1 FROM users WHERE email = ?', [$email])) $errors['email'] = t('auth.errors.emailTaken');
        if ($phone !== '' && !preg_match('/^(0|\+84)(3|5|7|8|9)\d{8}$/', $phone)) $errors['phone'] = t('checkout.errors.phone');
        if (mb_strlen($pass) < self::MIN_PASSWORD) $errors['password'] = t('auth.errors.passwordShort', ['min' => self::MIN_PASSWORD]);
        elseif ($pass !== (string) ($in['password_confirm'] ?? '')) $errors['password_confirm'] = t('auth.errors.passwordMismatch');
        if (empty($in['agree'])) $errors['agree'] = t('auth.errors.agree');
        if ($errors) return $errors;

        try {
            Database::exec("INSERT INTO users (full_name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, 'customer')",
                           [$name, $email, password_hash($pass, PASSWORD_DEFAULT), $phone ?: null]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') return ['email' => t('auth.errors.emailTaken')];   // raced with another signup
            throw $e;
        }
        self::login((int) Database::pdo()->lastInsertId());
        return [];
    }

    /**
     * Start the session for $id: new session id (prevents fixation), then carry over what the
     * visitor did as a guest — orders placed from this browser and the session wishlist.
     */
    public static function login(int $id): void
    {
        session_regenerate_id(true);
        $_SESSION['customer'] = ['id' => $id];
        self::$loaded = false;
        self::$user = null;
        Database::exec('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);

        $codes = array_filter($_SESSION['orders'] ?? [], 'is_string');
        if ($codes) {
            $in = implode(',', array_fill(0, count($codes), '?'));
            Database::exec("UPDATE orders SET user_id = ? WHERE user_id IS NULL AND order_code IN ($in)", [$id, ...$codes]);
        }
        Wishlist::mergeGuest($id);
    }

    public static function logout(): void
    {
        unset($_SESSION['customer'], $_SESSION['wishlist']);
        self::$user = null;
        session_regenerate_id(true);
    }

    /** Route middleware: 401 JSON for Ajax, otherwise redirect to the login page and come back afterwards. */
    public static function required(Request $req): void
    {
        if (self::user()) return;
        if ($req->isAjax()) throw new HttpException(401, t('auth.errors.sessionExpired'));
        Response::redirect(url('/dang-nhap', ['next' => $req->path]));
    }

    /** Only allow redirects back to pages on this site (no //host or scheme). */
    public static function safeNext(mixed $next, string $default = '/tai-khoan'): string
    {
        return is_string($next) && preg_match('#^/(?!/)[a-z0-9\-/]*$#', $next) && !str_starts_with($next, '/admin')
            && !str_starts_with($next, '/api') ? $next : $default;
    }
}
