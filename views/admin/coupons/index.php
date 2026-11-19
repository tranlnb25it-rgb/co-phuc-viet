<?php
/** @var array $filters @var array $result */
use App\Core\View;
?>
<header class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <p class="eyebrow">Bán hàng</p>
    <h1 class="mt-1 text-4xl">Mã giảm giá</h1>
    <p class="mt-1 text-sm text-ink-soft">Giá khuyến mãi theo từng sản phẩm được đặt trong trang sửa sản phẩm, hoặc chọn nhiều sản phẩm → “Giảm giá %”.</p>
  </div>
  <a href="<?= e(url('/admin/khuyen-mai/them')) ?>" class="btn-primary"><?= icon('plus', 'h-4 w-4') ?> Tạo mã</a>
</header>

<form class="mt-6 flex flex-wrap items-end gap-3" method="get" action="<?= e(url('/admin/khuyen-mai')) ?>" data-ajax-filter="#coupon-list">
  <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Trạng thái">
    <?php foreach (['' => 'Tất cả', 'running' => 'Đang chạy', 'ended' => 'Đã kết thúc / tắt'] as $k => $label): ?>
      <label class="cursor-pointer">
        <input type="radio" name="status" value="<?= $k ?>" class="peer sr-only" <?= $filters['status'] === $k ? 'checked' : '' ?>>
        <span class="inline-flex rounded-full border border-line bg-white px-3 py-1.5 text-sm transition hover:border-gold peer-checked:border-crimson peer-checked:bg-crimson peer-checked:text-ivory peer-focus-visible:outline-2 peer-focus-visible:outline-gold"><?= e($label) ?></span>
      </label>
    <?php endforeach; ?>
  </div>
  <label class="relative block min-w-56 flex-1 sm:max-w-xs">
    <span class="sr-only">Tìm mã</span>
    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-ink-soft"><?= icon('search', 'h-4 w-4') ?></span>
    <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Tìm mã hoặc mô tả…" class="input pl-9" autocomplete="off">
  </label>
  <noscript><button class="btn-primary">Lọc</button></noscript>
</form>

<div id="coupon-list" class="mt-5 transition-opacity" data-endpoint="<?= e(url('/admin/api/coupons')) ?>">
  <?= View::partial('admin/coupons/rows', ['result' => $result, 'filters' => $filters]) ?>
</div>
