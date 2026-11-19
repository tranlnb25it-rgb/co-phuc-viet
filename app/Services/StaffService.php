<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException};
use App\Support\Auth;

/** Staff accounts (admin / sales / warehouse). There must always be at least one active admin. */
final class StaffService
{
    public static function list(): array
    {
        return Database::all(
            "SELECT u.id, u.full_name, u.email, u.phone, u.role, u.is_active, u.last_login_at, u.created_at,
                    (SELECT COUNT(*) FROM activity_log a WHERE a.user_id = u.id AND a.created_at >= NOW() - INTERVAL 30 DAY) AS actions_30d
               FROM users u WHERE u.role IN ('admin','sales','warehouse')
              ORDER BY u.is_active DESC, FIELD(u.role, 'admin','sales','warehouse'), u.full_name");
    }

    public static function create(array $in): int
    {
        [$d, $e] = self::validate($in, null);
        $pw = (string) ($in['password'] ?? '');
        if (mb_strlen($pw) < 8) $e['password'] = 'Mật khẩu tối thiểu 8 ký tự';
        if ($e) throw HttpException::badRequest('Vui lòng kiểm tra lại', $e);

        Database::exec('INSERT INTO users (full_name, email, phone, role, is_active, password_hash) VALUES (?, ?, ?, ?, 1, ?)',
                       [$d['full_name'], $d['email'], $d['phone'], $d['role'], password_hash($pw, PASSWORD_DEFAULT)]);
        $id = (int) Database::pdo()->lastInsertId();
        Activity::log('staff.create', "Tạo tài khoản {$d['email']} (" . Auth::ROLES[$d['role']] . ')', 'user', $id);
        return $id;
    }

    public static function update(int $id, array $in): void
    {
        $u = self::get($id);
        [$d, $e] = self::validate($in, $id);
        $d['is_active'] = !empty($in['is_active']) ? 1 : 0;
        $me = Auth::user()['id'];

        if ($id === $me && ($d['role'] !== 'admin' || !$d['is_active'])) {
            $e['role'] = 'Bạn không thể tự hạ quyền hoặc khoá chính mình';
        }
        if ($u['role'] === 'admin' && $u['is_active'] && ($d['role'] !== 'admin' || !$d['is_active']) && self::activeAdmins() <= 1) {
            $e['role'] = 'Phải còn ít nhất một quản trị viên đang hoạt động';
        }
        if ($e) throw HttpException::badRequest('Vui lòng kiểm tra lại', $e);

        Database::exec('UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, is_active = ? WHERE id = ?',
                       [$d['full_name'], $d['email'], $d['phone'], $d['role'], $d['is_active'], $id]);
        $changes = [];
        if ($u['role'] !== $d['role']) $changes[] = 'vai trò → ' . Auth::ROLES[$d['role']];
        if ((int) $u['is_active'] !== $d['is_active']) $changes[] = $d['is_active'] ? 'mở khoá' : 'khoá tài khoản';
        Activity::log('staff.update', "Sửa tài khoản {$d['email']}" . ($changes ? ': ' . implode(', ', $changes) : ''), 'user', $id);
    }

    /** Admin sets a new password for someone else. */
    public static function resetPassword(int $id, string $password): void
    {
        $u = self::get($id);
        if (mb_strlen($password) < 8) throw HttpException::badRequest('Mật khẩu tối thiểu 8 ký tự', ['password' => 'Mật khẩu tối thiểu 8 ký tự']);
        Database::exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $id]);
        Activity::log('staff.password', "Đặt lại mật khẩu cho {$u['email']}", 'user', $id);
    }

    /** Any staff member changes their own password (must know the current one). */
    public static function changeOwnPassword(string $current, string $new, string $confirm): void
    {
        $me = Auth::user();
        $u = Database::one('SELECT password_hash FROM users WHERE id = ?', [$me['id']]);
        $e = [];
        if (!password_verify($current, $u['password_hash'])) $e['current'] = 'Mật khẩu hiện tại không đúng';
        if (mb_strlen($new) < 8) $e['new'] = 'Mật khẩu mới tối thiểu 8 ký tự';
        elseif ($new === $current) $e['new'] = 'Mật khẩu mới phải khác mật khẩu cũ';
        if ($new !== $confirm) $e['confirm'] = 'Nhập lại chưa khớp';
        if ($e) throw HttpException::badRequest('Vui lòng kiểm tra lại', $e);

        Database::exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $me['id']]);
        session_regenerate_id(true);
        Activity::log('auth.password', 'Đổi mật khẩu của mình', 'user', $me['id']);
    }

    private static function get(int $id): array
    {
        $u = Database::one("SELECT * FROM users WHERE id = ? AND role IN ('admin','sales','warehouse')", [$id]);
        if (!$u) throw HttpException::notFound('Không tìm thấy nhân viên');
        return $u;
    }

    private static function activeAdmins(): int
    {
        return (int) Database::one("SELECT COUNT(*) n FROM users WHERE role = 'admin' AND is_active = 1")['n'];
    }

    private static function validate(array $in, ?int $id): array
    {
        $d = [
            'full_name' => mb_substr(trim((string) ($in['full_name'] ?? '')), 0, 120),
            'email'     => mb_strtolower(trim((string) ($in['email'] ?? ''))),
            'phone'     => preg_replace('/[\s.\-()]/', '', (string) ($in['phone'] ?? '')) ?: null,
            'role'      => (string) ($in['role'] ?? ''),
        ];
        $e = [];
        if (mb_strlen($d['full_name']) < 2) $e['full_name'] = 'Nhập họ tên';
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $e['email'] = 'Email không hợp lệ';
        elseif (Database::one('SELECT id FROM users WHERE email = ? AND id <> ?', [$d['email'], $id ?? 0])) $e['email'] = 'Email đã được dùng';
        if ($d['phone'] !== null && !preg_match('/^(0|\+84)\d{9,10}$/', $d['phone'])) $e['phone'] = 'Số điện thoại không hợp lệ';
        if (!isset(Auth::ROLES[$d['role']])) $e['role'] = 'Chọn vai trò';
        return [$d, $e];
    }
}
