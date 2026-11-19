<?php
/** @var array $products search() rows @var bool $inAccount rendered inside the account frame */
use App\Core\View;
use App\Support\Customer;
?>
<?php if (!$inAccount): ?>
<section class="border-b border-line bg-silk/60">
  <div class="container-page py-8">
    <nav aria-label="<?= e(t('catalog.breadcrumb')) ?>" class="flex items-center gap-1.5 text-xs text-ink-soft">
      <a href="<?= e(url('/')) ?>" class="hover:text-crimson"><?= e(t('nav.home')) ?></a>
      <?= icon('chevron-right', 'h-3 w-3') ?>
      <span class="text-ink"><?= e(t('wishlist.title')) ?></span>
    </nav>
    <h1 class="mt-3 text-4xl sm:text-5xl"><?= e(t('wishlist.title')) ?></h1>
    <p class="mt-1 text-ink-soft"><?= e(t('wishlist.intro')) ?></p>
  </div>
</section>
<div class="container-page py-10">
  <?php if (!Customer::user()): ?>
    <p class="mb-6 flex items-center gap-2 border border-gold/50 bg-gold/10 px-4 py-3 text-sm">
      <?= icon('heart', 'h-4 w-4 shrink-0 text-crimson') ?>
      <span><?= strtr(e(t('wishlist.guestNote')), [':link' => '<a href="' . e(url('/dang-nhap', ['next' => '/yeu-thich'])) . '" class="font-medium text-crimson underline underline-offset-2">' . e(t('wishlist.guestLink')) . '</a>']) ?></span>
    </p>
  <?php endif; ?>
<?php else: ?>
  <h2 class="text-3xl"><?= e(t('wishlist.title')) ?></h2>
  <p class="mt-1 mb-6 text-sm text-ink-soft"><?= e(t('wishlist.intro')) ?></p>
<?php endif; ?>

  <div data-wishlist-page><?= View::partial('partials/wishlist-grid', ['products' => $products]) ?></div>

<?php if (!$inAccount): ?>
</div>
<?php endif; ?>
