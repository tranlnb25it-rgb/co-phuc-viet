<?php
/** @var array $me */
use App\Support\Auth;

$in = 'input mt-1.5 aria-[invalid=true]:border-crimson aria-[invalid=true]:ring-2 aria-[invalid=true]:ring-crimson/20';
?>
<header>
  <p class="eyebrow">Tài khoản</p>
  <h1 class="mt-1 text-4xl"><?= e($me['name']) ?></h1>
  <p class="mt-1 text-sm text-ink-soft"><?= e($me['email']) ?> · <?= e(Auth::ROLES[$me['role']]) ?></p>
</header>

<form class="mt-6 max-w-md space-y-1 border border-line bg-white/70 p-6" data-password-form="<?= e(url('/admin/api/account/password')) ?>">
  <h2 class="text-2xl">Đổi mật khẩu</h2>
  <label class="block pt-2"><span class="text-sm font-medium">Mật khẩu hiện tại</span>
    <input name="current" type="password" autocomplete="current-password" required class="<?= $in ?>">
    <span class="block min-h-4 text-xs text-crimson" data-error-for="current"></span></label>
  <label class="block"><span class="text-sm font-medium">Mật khẩu mới (tối thiểu 8 ký tự)</span>
    <input name="new" type="password" autocomplete="new-password" required minlength="8" class="<?= $in ?>">
    <span class="block min-h-4 text-xs text-crimson" data-error-for="new"></span></label>
  <label class="block"><span class="text-sm font-medium">Nhập lại mật khẩu mới</span>
    <input name="confirm" type="password" autocomplete="new-password" required class="<?= $in ?>">
    <span class="block min-h-4 text-xs text-crimson" data-error-for="confirm"></span></label>
  <button class="btn-primary mt-3 w-full"><?= icon('lock', 'h-4 w-4') ?> Đổi mật khẩu</button>
</form>
