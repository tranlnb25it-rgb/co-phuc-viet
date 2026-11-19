<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Support\Auth;

/** Order timeline: who changed what and when, plus internal staff notes. */
final class OrderEvents
{
    /** type => [label, icon, dot classes] for the timeline. */
    public const TYPES = [
        'created'  => ['Đặt hàng',      'shopping-bag', 'bg-crimson text-ivory'],
        'status'   => ['Trạng thái',    'truck',        'bg-ink text-ivory'],
        'payment'  => ['Thanh toán',    'banknote',     'bg-emerald-700 text-ivory'],
        'shipping' => ['Thông tin giao','map-pin',      'bg-sky-700 text-ivory'],
        'note'     => ['Ghi chú',       'book-open',    'bg-gold text-ink'],
        'stock'    => ['Tồn kho',       'rotate-ccw',   'bg-violet-700 text-ivory'],
    ];

    /** Pass as $userId when the customer (not staff) caused the event. */
    public const CUSTOMER = 0;

    /** Record an event. $userId defaults to the logged-in admin; CUSTOMER stores no user. */
    public static function log(int $orderId, string $type, string $message, ?int $userId = null): void
    {
        $userId = $userId === self::CUSTOMER ? null : ($userId ?? Auth::user()['id'] ?? null);
        Database::exec('INSERT INTO order_events (order_id, user_id, type, message) VALUES (?, ?, ?, ?)',
                       [$orderId, $userId, $type, mb_substr($message, 0, 1000)]);
    }

    /** Newest first, with the admin's name. */
    public static function forOrder(int $orderId): array
    {
        return Database::all(
            'SELECT e.*, u.full_name AS user_name FROM order_events e LEFT JOIN users u ON u.id = e.user_id
              WHERE e.order_id = ? ORDER BY e.created_at DESC, e.id DESC', [$orderId]);
    }
}
