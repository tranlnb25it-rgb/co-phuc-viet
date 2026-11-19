<?php
use App\Support\Customer;

$u = Customer::user();
$in = 'input mt-1 aria-[invalid=true]:border-crimson aria-[invalid=true]:ring-2 aria-[invalid=true]:ring-crimson/20';
$err = fn(string $n) => '<span class="mt-1 block min-h-4 text-xs text-crimson" data-error-for="' . $n . '"></span>';
$pw = fn(string $name, string $label, string $ac) => '<label class="block text-sm"><span class="font-medium">' . e($label) . '</span>'
    . '<input type="password" name="' . $name . '" autocomplete="' . $ac . '" class="' . $in . '">' . $err($name) . '</label>';
?>
<div class="grid gap-8 xl:grid-cols-2">
  <form class="border border-line bg-white/60 p-6" data-ajax-form data-url="<?= e(url('/api/account/profile')) ?>" data-method="PUT" data-profile-form novalidate>
    <h2 class="text-2xl"><?= e(t('account.profile.title')) ?></h2>
    <div class="mt-4">
      <label class="block text-sm"><span class="font-medium"><?= e(t('auth.fullName')) ?></span>
        <input name="full_name" value="<?= e($u['name']) ?>" autocomplete="name" maxlength="120" class="<?= $in ?>"><?= $err('full_name') ?></label>
      <label class="block text-sm"><span class="font-medium"><?= e(t('auth.phone')) ?></span>
        <input name="phone" type="tel" inputmode="tel" value="<?= e($u['phone'] ?? '') ?>" autocomplete="tel" maxlength="20" class="<?= $in ?>"><?= $err('phone') ?></label>
      <label class="block text-sm"><span class="font-medium"><?= e(t('auth.email')) ?></span>
        <input value="<?= e($u['email']) ?>" disabled class="input mt-1 cursor-not-allowed opacity-70"></label>
      <p class="mt-1 text-xs text-ink-soft"><?= e(t('account.profile.emailNote')) ?></p>
    </div>
    <p class="mt-3 hidden text-sm text-crimson" data-form-error role="alert"></p>
    <button class="btn-primary mt-5"><?= e(t('account.profile.save')) ?></button>
  </form>

  <form class="border border-line bg-white/60 p-6" data-ajax-form data-url="<?= e(url('/api/account/password')) ?>" data-method="POST" data-reset novalidate>
    <h2 class="text-2xl"><?= e(t('account.profile.passwordTitle')) ?></h2>
    <div class="mt-4">
      <?= $pw('current_password', t('account.profile.current'), 'current-password') ?>
      <?= $pw('new_password', t('account.profile.new'), 'new-password') ?>
      <p class="-mt-3 mb-2 text-xs text-ink-soft"><?= e(t('auth.passwordHint', ['min' => Customer::MIN_PASSWORD])) ?></p>
      <?= $pw('new_password_confirm', t('account.profile.confirm'), 'new-password') ?>
    </div>
    <p class="mt-3 hidden text-sm text-crimson" data-form-error role="alert"></p>
    <button class="btn-primary mt-2"><?= icon('lock', 'h-4 w-4') ?> <?= e(t('account.profile.change')) ?></button>
  </form>
</div>
