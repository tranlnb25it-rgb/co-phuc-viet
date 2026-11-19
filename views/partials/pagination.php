<?php
/** @var array $search @var array $filters */
use App\Support\ProductFilters;

$page  = (int) $search['page'];
$pages = (int) $search['pages'];
if ($pages <= 1) return;

// 1 … current-1 current current+1 … last
$numbers = array_unique(array_filter(
    [1, $page - 1, $page, $page + 1, $pages],
    fn($n) => $n >= 1 && $n <= $pages));
sort($numbers);

$link = fn(int $n) => e(ProductFilters::url($filters, ['page' => $n]));
$base = 'inline-flex h-10 min-w-10 items-center justify-center border px-3 text-sm transition';
?>
<nav class="mt-14 flex items-center justify-center gap-1.5" aria-label="Pagination">
  <?php if ($page > 1): ?>
    <a href="<?= $link($page - 1) ?>" data-ajax-link rel="prev" class="<?= $base ?> border-line hover:border-gold hover:text-crimson" aria-label="<?= e(t('catalog.prev')) ?>">
      <?= icon('chevron-right', 'h-4 w-4 rotate-180') ?>
    </a>
  <?php endif; ?>

  <?php $prev = 0; foreach ($numbers as $n): ?>
    <?php if ($n - $prev > 1): ?><span class="px-1 text-ink-soft">…</span><?php endif; ?>
    <?php if ($n === $page): ?>
      <span class="<?= $base ?> border-crimson bg-crimson text-ivory" aria-current="page"><?= $n ?></span>
    <?php else: ?>
      <a href="<?= $link($n) ?>" data-ajax-link class="<?= $base ?> border-line hover:border-gold hover:text-crimson" aria-label="<?= e(t('catalog.page', ['page' => $n])) ?>"><?= $n ?></a>
    <?php endif; ?>
  <?php $prev = $n; endforeach; ?>

  <?php if ($page < $pages): ?>
    <a href="<?= $link($page + 1) ?>" data-ajax-link rel="next" class="<?= $base ?> border-line hover:border-gold hover:text-crimson" aria-label="<?= e(t('catalog.next')) ?>">
      <?= icon('chevron-right', 'h-4 w-4') ?>
    </a>
  <?php endif; ?>
</nav>
