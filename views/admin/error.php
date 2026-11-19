<?php /** @var int $status @var string $message @var string|null $debug */ ?>
<div class="mx-auto max-w-lg py-16 text-center">
  <p class="font-serif text-7xl text-gold"><?= (int) $status ?></p>
  <h1 class="mt-2 text-3xl"><?= e($message) ?></h1>
  <?php if ($status === 403): ?>
    <p class="mt-3 text-sm text-ink-soft">Liên hệ quản trị viên nếu bạn cần quyền này.</p>
  <?php endif; ?>
  <?php if (!empty($debug)): ?>
    <pre class="mt-6 overflow-x-auto whitespace-pre-wrap border border-line bg-silk p-4 text-left text-xs text-ink-soft"><?= e($debug) ?></pre>
  <?php endif; ?>
  <a href="<?= e(url('/admin')) ?>" class="btn-primary mt-8">Về trang tổng quan</a>
</div>
