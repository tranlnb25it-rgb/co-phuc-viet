<?php
/**
 * Sign in + register side by side. Works as classic forms; app.js submits them over Ajax.
 * @var string $next @var string $focus login|register @var array $login @var array $reg
 * @var string|null $loginError @var array $regErrors
 */
use App\Core\Lang;
use App\Support\Customer;

$in = 'input mt-1.5 aria-[invalid=true]:border-crimson aria-[invalid=true]:ring-2 aria-[invalid=true]:ring-crimson/20';
$regField = function (string $name, string $label, string $type = 'text', string $extra = '') use ($reg, $regErrors, $in): string {
    $err = $regErrors[$name] ?? '';
    return '<label class="block text-sm"><span class="font-medium">' . e($label) . '</span>'
        . '<input type="' . $type . '" name="' . $name . '" ' . ($type !== 'password' ? 'value="' . e($reg[$name] ?? '') . '" ' : '') . $extra
        . ' class="' . $in . '"' . ($err ? ' aria-invalid="true"' : '') . '>'
        . '<span class="mt-1 block min-h-4 text-xs text-crimson" data-error-for="' . $name . '">' . e($err) . '</span></label>';
};
$pwToggle = '<button type="button" data-toggle-password class="absolute inset-y-0 right-0 mt-1.5 px-3 text-xs text-ink-soft hover:text-crimson" data-show="' . e(t('auth.show')) . '" data-hide="' . e(t('auth.hide')) . '">' . e(t('auth.show')) . '</button>';
?>
<section class="border-b border-line bg-silk/60">
  <div class="container-page py-8 text-center">
    <?= lotus('mx-auto h-10 w-10 text-gold') ?>
    <h1 class="mt-2 text-4xl sm:text-5xl"><?= e(t('auth.pageTitle')) ?></h1>
  </div>
</section>

<div class="container-page grid max-w-6xl gap-8 py-10 lg:grid-cols-2 lg:gap-12 lg:py-14">
  <!-- Sign in -->
  <form method="post" action="<?= e(url('/dang-nhap')) ?>" class="self-start border border-line bg-white/70 p-6 shadow-sm sm:p-8" data-ajax-form id="login" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <h2 class="text-3xl"><?= e(t('auth.loginTitle')) ?></h2>
    <p class="mt-1 text-sm text-ink-soft"><?= e(t('auth.loginLead')) ?></p>

    <div class="<?= $loginError ? 'flex' : 'hidden' ?> mt-5 items-start gap-2 border border-crimson/40 bg-crimson/5 p-3 text-sm text-crimson" data-form-error role="alert">
      <?= e($loginError ?? '') ?>
    </div>

    <div class="mt-5 space-y-4">
      <label class="block text-sm"><span class="font-medium"><?= e(t('auth.email')) ?></span>
        <input type="email" name="email" value="<?= e($login['email'] ?? '') ?>" autocomplete="email" required maxlength="190" class="<?= $in ?>" <?= $focus === 'login' ? 'autofocus' : '' ?>></label>
      <label class="block text-sm"><span class="font-medium"><?= e(t('auth.password')) ?></span>
        <span class="relative block"><input type="password" name="password" autocomplete="current-password" required class="<?= $in ?> pr-14"><?= $pwToggle ?></span></label>
    </div>
    <button class="btn-primary mt-6 w-full" data-label="<?= e(t('auth.login')) ?>" data-busy-label="<?= e(t('auth.loggingIn')) ?>"><span><?= e(t('auth.login')) ?></span></button>
    <p class="mt-4 text-xs text-ink-soft"><?= e(t('auth.forgot', ['phone' => store_info()['phone']])) ?></p>

    <div class="mt-8 border-t border-line pt-6">
      <p class="eyebrow"><?= e(t('auth.benefitsTitle')) ?></p>
      <ul class="mt-3 space-y-2.5 text-sm">
        <?php foreach (Lang::raw('auth.benefits') as $b): ?>
          <li class="flex items-center gap-3"><span class="rounded-full bg-silk p-1.5 text-crimson"><?= icon($b['icon'], 'h-4 w-4') ?></span><?= e($b['text']) ?></li>
        <?php endforeach; ?>
      </ul>
      <p class="mt-5 text-xs text-ink-soft">
        <?= strtr(e(t('auth.guestNote')), [':link' => '<a href="' . e(url('/tra-cuu-don-hang')) . '" class="text-crimson underline underline-offset-2">' . e(t('auth.guestLink')) . '</a>']) ?>
      </p>
    </div>
  </form>

  <!-- Register -->
  <form method="post" action="<?= e(url('/dang-ky')) ?>" class="self-start border border-gold/50 bg-silk/50 p-6 sm:p-8" data-ajax-form id="register" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <h2 class="text-3xl"><?= e(t('auth.registerTitle')) ?></h2>
    <p class="mt-1 text-sm text-ink-soft"><?= e(t('auth.registerLead')) ?></p>
    <div class="mt-3 hidden border border-crimson/40 bg-crimson/5 p-3 text-sm text-crimson" data-form-error role="alert"></div>

    <div class="mt-5 grid gap-x-4 sm:grid-cols-2">
      <div class="sm:col-span-2"><?= $regField('full_name', t('auth.fullName'), 'text', 'autocomplete="name" required maxlength="120"' . ($focus === 'register' ? ' autofocus' : '')) ?></div>
      <?= $regField('email', t('auth.email'), 'email', 'autocomplete="email" required maxlength="190"') ?>
      <?= $regField('phone', t('auth.phone'), 'tel', 'autocomplete="tel" inputmode="tel" maxlength="20"') ?>
      <?= $regField('password', t('auth.password') . ' (' . t('auth.passwordHint', ['min' => Customer::MIN_PASSWORD]) . ')', 'password', 'autocomplete="new-password" required') ?>
      <?= $regField('password_confirm', t('auth.passwordConfirm'), 'password', 'autocomplete="new-password" required') ?>
    </div>
    <label class="flex items-start gap-2 text-sm">
      <input type="checkbox" name="agree" value="1" class="mt-0.5 h-4 w-4 accent-crimson" <?= !empty($reg['agree']) ? 'checked' : '' ?>>
      <span><?= e(t('auth.agree')) ?></span>
    </label>
    <span class="mt-1 block min-h-4 text-xs text-crimson" data-error-for="agree"><?= e($regErrors['agree'] ?? '') ?></span>
    <button class="btn-primary mt-4 w-full" data-label="<?= e(t('auth.register')) ?>" data-busy-label="<?= e(t('auth.registering')) ?>"><span><?= e(t('auth.register')) ?></span></button>
  </form>
</div>
