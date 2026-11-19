<?php
/** @var array $filters @var array $result */
use App\Core\View;
use App\Services\CustomerService as Customers;

$s = $result['summary'];
$tiles = [
    ['Tổng khách hàng', number_format((int) $s['customers'], 0, ',', '.')],
    ['Mua từ 2 lần', number_format((int) $s['repeaters'], 0, ',', '.')],
    ['Khách VIP', number_format((int) $s['vip'], 0, ',', '.')],
    ['Chi tiêu TB / khách', money((int) round((float) $s['avg_spent']))],
];
?>
<header>
  <p class="eyebrow">Bán hàng</p>
  <h1 class="mt-1 text-4xl">Khách hàng</h1>
  <p class="mt-1 text-sm text-ink-soft">Gộp theo số điện thoại đặt hàng. Chi tiêu không tính đơn đã huỷ.</p>
</header>

<section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
  <?php foreach ($tiles as [$label, $value]): ?>
    <div class="border border-line bg-white/70 p-5">
      <p class="text-sm text-ink-soft"><?= e($label) ?></p>
      <p class="mt-2 text-3xl font-semibold tracking-tight"><?= e($value) ?></p>
    </div>
  <?php endforeach; ?>
</section>

<form class="mt-6 space-y-4" method="get" action="<?= e(url('/admin/khach-hang')) ?>" data-ajax-filter="#customer-list">
  <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Nhóm khách">
    <?php foreach (Customers::SEGMENTS as $k => $label): ?>
      <label class="cursor-pointer">
        <input type="radio" name="segment" value="<?= $k ?>" class="peer sr-only" <?= $filters['segment'] === $k ? 'checked' : '' ?>>
        <span class="inline-flex rounded-full border border-line bg-white px-3 py-1.5 text-sm transition hover:border-gold peer-checked:border-crimson peer-checked:bg-crimson peer-checked:text-ivory peer-focus-visible:outline-2 peer-focus-visible:outline-gold"><?= e($label) ?></span>
      </label>
    <?php endforeach; ?>
  </div>
  <div class="flex flex-wrap gap-3">
    <label class="relative block min-w-64 flex-1 sm:max-w-md">
      <span class="sr-only">Tìm kiếm</span>
      <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-ink-soft"><?= icon('search', 'h-4 w-4') ?></span>
      <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Tên, số điện thoại hoặc email…" class="input pl-9" autocomplete="off">
    </label>
    <select name="sort" class="input w-auto!" aria-label="Sắp xếp">
      <?php foreach (Customers::SORTS as $k => $label): ?>
        <option value="<?= $k ?>" <?= $filters['sort'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <noscript><button class="btn-primary">Lọc</button></noscript>
</form>

<div id="customer-list" class="mt-6 transition-opacity" data-endpoint="<?= e(url('/admin/api/customers')) ?>">
  <?= View::partial('admin/customers/rows', ['result' => $result, 'filters' => $filters]) ?>
</div>
