<?php
declare(strict_types=1);

namespace App\Support;

use App\Core\{Database, HttpException};

/**
 * Wishlist: stored in the database for signed-in customers and in the session for guests;
 * the guest list is merged into the account at login.
 */
final class Wishlist
{
    public const GUEST_LIMIT = 50;

    private static ?array $ids = null;

    /** Product ids, newest first (cached per request). */
    public static function ids(): array
    {
        if (self::$ids === null) {
            $uid = Customer::id();
            self::$ids = $uid
                ? array_map('intval', array_column(Database::all('SELECT product_id FROM wishlists WHERE user_id = ? ORDER BY created_at DESC', [$uid]), 'product_id'))
                : array_values(array_map('intval', $_SESSION['wishlist'] ?? []));
        }
        return self::$ids;
    }

    public static function has(int $productId): bool
    {
        return in_array($productId, self::ids(), true);
    }

    public static function count(): int
    {
        return count(self::ids());
    }

    /** Add or remove; returns the new state (true = saved). */
    public static function toggle(int $productId): bool
    {
        if (!Database::one('SELECT 1 FROM products WHERE id = ? AND is_active = 1', [$productId])) {
            throw HttpException::notFound(t('errors.productNotFound'));
        }
        $saved = !self::has($productId);
        $uid = Customer::id();
        if ($uid) {
            $saved ? Database::exec('INSERT IGNORE INTO wishlists (user_id, product_id) VALUES (?, ?)', [$uid, $productId])
                   : Database::exec('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?', [$uid, $productId]);
        } else {
            $list = array_values(array_diff(array_map('intval', $_SESSION['wishlist'] ?? []), [$productId]));
            if ($saved) array_unshift($list, $productId);
            $_SESSION['wishlist'] = array_slice($list, 0, self::GUEST_LIMIT);
        }
        self::$ids = null;
        return $saved;
    }

    /** Move the guest (session) list into the account. */
    public static function mergeGuest(int $userId): void
    {
        $ids = array_reverse(array_map('intval', $_SESSION['wishlist'] ?? []));   // oldest first keeps the order
        foreach ($ids as $pid) {
            Database::exec('INSERT IGNORE INTO wishlists (user_id, product_id)
                            SELECT ?, id FROM products WHERE id = ?', [$userId, $pid]);
        }
        unset($_SESSION['wishlist']);
        self::$ids = null;
    }
}
