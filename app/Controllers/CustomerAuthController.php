<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{HttpException, Request, Response, View};
use App\Support\Customer;

/**
 * Customer sign-in / registration. Both forms post normally without JS (redirect or page with errors)
 * and as Ajax (JSON {redirect} or 422 with field errors).
 */
final class CustomerAuthController
{
    /** GET /dang-nhap  and  GET /dang-ky (same page, register form focused) */
    public function show(Request $req): void
    {
        $next = Customer::safeNext($req->query('next'));
        if (Customer::user()) Response::redirect(url($next));
        $this->render(['next' => $next, 'focus' => str_ends_with($req->path, '/dang-ky') ? 'register' : 'login']);
    }

    /** POST /dang-nhap */
    public function login(Request $req): void
    {
        $email = trim((string) $req->input('email', ''));
        $next = Customer::safeNext($req->input('next'));

        if (Customer::attempt($email, (string) $req->input('password', ''))) {
            $this->done($req, $next);
        }
        $locked = Customer::lockedFor();
        $error = $locked ? t('auth.errors.locked', ['minutes' => (int) ceil($locked / 60)]) : t('auth.errors.invalid');
        if ($req->isAjax()) throw HttpException::badRequest($error, ['_login' => $error]);
        $this->render(['next' => $next, 'focus' => 'login', 'login' => ['email' => $email], 'loginError' => $error], 422);
    }

    /** POST /dang-ky */
    public function register(Request $req): void
    {
        $in = $req->body();
        $next = Customer::safeNext($in['next'] ?? null);
        $errors = Customer::register($in);
        if (!$errors) {
            $_SESSION['flash'] = t('auth.welcome', ['name' => Customer::user()['name']]);
            $this->done($req, $next);
        }
        if ($req->isAjax()) throw HttpException::badRequest(t('pdp.errors.fix'), $errors);
        unset($in['password'], $in['password_confirm']);
        $this->render(['next' => $next, 'focus' => 'register', 'reg' => $in, 'regErrors' => $errors], 422);
    }

    /** POST /dang-xuat */
    public function logout(Request $req): void
    {
        Customer::logout();
        if ($req->isAjax()) Response::json(['ok' => true, 'redirect' => url('/')]);
        Response::redirect(url('/'), 303);
    }

    private function done(Request $req, string $next): never
    {
        if ($req->isAjax()) Response::json(['ok' => true, 'redirect' => url($next)]);
        Response::redirect(url($next), 303);
    }

    private function render(array $data, int $status = 200): never
    {
        View::page('pages/auth', $data + [
            'title' => t('auth.pageTitle'), 'login' => [], 'reg' => [], 'loginError' => null, 'regErrors' => [],
        ], $status);
    }
}
