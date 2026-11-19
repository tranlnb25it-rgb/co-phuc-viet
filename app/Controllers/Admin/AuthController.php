<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Request, Response, View};
use App\Support\Auth;

final class AuthController
{
    /** GET /admin/dang-nhap */
    public function show(Request $req): void
    {
        if (Auth::user()) Response::redirect(url('/admin'));
        $this->render(['next' => $this->safeNext($req->query('next'))]);
    }

    /** POST /admin/dang-nhap (classic form) */
    public function login(Request $req): void
    {
        $email = (string) $req->input('email', '');
        $next = $this->safeNext($req->input('next'));

        if (Auth::attempt($email, (string) $req->input('password', ''))) {
            Response::redirect(url($next), 303);
        }
        $locked = Auth::lockedFor();
        $this->render([
            'email' => $email,
            'next'  => $next,
            'error' => $locked ? 'Đăng nhập sai quá nhiều lần. Thử lại sau ' . ceil($locked / 60) . ' phút.'
                               : 'Email hoặc mật khẩu không đúng.',
        ], 422);
    }

    /** POST /admin/dang-xuat */
    public function logout(Request $req): void
    {
        Auth::logout();
        Response::redirect(url('/admin/dang-nhap'), 303);
    }

    /** Only allow redirects to admin pages on this site. */
    private function safeNext(mixed $next): string
    {
        return is_string($next) && preg_match('#^/admin(/[a-z0-9\-/]*)?$#', $next) ? $next : '/admin';
    }

    private function render(array $data, int $status = 200): never
    {
        View::page('admin/login', $data + ['title' => 'Đăng nhập quản trị', 'email' => '', 'error' => null], $status, 'admin/blank');
    }
}
