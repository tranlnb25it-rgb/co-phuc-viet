<?php /** Minimal admin layout (login). @var string $content */ ?>
<!doctype html>
<html lang="vi">
<head><?= App\Core\View::partial('admin/partials/head', ['title' => $title ?? null]) ?></head>
<body class="hero-pattern flex min-h-screen items-center justify-center bg-crimson-dark p-4" data-base="<?= e(url('/')) ?>">
  <?= $content ?>
  <script src="<?= asset('js/common.js') ?>" defer></script>
</body>
</html>
