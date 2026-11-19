<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Request, Response, View};
use App\Services\AdminDashboardService as Dashboard;

final class DashboardController
{
    /** GET /admin?range=7|30|90 */
    public function index(Request $req): void
    {
        $data = Dashboard::build((int) $req->query('range', 30));
        View::page('admin/dashboard', ['title' => 'Tổng quan', 'd' => $data], 200, 'admin/layout');
    }

    /** GET /admin/api/dashboard?range= — re-rendered body when the range changes */
    public function body(Request $req): void
    {
        $data = Dashboard::build((int) $req->query('range', 30));
        Response::json(['html' => View::partial('admin/dashboard-body', ['d' => $data]), 'range' => $data['days']]);
    }
}
