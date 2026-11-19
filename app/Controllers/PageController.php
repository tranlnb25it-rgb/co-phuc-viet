<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Request, View};

final class PageController
{
    /** Placeholder for pages built in later steps. */
    public function comingSoon(Request $req): void
    {
        View::page('pages/coming-soon', ['title' => t('common.comingSoon')]);
    }
}
