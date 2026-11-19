<?php
/** @var array $addresses */
use App\Core\{Lang, View};

$f = Lang::raw('account.addresses.fields');
$in = 'input mt-1 aria-[invalid=true]:border-crimson aria-[invalid=true]:ring-2 aria-[invalid=true]:ring-crimson/20';
$field = fn(string $name, string $extra = '', string $ph = '') => '<label class="block text-sm"><span class="font-medium">' . e($f[$name]) . '</span>'
    . '<input name="' . $name . '" class="' . $in . '" ' . $extra . ($ph ? ' placeholder="' . e($ph) . '"' : '') . '>'
    . '<span class="mt-1 block min-h-4 text-xs text-crimson" data-error-for="' . $name . '"></span></label>';
?>
<div class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h2 class="text-3xl"><?= e(t('account.addresses.title')) ?></h2>
    <p class="mt-1 text-sm text-ink-soft"><?= e(t('account.addresses.intro')) ?></p>
  </div>
  <button type="button" class="btn-primary" data-form-dialog="address-dialog"
          data-url="<?= e(url('/api/account/addresses')) ?>" data-method="POST" data-heading="<?= e(t('account.addresses.add')) ?>">
    <?= icon('plus', 'h-4 w-4') ?> <?= e(t('account.addresses.add')) ?>
  </button>
</div>

<div class="mt-6" data-address-list><?= View::partial('account/address-list', ['addresses' => $addresses]) ?></div>

<dialog id="address-dialog" class="m-auto w-[min(560px,calc(100vw-2rem))] border border-line bg-ivory p-0 text-ink shadow-2xl backdrop:bg-ink/50" aria-labelledby="address-dialog-title">
  <form data-ajax-form data-target="[data-address-list]" novalidate>
    <div class="flex items-center justify-between border-b border-line px-6 py-4">
      <h3 id="address-dialog-title" class="text-2xl" data-dialog-heading><?= e(t('account.addresses.add')) ?></h3>
      <button type="button" data-dialog-close class="rounded-full p-1.5 hover:bg-silk" aria-label="<?= e(t('nav.close')) ?>"><?= icon('x') ?></button>
    </div>
    <div class="grid gap-x-4 px-6 pt-5 sm:grid-cols-2">
      <?= $field('recipient_name', 'autocomplete="name" maxlength="120" required') ?>
      <?= $field('phone', 'type="tel" inputmode="tel" autocomplete="tel" maxlength="20" required') ?>
      <div class="sm:col-span-2"><?= $field('address', 'autocomplete="street-address" maxlength="180" required', t('checkout.placeholders.address')) ?></div>
      <?= $field('city', 'autocomplete="address-level1" maxlength="70" required', t('checkout.placeholders.city')) ?>
      <?= $field('label', 'maxlength="40"', t('account.addresses.labelPlaceholder')) ?>
      <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" name="is_default" value="1" class="h-4 w-4 accent-crimson"> <?= e($f['is_default']) ?></label>
    </div>
    <p class="mx-6 mt-3 hidden text-sm text-crimson" data-form-error role="alert"></p>
    <div class="mt-5 flex justify-end gap-2 border-t border-line px-6 py-4">
      <button type="button" data-dialog-close class="btn border border-line hover:border-gold"><?= e(t('account.addresses.cancel')) ?></button>
      <button class="btn-primary"><?= e(t('account.addresses.save')) ?></button>
    </div>
  </form>
</dialog>
