<?php
declare(strict_types=1);

namespace App\Support;

use App\Core\{Database, HttpException, Request, Response};
use App\Services\Activity;

/**
 * Session-based staff authentication + role permissions.
 * The session only stores the user id; role and active flag are re-read from the database
 * on every request, so deactivating a staff member or changing a role takes effect immediately.
 */
final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 300;

    public const ROLES = [
        'admin'     => 'Quản trị viên',
        'sales'     => 'Nhân viên bán hàng',
        'warehouse' => 'Nhân viên kho',
    ];

    /** permission => roles allowed */
    public const PERMISSIONS = [
        'dashboard'     => ['admin', 'sales', 'warehouse'],
        'orders.view'   => ['admin', 'sales', 'warehouse'],
        'orders.manage' => ['admin', 'sales'],
        'customers'     => ['admin', 'sales'],
        'promotions'    => ['admin', 'sales'],
        'reviews'       => ['admin', 'sales'],
        'products'      => ['admin', 'warehouse'],
        'inventory'     => ['admin', 'warehouse'],
        'catalog'       => ['admin'],
        'settings'      => ['admin'],
        'staff'         => ['admin'],
        'activity'      => ['admin'],
    ];

    private static ?array $user = null;
    private static bool $loaded = false;

    /** Logged-in staff member (id, name, email, role) or null. */
    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = (int) ($_SESSION['admin']['id'] ?? 0);
            if ($id) {
                $u = Database::one("SELECT id, full_name, email, role FROM users
                                    WHERE id = ? AND is_active = 1 AND role IN ('admin','sales','warehouse')", [$id]);
                if ($u) self::$user = ['id' => (int) $u['id'], 'name' => $u['full_name'], 'email' => $u['email'], 'role' => $u['role']];
                else unset($_SESSION['admin']);   // deactivated or demoted since login
            }
        }
        return self::$user;
    }

    public static function can(string $permission): bool
    {
        $u = self::user();
        return $u !== null && in_array($u['role'], self::PERMISSIONS[$permission] ?? [], true);
    }

    /** Seconds left before another login attempt is allowed (0 = not locked). */
    public static function lockedFor(): int
    {
        return max(0, (int) ($_SESSION['login_lock_until'] ?? 0) - time());
    }

    public static function attempt(string $email, string $password): bool
    {
        if (self::lockedFor() > 0) return false;

        $u = Database::one("SELECT id, full_name, email, role, password_hash FROM users
                            WHERE email = ? AND is_active = 1 AND role IN ('admin','sales','warehouse')",
                           [mb_strtolower(trim($email))]);
        // verify against a dummy hash when the user doesn't exist, so timing doesn't reveal valid emails
        $hash = $u['password_hash'] ?? '$2y$10$3Pqd2.kaWnLPVTDGH/2Z.ORSXOx34LmJfrn07nkw2FJfuz8wcGYdq';
        if (!password_verify($password, $hash) || !$u) {
            $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
            if ($_SESSION['login_fails'] >= self::MAX_ATTEMPTS) {
                $_SESSION['login_lock_until'] = time() + self::LOCK_SECONDS;
                $_SESSION['login_fails'] = 0;
            }
            return false;
        }

        session_regenerate_id(true);   // prevent session fixation
        unset($_SESSION['login_fails'], $_SESSION['login_lock_until']);
        $_SESSION['admin'] = ['id' => (int) $u['id']];
        self::$loaded = false;
        Database::exec('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$u['id']]);
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            Database::exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $u['id']]);
        }
        Activity::log('auth.login', 'Đăng nhập', 'user', $u['id'], self::user());
        return true;
    }

    public static function logout(): void
    {
        if ($u = self::user()) Activity::log('auth.logout', 'Đăng xuất', 'user', $u['id'], $u);
        unset($_SESSION['admin']);
        self::$user = null;
        session_regenerate_id(true);
    }

    /** Route middleware: any staff member. 401 JSON for Ajax, redirect to the login page otherwise. */
    public static function requireAdmin(Request $req): void
    {
        if (self::user()) return;
        if ($req->isAjax()) throw new HttpException(401, 'Phiên đăng nhập đã hết. Vui lòng đăng nhập lại.');
        Response::redirect(url('/admin/dang-nhap', ['next' => $req->path]));
    }

    /** Route middleware factory: staff member with $permission (403 otherwise). */
    public static function need(string $permission): callable
    {
        return function (Request $req) use ($permission): void {
            self::requireAdmin($req);
            if (!self::can($permission)) {
                throw new HttpException(403, 'Tài khoản của bạn không có quyền truy cập chức năng này.');
            }
        };
    }
}
