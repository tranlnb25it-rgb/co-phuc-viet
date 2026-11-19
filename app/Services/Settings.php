<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Config, Database, HttpException};

/**
 * Store settings edited in /admin/cai-dat. Each group is stored as JSON in `settings`.
 * Lookup order: database → config.php → defaults below.
 */
final class Settings
{
    public const DEFAULTS = [
        'store' => [
            'name' => 'Cổ Phục Việt', 'address' => 'Huế, Việt Nam', 'phone' => '0900 000 000',
            'email' => 'lienhe@cophucviet.vn', 'hours' => '8:00 – 21:00 hằng ngày',
        ],
        'bank'      => ['name' => 'Vietcombank — CN Huế', 'account' => '0000 0000 0000', 'holder' => 'CO PHUC VIET'],
        'shipping'  => ['fee' => 30000, 'free_from' => 2000000],
        'inventory' => ['low_stock' => 3],
        'reviews'   => ['auto_approve' => 0],
    ];

    private static array $memo = [];

    public static function get(string $group): array
    {
        if (!isset(self::$memo[$group])) {
            $row = null;
            try {
                $row = Database::one('SELECT value FROM settings WHERE `key` = ?', [$group]);
            } catch (\PDOException) {
                // settings table not migrated yet — fall back to config/defaults
            }
            $db = $row ? (json_decode($row['value'], true) ?: []) : [];
            self::$memo[$group] = $db + (array) Config::get($group, []) + (self::DEFAULTS[$group] ?? []);
        }
        return self::$memo[$group];
    }

    public static function value(string $group, string $key): mixed
    {
        return self::get($group)[$key] ?? null;
    }

    /**
     * Validate and save one group. Returns the changed keys (for the activity log).
     * @throws HttpException 422 with field errors as "group.key"
     */
    public static function save(string $group, array $in): array
    {
        [$clean, $errors] = self::validate($group, $in);
        if ($errors) throw HttpException::badRequest('Vui lòng kiểm tra lại', $errors);

        $before = self::get($group);
        Database::exec('INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)',
                       [$group, json_encode($clean, JSON_UNESCAPED_UNICODE)]);
        unset(self::$memo[$group]);
        return array_keys(array_filter($clean, fn($v, $k) => ($before[$k] ?? null) != $v, ARRAY_FILTER_USE_BOTH));
    }

    private static function validate(string $group, array $in): array
    {
        $str = fn(string $k, int $max) => mb_substr(trim((string) ($in[$k] ?? '')), 0, $max);
        $int = fn(string $k) => (int) preg_replace('/\D/', '', (string) ($in[$k] ?? '0'));
        $e = [];
        switch ($group) {
            case 'store':
                $d = ['name' => $str('name', 80), 'address' => $str('address', 200), 'phone' => $str('phone', 30),
                      'email' => $str('email', 120), 'hours' => $str('hours', 80)];
                if (mb_strlen($d['name']) < 2) $e['store.name'] = 'Nhập tên cửa hàng';
                if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $e['store.email'] = 'Email không hợp lệ';
                if ($d['phone'] === '') $e['store.phone'] = 'Nhập số điện thoại';
                break;
            case 'bank':
                $d = ['name' => $str('name', 100), 'account' => $str('account', 40), 'holder' => mb_strtoupper($str('holder', 80))];
                if ($d['name'] === '') $e['bank.name'] = 'Nhập tên ngân hàng';
                if (!preg_match('/^[0-9 ]{6,40}$/', $d['account'])) $e['bank.account'] = 'Số tài khoản chỉ gồm chữ số';
                if ($d['holder'] === '') $e['bank.holder'] = 'Nhập tên chủ tài khoản';
                break;
            case 'shipping':
                $d = ['fee' => $int('fee'), 'free_from' => $int('free_from')];
                if ($d['fee'] > 1_000_000) $e['shipping.fee'] = 'Phí ship tối đa 1.000.000 ₫';
                if ($d['free_from'] > 100_000_000) $e['shipping.free_from'] = 'Giá trị quá lớn';
                break;
            case 'inventory':
                $d = ['low_stock' => $int('low_stock')];
                if ($d['low_stock'] < 0 || $d['low_stock'] > 1000) $e['inventory.low_stock'] = 'Từ 0 đến 1000';
                break;
            case 'reviews':
                $d = ['auto_approve' => !empty($in['auto_approve']) ? 1 : 0];
                break;
            default:
                throw HttpException::badRequest('Nhóm cài đặt không hợp lệ');
        }
        return [$d, $e];
    }
}
