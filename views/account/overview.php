<?php
/** @var array $stats AccountService::stats() @var array $recent latest orders */
use App\Core\View;

$cards = [
    ['package',  t('account.overview.orders'),   (string) $stats['orders'],  '/tai-khoan/don-hang'],
    ['truck',    t('account.overview.active'),   (string) $stats['active'],  '/tai-khoan/don-hang?tab=processing'],
    ['banknote', t('account.overview.spent'),    money($stats['spent']),     '/tai-khoan/don-hang?tab=completed'],
    ['heart',    t('account.overview.wishlist'), (string) $stats['wishlist'],'/yeu-thich'],
];
?>
<h2 class="sr-only"><?= e(t('account.nav.overview')) ?></h2>
<div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
  <?php foreach ($cards as [$ic, $label, $value, $href]): ?>
    <a href="<?= e(url($href)) ?>" class="group border border-line bg-white/60 p-4 transition hover:border-gold hover:shadow-sm" <?= $href === '/yeu-thich' ? '' : 'data-account-link' ?>>
      <span class="flex items-center gap-2 text-xs uppercase tracking-wider text-ink-soft"><span class="text-crimson"><?= icon($ic, 'h-4 w-4') ?></span><?= e($label) ?></span>
      <span class="mt-2 block font-serif text-3xl font-semibold group-hover:text-crimson"><?= e($value) ?></span>
    </a>
  <?php endforeach; ?>
</div>

<section class="mt-10">
  <div class="flex items-end justify-between gap-4">
    <h2 class="text-2xl"><?= e(t('account.overview.recent')) ?></h2>
    <?php if ($recent): ?><a href="<?= e(url('/tai-khoan/don-hang')) ?>" data-account-link class="text-sm text-crimson hover:underline"><?= e(t('account.overview.viewAll')) ?> →</a><?php endif; ?>
  </div>
  <?php if ($recent): ?>
    <div class="mt-4 space-y-3"><?php foreach ($recent as $o) echo View::partial('account/order-card', ['o' => $o]); ?></div>
  <?php else: ?>
    <div class="mt-4 flex flex-col items-center border border-dashed border-line py-12 text-center">
      <?= lotus('h-10 w-10 text-gold/60') ?>
      <p class="mt-3 text-ink-soft"><?= e(t('account.overview.noOrders')) ?></p>
      <a href="<?= e(url('/bo-suu-tap')) ?>" class="btn-primary mt-5"><?= e(t('account.overview.startShopping')) ?></a>
    </div>
  <?php endif; ?>
</section>

<section class="mt-10">
  <h2 class="text-2xl"><?= e(t('account.overview.shortcuts')) ?></h2>
  <div class="mt-4 grid gap-3 sm:grid-cols-3">
    <a href="<?= e(url('/tai-khoan/dia-chi')) ?>" data-account-link class="flex items-center gap-3 border border-line bg-white/60 p-4 text-sm transition hover:border-gold">
      <span class="rounded-full bg-silk p-2.5 text-crimson"><?= icon('map-pin', 'h-4 w-4') ?></span>
      <span><strong class="block"><?= e(t('account.nav.addresses')) ?></strong><span class="text-xs text-ink-soft"><?= e(t('account.overview.addressCount', ['count' => $stats['addresses']])) ?></span></span>
    </a>
    <a href="<?= e(url('/tai-khoan/so-do')) ?>" data-account-link class="flex items-center gap-3 border border-line bg-white/60 p-4 text-sm transition hover:border-gold">
      <span class="rounded-full bg-silk p-2.5 text-crimson"><?= icon('ruler', 'h-4 w-4') ?></span>
      <span><strong class="block"><?= e(t('account.nav.measurements')) ?></strong><span class="text-xs text-ink-soft"><?= e(t('account.overview.measurementCount', ['count' => $stats['measurements']])) ?></span></span>
    </a>
    <a href="<?= e(url('/tai-khoan/thong-tin')) ?>" data-account-link class="flex items-center gap-3 border border-line bg-white/60 p-4 text-sm transition hover:border-gold">
      <span class="rounded-full bg-silk p-2.5 text-crimson"><?= icon('lock', 'h-4 w-4') ?></span>
      <span><strong class="block"><?= e(t('account.nav.profile')) ?></strong><span class="text-xs text-ink-soft"><?= e(App\Support\Customer::user()['email']) ?></span></span>
    </a>
  </div>
</section>
