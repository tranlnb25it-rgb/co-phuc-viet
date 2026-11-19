<?php
/** @var array $filters @var array $result @var array $counts */
use App\Core\View;
use App\Services\AdminOrderService as Orders;

$tabs = ['' => 'Tất cả'] + array_map(fn($s) => $s[0], Orders::STATUSES);
$exportQuery = array_filter(['status' => $filters['status'], 'q' => $filters['q'], 'from' => $filters['from'], 'to' => $filters['to']]);
?>
<header class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <p class="eyebrow">Quản lý</p>
    <h1 class="mt-1 text-4xl">Đơn hàng</h1>
  </div>
  <?php if (App\Support\Auth::can('orders.manage')): ?>
  <a href="<?= e(url('/admin/don-hang/xuat-excel', $exportQuery)) ?>" class="btn-outline px-4! py-2!" data-export-link
     data-base-href="<?= e(url('/admin/don-hang/xuat-excel')) ?>">
    <?= icon('copy', 'h-4 w-4') ?> Xuất Excel
  </a>
  <?php endif; ?>
</header>

<form class="mt-6 space-y-4" method="get" action="<?= e(url('/admin/don-hang')) ?>" data-ajax-filter="#order-list">
  <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Trạng thái">
    <?php foreach ($tabs as $k => $label): ?>
      <label class="cursor-pointer">
        <input type="radio" name="status" value="<?= $k ?>" class="peer sr-only" <?= $filters['status'] === $k ? 'checked' : '' ?>>
        <span class="inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-3 py-1.5 text-sm transition hover:border-gold peer-checked:border-crimson peer-checked:bg-crimson peer-checked:text-ivory peer-focus-visible:outline-2 peer-focus-visible:outline-gold">
          <?= e($label) ?> <span class="text-xs opacity-70" data-count="<?= $k ?>"><?= (int) ($counts[$k] ?? 0) ?></span>
        </span>
      </label>
    <?php endforeach; ?>
  </div>
  <div class="flex flex-wrap items-end gap-3">
    <label class="relative block min-w-64 flex-1 sm:max-w-md">
      <span class="sr-only">Tìm kiếm</span>
      <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-ink-soft"><?= icon('search', 'h-4 w-4') ?></span>
      <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Mã đơn, số điện thoại hoặc tên khách…" class="input pl-9" autocomplete="off">
    </label>
    <label class="text-xs text-ink-soft">Từ ngày<input type="date" name="from" value="<?= e($filters['from']) ?>" class="input mt-1 w-auto! py-2!"></label>
    <label class="text-xs text-ink-soft">Đến ngày<input type="date" name="to" value="<?= e($filters['to']) ?>" class="input mt-1 w-auto! py-2!"></label>
  </div>
  <noscript><button class="btn-primary">Lọc</button></noscript>
</form>

<!-- Bulk actions (shown when rows are selected) -->
<div class="sticky top-2 z-30 mt-6 hidden flex-wrap items-center gap-3 border border-ink bg-ink px-4 py-3 text-sm text-ivory shadow-lg" data-bulk-bar
     data-bulk-url="<?= e(url('/admin/api/orders/bulk')) ?>" data-print-url="<?= e(url('/admin/don-hang/in')) ?>">
  <span>Đã chọn <strong data-bulk-count>0</strong> đơn</span>
  <?php if (App\Support\Auth::can('orders.manage')): ?>
  <span class="h-4 w-px bg-ivory/30"></span>
  <label class="flex items-center gap-2">Chuyển sang
    <select class="rounded-sm border-0 bg-ivory px-2 py-1.5 text-sm text-ink" data-bulk-status>
      <?php foreach (Orders::STATUSES as $k => [$label]): ?><option value="<?= $k ?>"><?= e($label) ?></option><?php endforeach; ?>
    </select>
  </label>
  <button type="button" class="rounded-sm bg-gold px-3 py-1.5 font-medium text-ink hover:bg-gold-light" data-bulk-apply>Áp dụng</button>
  <?php endif; ?>
  <span class="h-4 w-px bg-ivory/30"></span>
  <button type="button" class="underline-offset-4 hover:underline" data-bulk-print="invoice">In hoá đơn</button>
  <button type="button" class="underline-offset-4 hover:underline" data-bulk-print="packing">In phiếu giao</button>
  <button type="button" class="ml-auto text-ivory/70 hover:text-ivory" data-bulk-clear>Bỏ chọn</button>
</div>

<div id="order-list" class="mt-4 transition-opacity" data-endpoint="<?= e(url('/admin/api/orders')) ?>">
  <?= View::partial('admin/orders/rows', ['result' => $result, 'filters' => $filters]) ?>
</div>
