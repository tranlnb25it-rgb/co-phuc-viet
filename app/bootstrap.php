<?php
declare(strict_types=1);

// ---------- Autoload App\* from app/ ----------
spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) require $file;
    }
});

require __DIR__ . '/helpers.php';

App\Core\Config::load(dirname(__DIR__) . '/config/config.php');

// One timezone for PHP and MySQL (Database sets the same offset per connection), whatever php.ini says.
date_default_timezone_set((string) App\Core\Config::get('timezone', 'Asia/Ho_Chi_Minh'));

// ---------- Session (cart, CSRF token, recent orders) ----------
session_name('cpv_sess');
session_set_cookie_params([
    'path'     => (App\Core\Config::get('base_path') ?: '') . '/',
    'httponly' => true,
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();

App\Core\Lang::init();
