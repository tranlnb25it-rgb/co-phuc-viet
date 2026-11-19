<?php
/** Shared <head> for admin pages. Admin pages are never indexed or cached. @var string|null $title */
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(App\Core\Csrf::token()) ?>">
<title><?= isset($title) ? e($title) . ' · ' : '' ?>Quản trị <?= e(t('brand')) ?></title>
<link rel="icon" type="image/svg+xml" href="<?= asset('img/lotus.svg') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@500;600;700&display=swap&subset=vietnamese">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<script>document.documentElement.classList.add('js')</script>
