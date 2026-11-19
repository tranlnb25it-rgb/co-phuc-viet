<?php
/** @var array $d AdminDashboardService::build() */
use App\Core\View;
use App\Services\AdminDashboardService as Dashboard;
?>
<header class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <p class="eyebrow">Xin chào, <?= e(App\Support\Auth::user()['name'] ?? '') ?></p>
    <h1 class="mt-1 text-4xl">Tổng quan</h1>
  </div>
  <a href="<?= e(url('/admin/san-pham/them')) ?>" class="btn-primary"><?= icon('plus', 'h-4 w-4') ?> Thêm sản phẩm</a>
</header>

<!-- Filter row: date range first, scopes everything below -->
<div class="mt-6 flex flex-wrap items-center gap-3" data-range-filter>
  <div class="inline-flex rounded-sm border border-line bg-white p-0.5" role="radiogroup" aria-label="Khoảng thời gian">
    <?php foreach (Dashboard::RANGES as $days => $label): $on = $d['days'] === $days; ?>
      <a href="<?= e(url('/admin', ['range' => $days])) ?>" role="radio" aria-checked="<?= $on ? 'true' : 'false' ?>" data-range="<?= $days ?>"
         class="rounded-sm px-3.5 py-1.5 text-sm transition aria-checked:bg-ink aria-checked:text-ivory hover:bg-silk aria-checked:hover:bg-ink"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div id="dashboard-body" class="transition-opacity" data-endpoint="<?= e(url('/admin/api/dashboard')) ?>">
  <?= View::partial('admin/dashboard-body', ['d' => $d]) ?>
</div>
