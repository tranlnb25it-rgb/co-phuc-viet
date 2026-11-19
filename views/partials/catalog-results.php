<?php
/**
 * Results block of the catalog page. Rendered on page load AND returned by /api/products?format=catalog.
 * Links carry data-ajax-link so JavaScript can load them without a full page reload.
 * @var array $products @var array $search @var array $filters @var array $chips
 */
use App\Core\View;
?>
<?php if ($chips): ?>
  <div class="mb-6 flex flex-wrap items-center gap-2">
    <?php foreach ($chips as $chip): ?>
      <a href="<?= e($chip['url']) ?>" data-ajax-link
         class="group inline-flex items-center gap-1.5 rounded-full border border-gold/50 bg-silk px-3 py-1 text-xs text-ink transition hover:border-crimson hover:text-crimson">
        <?= e($chip['label']) ?>
        <?= icon('x', 'h-3.5 w-3.5 text-ink-soft group-hover:text-crimson') ?>
      </a>
    <?php endforeach; ?>
    <a href="<?= e(url('/bo-suu-tap')) ?>" data-ajax-link class="ml-1 text-xs font-medium text-crimson underline-offset-4 hover:underline">
      <?= e(t('catalog.clearAll')) ?>
    </a>
  </div>
<?php endif; ?>

<?php if (!$products): ?>
  <div class="flex flex-col items-center border border-dashed border-line px-6 py-20 text-center">
    <?= lotus('h-12 w-12 text-gold/60') ?>
    <h2 class="mt-4 text-2xl"><?= e(t('catalog.emptyTitle')) ?></h2>
    <p class="mt-2 max-w-sm text-sm text-ink-soft"><?= e(t('catalog.emptyText')) ?></p>
    <a href="<?= e(url('/bo-suu-tap')) ?>" data-ajax-link class="btn-outline mt-6"><?= e(t('catalog.clearAll')) ?></a>
  </div>
<?php else: ?>
  <div class="grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 xl:grid-cols-3">
    <?= View::partial('partials/product-grid', ['products' => $products]) ?>
  </div>
  <?= View::partial('partials/pagination', ['search' => $search, 'filters' => $filters]) ?>
<?php endif; ?>
