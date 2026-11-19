<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Request, Response, View};
use App\Services\{Activity, Settings};

final class SettingsController
{
    public const GROUPS = ['store' => 'Thông tin cửa hàng', 'bank' => 'Tài khoản ngân hàng', 'shipping' => 'Vận chuyển', 'inventory' => 'Kho hàng', 'reviews' => 'Đánh giá sản phẩm'];

    /** GET /admin/cai-dat */
    public function index(Request $req): void
    {
        $values = [];
        foreach (array_keys(self::GROUPS) as $g) $values[$g] = Settings::get($g);
        View::page('admin/settings', ['title' => 'Cài đặt', 'values' => $values], 200, 'admin/layout');
    }

    /** PUT /admin/api/settings/{group} */
    public function save(Request $req): void
    {
        $group = $req->params['group'];
        $changed = Settings::save($group, $req->body());
        if ($changed) Activity::log('settings.update', (self::GROUPS[$group] ?? $group) . ': ' . implode(', ', $changed), 'settings', $group);
        Response::json(['ok' => true, 'message' => $changed ? 'Đã lưu ' . mb_strtolower(self::GROUPS[$group]) : 'Không có thay đổi']);
    }
}
