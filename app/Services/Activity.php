<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Support\Auth;

/** Staff activity log ("who did what"), shown in /admin/nhat-ky. */
final class Activity
{
    /** action prefix => label (filter dropdown) */
    public const GROUPS = [
        'auth'      => 'Đăng nhập',
        'product'   => 'Sản phẩm',
        'inventory' => 'Kho',
        'order'     => 'Đơn hàng',
        'promo'     => 'Khuyến mãi',
        'review'    => 'Đánh giá',
        'catalog'   => 'Danh mục',
        'settings'  => 'Cài đặt',
        'staff'     => 'Nhân viên',
    ];
    public const PER_PAGE = 30;

    public static function log(string $action, string $message, ?string $entity = null, int|string|null $entityId = null, ?array $user = null): void
    {
        $user ??= Auth::user();
        try {
            Database::exec(
                'INSERT INTO activity_log (user_id, user_name, action, entity, entity_id, message, ip) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$user['id'] ?? null, $user['name'] ?? 'Hệ thống', $action, $entity,
                 $entityId === null ? null : (string) $entityId, mb_substr($message, 0, 500), $_SERVER['REMOTE_ADDR'] ?? null]);
        } catch (\PDOException) {
            // never let logging break the actual operation
        }
    }

    public static function list(array $f): array
    {
        $where = ['1 = 1']; $p = [];
        if (!empty($f['user']))  { $where[] = 'a.user_id = :u'; $p['u'] = (int) $f['user']; }
        if (isset(self::GROUPS[$f['group'] ?? ''])) { $where[] = 'a.action LIKE :g'; $p['g'] = $f['group'] . '.%'; }
        if (($f['q'] ?? '') !== '') { $where[] = '(a.message LIKE :q OR a.entity_id LIKE :q2)'; $p['q'] = $p['q2'] = '%' . addcslashes($f['q'], '%_\\') . '%'; }
        if (!empty($f['from'])) { $where[] = 'a.created_at >= :from'; $p['from'] = $f['from'] . ' 00:00:00'; }
        if (!empty($f['to']))   { $where[] = 'a.created_at <= :to';   $p['to']   = $f['to'] . ' 23:59:59'; }
        $sql = 'FROM activity_log a WHERE ' . implode(' AND ', $where);

        $total = (int) Database::one("SELECT COUNT(*) n $sql", $p)['n'];
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min($pages, (int) ($f['page'] ?? 1)));
        $items = Database::all("SELECT a.* $sql ORDER BY a.id DESC LIMIT " . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE), $p);
        return compact('items', 'total', 'page', 'pages');
    }
}
