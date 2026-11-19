<?php
/** @var array $result {page, pages, total} @var array $filters @var string $path */
$page = (int) $result['page']; $pages = (int) $result['pages'];
if ($pages <= 1) return;
$link = fn(int $n) => e(url($path, array_merge($filters, ['page' => $n])));   // url() drops empty values
$cls = 'inline-flex h-9 min-w-9 items-center justify-center border px-3 text-sm transition';
?>
<nav class="mt-6 flex flex-wrap items-center justify-end gap-1.5" aria-label="Phân trang">
  <?php for ($n = 1; $n <= $pages; $n++): ?>
    <?php if ($n === $page): ?>
      <span class="<?= $cls ?> border-crimson bg-crimson text-ivory" aria-current="page"><?= $n ?></span>
    <?php elseif ($n === 1 || $n === $pages || abs($n - $page) <= 2): ?>
      <a href="<?= $link($n) ?>" data-ajax-link class="<?= $cls ?> border-line bg-white hover:border-gold"><?= $n ?></a>
    <?php elseif (abs($n - $page) === 3): ?>
      <span class="px-1 text-ink-soft">…</span>
    <?php endif; ?>
  <?php endfor; ?>
</nav>
