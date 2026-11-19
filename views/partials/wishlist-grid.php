<?php /** @var array $products */ ?>
<?php if ($products): ?>
  <p class="mb-4 text-sm text-ink-soft"><?= e(t('wishlist.count', ['count' => count($products)])) ?></p>
  <div class="grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 lg:grid-cols-3 xl:grid-cols-4">
    <?= App\Core\View::partial('partials/product-grid', ['products' => $products]) ?>
  </div>
<?php else: ?>
  <div class="flex flex-col items-center border border-dashed border-line py-16 text-center">
    <span class="text-gold/70"><?= icon('heart', 'h-12 w-12') ?></span>
    <p class="mt-4 font-serif text-2xl"><?= e(t('wishlist.empty')) ?></p>
    <p class="mt-1 text-sm text-ink-soft"><?= e(t('wishlist.emptyText')) ?></p>
    <a href="<?= e(url('/bo-suu-tap')) ?>" class="btn-primary mt-6"><?= e(t('cart.browse')) ?></a>
  </div>
<?php endif; ?>
