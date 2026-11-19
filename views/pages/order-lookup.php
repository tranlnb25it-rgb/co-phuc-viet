<?php
/** @var array $old @var array|null $order @var string|null $error */
use App\Core\View;
use App\Support\Customer;

$in = 'input mt-1.5 aria-[invalid=true]:border-crimson';
?>
<section class="border-b border-line bg-silk/60">
  <div class="container-page py-8">
    <nav aria-label="<?= e(t('catalog.breadcrumb')) ?>" class="flex items-center gap-1.5 text-xs text-ink-soft">
      <a href="<?= e(url('/')) ?>" class="hover:text-crimson"><?= e(t('nav.home')) ?></a>
      <?= icon('chevron-right', 'h-3 w-3') ?>
      <span class="text-ink"><?= e(t('lookup.title')) ?></span>
    </nav>
    <h1 class="mt-3 text-4xl sm:text-5xl"><?= e(t('lookup.title')) ?></h1>
    <p class="mt-1 text-ink-soft"><?= e(t('lookup.intro')) ?></p>
  </div>
</section>

<div class="container-page max-w-5xl py-10">
  <form method="post" action="<?= e(url('/tra-cuu-don-hang')) ?>" class="grid gap-4 border border-line bg-white/70 p-5 sm:grid-cols-[1fr_1fr_auto] sm:items-end sm:p-6"
        data-ajax-form data-target="[data-lookup-result]" data-error-box="#lookup-error" data-scroll-result data-api="<?= e(url('/api/orders/lookup')) ?>" novalidate>
    <?= csrf_field() ?>
    <label class="block text-sm"><span class="font-medium"><?= e(t('lookup.code')) ?></span>
      <input name="code" value="<?= e($old['code'] ?? '') ?>" required maxlength="20" autocomplete="off" spellcheck="false"
             placeholder="<?= e(t('lookup.codePlaceholder')) ?>" class="<?= $in ?> font-mono uppercase"></label>
    <label class="block text-sm"><span class="font-medium"><?= e(t('lookup.phone')) ?></span>
      <input name="phone" type="tel" inputmode="tel" value="<?= e($old['phone'] ?? '') ?>" required maxlength="20" autocomplete="tel"
             placeholder="<?= e(t('checkout.placeholders.phone')) ?>" class="<?= $in ?>"></label>
    <button class="btn-primary h-[42px]" data-label="<?= e(t('lookup.submit')) ?>" data-busy-label="<?= e(t('lookup.searching')) ?>">
      <?= icon('search', 'h-4 w-4') ?> <span><?= e(t('lookup.submit')) ?></span></button>
    <p class="text-xs text-ink-soft sm:col-span-3"><?= e(t('lookup.where')) ?>
      <?php if (!Customer::user()): ?>
        <?= strtr(e(t('lookup.accountCta')), [':link' => '<a href="' . e(url('/dang-nhap', ['next' => '/tai-khoan/don-hang'])) . '" class="text-crimson underline underline-offset-2">' . e(t('nav.login')) . '</a>']) ?>
      <?php endif; ?>
    </p>
  </form>

  <div class="<?= $error ? 'flex' : 'hidden' ?> mt-6 items-center gap-2 border border-crimson/40 bg-crimson/5 p-4 text-sm text-crimson" id="lookup-error" role="alert"><?= e($error ?? '') ?></div>

  <div class="mt-8" data-lookup-result aria-live="polite">
    <?php if ($order): ?><?= View::partial('partials/order-tracking', ['order' => $order, 'public' => true]) ?><?php endif; ?>
  </div>
</div>
