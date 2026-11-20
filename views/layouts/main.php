<?php /** @var string $content @var string|null $title */ ?>
<!doctype html>
<html lang="<?= e(lang()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e(App\Core\Csrf::token()) ?>">
  <script>document.documentElement.classList.add('js')</script>
  <title><?= isset($title) ? e($title) . ' · ' : '' ?><?= e(t('brand')) ?></title>
  <meta name="description" content="<?= e(t('footer.about')) ?>">
  <link rel="icon" type="image/svg+xml" href="<?= asset('img/lotus.svg') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&display=swap&subset=vietnamese">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="flex min-h-screen flex-col" data-base="<?= e(url('/')) ?>" data-lang="<?= e(lang()) ?>">
  <?= App\Core\View::partial('partials/header') ?>
  <main class="flex-1"><?= $content ?></main>
  <?= App\Core\View::partial('partials/footer') ?>
  <?= App\Core\View::partial('partials/cart-drawer') ?>
  <?php $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); ?>
  <!-- site-wide toast (wishlist, account actions, one-off flash messages) -->
  <div class="pointer-events-none fixed inset-x-4 bottom-6 z-[60] flex justify-center sm:inset-x-auto sm:right-6">
    <p class="<?= $flash ? '' : 'hidden' ?> rounded-sm bg-ink px-4 py-3 text-sm text-ivory shadow-xl" data-site-toast role="status"><?= e($flash ?? '') ?></p>
  </div>
  <script src="<?= asset('js/common.js') ?>" defer></script>
  <script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
