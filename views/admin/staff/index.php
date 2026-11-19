<?php
/** @var array $staff */
use App\Core\View;
use App\Support\Auth;

$in = 'input mt-1 py-2! aria-[invalid=true]:border-crimson';
?>
<header>
  <p class="eyebrow">Hệ thống</p>
  <h1 class="mt-1 text-4xl">Nhân viên & phân quyền</h1>
</header>

<section class="mt-6 grid gap-4 md:grid-cols-3">
  <?php
  $desc = [
      'admin'     => 'Toàn quyền: sản phẩm, kho, đơn hàng, khách hàng, danh mục, cài đặt, nhân viên, nhật ký.',
      'sales'     => 'Tổng quan, đơn hàng (xử lý, in, xuất Excel), khách hàng, mã giảm giá. Không sửa sản phẩm, kho.',
      'warehouse' => 'Tổng quan, sản phẩm, kho hàng; xem & in đơn để đóng gói. Không đổi trạng thái đơn.',
  ];
  foreach (Auth::ROLES as $role => $label): ?>
    <div class="border border-line bg-white/70 p-4 text-sm">
      <p class="font-semibold"><?= e($label) ?></p>
      <p class="mt-1 text-xs text-ink-soft"><?= e($desc[$role]) ?></p>
    </div>
  <?php endforeach; ?>
</section>

<div class="mt-6" data-staff-list><?= View::partial('admin/staff/rows', ['staff' => $staff]) ?></div>

<form class="mt-6 border border-gold/40 bg-silk/50 p-5" data-staff-form="<?= e(url('/admin/api/staff')) ?>" data-method="POST">
  <h2 class="text-2xl">Thêm nhân viên</h2>
  <div class="mt-3 grid gap-3 md:grid-cols-[1fr_1fr_0.8fr_0.9fr_1fr_auto] md:items-start">
    <label class="text-xs font-medium">Họ tên<input name="full_name" class="<?= $in ?>"><span class="block text-[11px] text-crimson" data-error-for="full_name"></span></label>
    <label class="text-xs font-medium">Email đăng nhập<input name="email" type="email" class="<?= $in ?>"><span class="block text-[11px] text-crimson" data-error-for="email"></span></label>
    <label class="text-xs font-medium">Điện thoại<input name="phone" class="<?= $in ?>"><span class="block text-[11px] text-crimson" data-error-for="phone"></span></label>
    <label class="text-xs font-medium">Vai trò
      <select name="role" class="<?= $in ?>"><?php foreach (Auth::ROLES as $k => $l): ?><option value="<?= $k ?>" <?= $k === 'sales' ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
      <span class="block text-[11px] text-crimson" data-error-for="role"></span></label>
    <label class="text-xs font-medium">Mật khẩu (≥ 8 ký tự)<input name="password" type="password" autocomplete="new-password" class="<?= $in ?>"><span class="block text-[11px] text-crimson" data-error-for="password"></span></label>
    <button class="btn-primary mt-5 px-4! py-2!"><?= icon('plus', 'h-4 w-4') ?> Thêm</button>
  </div>
</form>
