<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Lang, Request, Response};

final class LangController
{
    /** GET /lang/{vi|en} — set the language cookie and go back to the previous page on this site. */
    public function switch(Request $req): void
    {
        Lang::set($req->params['code']);

        $back = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $ok = $back !== '' && parse_url($back, PHP_URL_HOST) . (parse_url($back, PHP_URL_PORT) ? ':' . parse_url($back, PHP_URL_PORT) : '') === $host;
        Response::redirect($ok ? $back : url('/'));
    }
}
