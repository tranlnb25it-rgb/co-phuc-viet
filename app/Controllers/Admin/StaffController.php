<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Request, Response, View};
use App\Services\{Activity, StaffService as Staff};
use App\Support\Auth;

final class StaffController
{
    /** GET /admin/nhan-vien */
    public function index(Request $req): void
    {
        View::page('admin/staff/index', ['title' => 'Nhân viên', 'staff' => Staff::list()], 200, 'admin/layout');
    }

    /** POST /admin/api/staff */
    public function store(Request $req): void
    {
        Staff::create($req->body());
        $this->respond('Đã tạo tài khoản');
    }

    /** PUT /admin/api/staff/{id} */
    public function update(Request $req): void
    {
        Staff::update((int) $req->params['id'], $req->body());
        $this->respond('Đã lưu');
    }

    /** POST /admin/api/staff/{id}/password  {password} */
    public function resetPassword(Request $req): void
    {
        Staff::resetPassword((int) $req->params['id'], (string) $req->input('password', ''));
        Response::json(['ok' => true, 'message' => 'Đã đặt mật khẩu mới']);
    }

    /** GET /admin/tai-khoan — own account (any staff member) */
    public function account(Request $req): void
    {
        View::page('admin/account', ['title' => 'Tài khoản của tôi', 'me' => Auth::user()], 200, 'admin/layout');
    }

    /** POST /admin/api/account/password  {current, new, confirm} */
    public function changePassword(Request $req): void
    {
        Staff::changeOwnPassword((string) $req->input('current', ''), (string) $req->input('new', ''), (string) $req->input('confirm', ''));
        Response::json(['ok' => true, 'message' => 'Đã đổi mật khẩu']);
    }

    /** GET /admin/nhat-ky */
    public function activity(Request $req): void
    {
        $f = $this->activityFilters($req);
        View::page('admin/activity/index', ['title' => 'Nhật ký hoạt động', 'filters' => $f, 'result' => Activity::list($f),
                                            'staff' => Staff::list()], 200, 'admin/layout');
    }

    /** GET /admin/api/activity — Ajax rows */
    public function activityRows(Request $req): void
    {
        $f = $this->activityFilters($req);
        Response::json(['html' => View::partial('admin/activity/rows', ['result' => Activity::list($f), 'filters' => $f])]);
    }

    private function respond(string $message): never
    {
        Response::json(['ok' => true, 'message' => $message, 'html' => View::partial('admin/staff/rows', ['staff' => Staff::list()])]);
    }

    private function activityFilters(Request $req): array
    {
        $date = fn($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
        return [
            'q'     => mb_substr(trim((string) $req->query('q', '')), 0, 60),
            'user'  => (int) $req->query('user', 0) ?: '',
            'group' => (string) $req->query('group', ''),
            'from'  => $date($req->query('from')),
            'to'    => $date($req->query('to')),
            'page'  => (int) $req->query('page', 1),
        ];
    }
}
