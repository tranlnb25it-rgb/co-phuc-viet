<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\{Database, Request, Response};
use App\Repositories\MetaRepository;

final class MetaController
{
    public function health(Request $req): void
    {
        $db = Database::one('SELECT VERSION() AS version, DATABASE() AS name');
        Response::json(['status' => 'ok', 'php' => PHP_VERSION, 'db' => $db]);
    }

    /** Categories (tree) + dynasties + fabrics, in the current language. */
    public function all(Request $req): void
    {
        $l = lang();
        Response::json([
            'categories' => MetaRepository::categoryTree($l),
            'dynasties'  => MetaRepository::dynasties($l),
            'fabrics'    => MetaRepository::fabrics($l),
        ]);
    }
}
