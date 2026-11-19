<?php
/** @var array $filters @var array $result @var array $categories */
use App\Core\View;

$statuses = ['' => 'Tất cả', 'active' => 'Đang bán', 'hidden' => 'Đang ẩn', 'featured' => 'Nổi bật', 'sale' => 'Đang giảm giá', 'low' => 'Sắp hết hàng'];
?>
<header class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <p class="eyebrow">Quản lý</p>
    <h1 class="mt-1 text-4xl">Sản phẩm</h1>
  </div>
  <a href="<?= e(url('/admin/san-pham/them')) ?>" class="btn-primary"><?= icon('plus', 'h-4 w-4') ?> Thêm sản phẩm</a>
</header>

<form class="mt-6 grid gap-3 sm:grid-cols-[1fr_auto_auto]" method="get" action="<?= e(url('/admin/san-pham')) ?>"
      data-ajax-filter="#product-list">
  <label class="relative">
    <span class="sr-only">Tìm kiếm</span>
    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-ink-soft"><?= icon('search', 'h-4 w-4') ?></span>
    <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Tìm theo tên hoặc slug…" class="input pl-9" autocomplete="off">
  </label>
  <select name="category" class="input w-auto!" aria-label="Danh mục">
    <option value="">Mọi danh mục</option>
    <?php foreach ($categories as $c): ?>
      <option value="<?= $c['id'] ?>" <?= (int) $filters['category'] === $c['id'] ? 'selected' : '' ?>><?= e($c['label']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="status" class="input w-auto!" aria-label="Trạng thái">
    <?php foreach ($statuses as $k => $label): ?>
      <option value="<?= $k ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <noscript><button class="btn-primary">Lọc</button></noscript>
</form>

<!-- Bulk actions (shown when rows are selected) -->
<div class="sticky top-2 z-30 mt-6 hidden flex-wrap items-center gap-3 border border-ink bg-ink px-4 py-3 text-sm text-ivory shadow-lg" data-bulk-bar
     data-bulk-url="<?= e(url('/admin/api/products/bulk')) ?>" data-bulk-kind="products">
  <span>Đã chọn <strong data-bulk-count>0</strong> sản phẩm</span>
  <span class="h-4 w-px bg-ivory/30"></span>
  <?php foreach (['activate' => 'Bật bán', 'hide' => 'Ẩn', 'feature' => 'Đặt nổi bật', 'unfeature' => 'Bỏ nổi bật'] as $act => $label): ?>
    <button type="button" class="underline-offset-4 hover:underline" data-bulk-action="<?= $act ?>"><?= e($label) ?></button>
  <?php endforeach; ?>
  <span class="h-4 w-px bg-ivory/30"></span>
  <button type="button" class="rounded-sm bg-gold px-3 py-1 font-medium text-ink hover:bg-gold-light" data-bulk-sale>Giảm giá %…</button>
  <button type="button" class="underline-offset-4 hover:underline" data-bulk-action="unsale">Bỏ giảm giá</button>
  <span class="h-4 w-px bg-ivory/30"></span>
  <button type="button" class="rounded-sm bg-crimson px-3 py-1 font-medium hover:bg-crimson-light" data-bulk-action="delete">Xoá</button>
  <button type="button" class="ml-auto text-ivory/70 hover:text-ivory" data-bulk-clear>Bỏ chọn</button>
</div>

<div id="product-list" class="mt-4 transition-opacity" data-endpoint="<?= e(url('/admin/api/products')) ?>">
  <?= View::partial('admin/products/rows', ['result' => $result, 'filters' => $filters]) ?>
</div>
