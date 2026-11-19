<?php /** @var string $email @var string $next @var string|null $error */ ?>
<div class="w-full max-w-sm">
  <div class="mb-6 flex flex-col items-center text-gold">
    <?= lotus('h-12 w-12') ?>
    <p class="mt-2 font-serif text-3xl font-bold text-ivory"><?= e(t('brand')) ?></p>
    <p class="text-xs uppercase tracking-[0.3em] text-ivory/60">Quản trị</p>
  </div>
  <form method="post" action="<?= e(url('/admin/dang-nhap')) ?>" class="border-t-4 border-gold bg-ivory p-7 shadow-2xl">
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <h1 class="text-2xl">Đăng nhập</h1>
    <?php if ($error): ?>
      <p class="mt-4 flex items-start gap-2 border border-crimson/30 bg-crimson/5 p-3 text-sm text-crimson" role="alert"><?= icon('alert', 'mt-0.5 h-4 w-4 shrink-0') ?> <?= e($error) ?></p>
    <?php endif; ?>
    <label class="mt-5 block">
      <span class="text-sm font-medium">Email</span>
      <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="username" autofocus class="input mt-1.5">
    </label>
    <label class="mt-4 block">
      <span class="text-sm font-medium">Mật khẩu</span>
      <input type="password" name="password" required autocomplete="current-password" class="input mt-1.5">
    </label>
    <button class="btn-primary mt-6 w-full"><?= icon('lock', 'h-4 w-4') ?> Đăng nhập</button>
  </form>
  <p class="mt-6 text-center text-xs text-ivory/60"><a href="<?= e(url('/')) ?>" class="hover:text-gold">← Về cửa hàng</a></p>
</div>
