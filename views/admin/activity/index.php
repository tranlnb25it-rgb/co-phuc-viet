<?php
/** @var array $filters @var array $result @var array $staff */
use App\Core\View;
use App\Services\Activity;
?>
<header>
  <p class="eyebrow">Hệ thống</p>
  <h1 class="mt-1 text-4xl">Nhật ký hoạt động</h1>
  <p class="mt-1 text-sm text-ink-soft">Ai đã làm gì, lúc nào — đăng nhập, sửa sản phẩm, kho, đơn hàng, cài đặt, nhân viên.</p>
</header>

<form class="mt-6 flex flex-wrap items-end gap-3" method="get" action="<?= e(url('/admin/nhat-ky')) ?>" data-ajax-filter="#activity-list">
  <label class="relative block min-w-64 flex-1 sm:max-w-sm">
    <span class="sr-only">Tìm kiếm</span>
    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-ink-soft"><?= icon('search', 'h-4 w-4') ?></span>
    <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Nội dung, mã đơn…" class="input pl-9" autocomplete="off">
  </label>
  <select name="user" class="input w-auto!" aria-label="Nhân viên">
    <option value="">Mọi nhân viên</option>
    <?php foreach ($staff as $u): ?><option value="<?= (int) $u['id'] ?>" <?= (int) $filters['user'] === (int) $u['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?></option><?php endforeach; ?>
  </select>
  <select name="group" class="input w-auto!" aria-label="Loại">
    <option value="">Mọi loại</option>
    <?php foreach (Activity::GROUPS as $k => $l): ?><option value="<?= $k ?>" <?= $filters['group'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
  </select>
  <label class="text-xs text-ink-soft">Từ ngày<input type="date" name="from" value="<?= e($filters['from']) ?>" class="input mt-1 w-auto! py-2!"></label>
  <label class="text-xs text-ink-soft">Đến ngày<input type="date" name="to" value="<?= e($filters['to']) ?>" class="input mt-1 w-auto! py-2!"></label>
  <noscript><button class="btn-primary">Lọc</button></noscript>
</form>

<div id="activity-list" class="mt-5 transition-opacity" data-endpoint="<?= e(url('/admin/api/activity')) ?>">
  <?= View::partial('admin/activity/rows', ['result' => $result, 'filters' => $filters]) ?>
</div>
