<?php
/** @var array $p  Row from ProductRepository::search() */
$href = url('/san-pham/' . $p['slug']);
$soldOut = (int) $p['stock'] <= 0 && !$p['allow_custom_tailoring'];
$sale = App\Support\Pricing::info($p);
$saved = App\Support\Wishlist::has((int) $p['id']);
?>
<article class="product-card group relative flex flex-col">
  <a href="<?= e($href) ?>" class="relative block aspect-[3/4] overflow-hidden border border-line bg-silk">
    <!-- front image, cross-fades to the back view on hover -->
    <div class="absolute inset-0 transition duration-700 ease-silk group-hover:scale-105 <?= $p['image_back'] ? 'group-hover:opacity-0' : '' ?>">
      <?= product_image($p['image_front'], $p['name'], $p['tint']) ?>
    </div>
    <?php if ($p['image_back']): ?>
      <div class="absolute inset-0 opacity-0 transition duration-700 ease-silk group-hover:opacity-100">
        <?= product_image($p['image_back'], $p['name'], $p['tint']) ?>
      </div>
    <?php endif; ?>

    <?php if ($sale['on_sale']): ?>
      <span class="absolute right-3 top-14 z-10 flex h-12 w-12 items-center justify-center rounded-full bg-crimson text-xs font-bold text-ivory shadow-md ring-2 ring-gold/70">−<?= $sale['percent'] ?>%</span>
    <?php endif; ?>
    <div class="absolute left-3 top-3 flex flex-col items-start gap-1.5">
      <?php if ($p['dynasty_name']): ?>
        <span class="bg-ivory/90 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-crimson"><?= e($p['dynasty_name']) ?></span>
      <?php endif; ?>
      <?php if ($p['allow_custom_tailoring']): ?>
        <span class="inline-flex items-center gap-1 bg-crimson px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-ivory">
          <?= icon('scissors', 'h-3 w-3') ?> <?= e(t('product.custom')) ?>
        </span>
      <?php endif; ?>
    </div>
    <?php if ($soldOut): ?>
      <span class="absolute inset-x-0 bottom-0 bg-ink/80 py-1.5 text-center text-xs font-medium uppercase tracking-wider text-ivory"><?= e(t('product.outOfStock')) ?></span>
    <?php endif; ?>
  </a>
  <?= App\Core\View::partial('partials/wishlist-button', ['id' => (int) $p['id'], 'saved' => $saved, 'class' => 'absolute right-3 top-3 z-20']) ?>

  <div class="flex flex-1 flex-col pt-4">
    <p class="text-[11px] uppercase tracking-[0.2em] text-ink-soft"><?= e($p['category_name']) ?> · <?= e(t('product.gender.' . $p['gender'])) ?></p>
    <h3 class="mt-1 text-xl leading-snug">
      <a href="<?= e($href) ?>" class="transition-colors hover:text-crimson"><?= e($p['name']) ?></a>
    </h3>
    <?php if ((int) ($p['rating_count'] ?? 0) > 0): ?>
      <p class="mt-1 flex items-center gap-1.5 text-xs text-ink-soft"><?= stars((float) $p['rating_avg'], 'h-3.5 w-3.5') ?> <span><?= e(number_format((float) $p['rating_avg'], 1, ',', '')) ?> (<?= (int) $p['rating_count'] ?>)</span></p>
    <?php endif; ?>
    <p class="mt-auto pt-2 text-sm">
      <span class="text-ink-soft"><?= e(t('product.from')) ?></span>
      <span class="font-semibold text-crimson"><?= money($sale['price']) ?></span>
      <?php if ($sale['on_sale']): ?>
        <span class="ml-1 text-xs text-ink-soft line-through"><?= money($sale['original']) ?></span>
      <?php endif; ?>
    </p>
  </div>
</article>
