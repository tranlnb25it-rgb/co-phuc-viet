<?php
/** @var array $values group => settings */
$v = $values;
$in = 'input mt-1.5 aria-[invalid=true]:border-crimson aria-[invalid=true]:ring-2 aria-[invalid=true]:ring-crimson/20';
$card = 'border border-line bg-white/70 p-5 sm:p-6';
$field = fn(string $group, string $key, string $label, string $type = 'text', string $hint = '') =>
    '<label class="block"><span class="text-sm font-medium">' . e($label) . '</span>'
    . '<input name="' . $key . '" type="' . $type . '" value="' . e((string) ($v[$group][$key] ?? '')) . '" class="' . $in . '"'
    . ($type === 'number' ? ' inputmode="numeric" min="0"' : '') . '>'
    . ($hint ? '<span class="mt-1 block text-xs text-ink-soft">' . e($hint) . '</span>' : '')
    . '<span class="mt-1 block min-h-4 text-xs text-crimson" data-error-for="' . $group . '.' . $key . '"></span></label>';
$save = '<div class="flex justify-end"><button class="btn-primary px-5! py-2!">' . icon('check', 'h-4 w-4') . ' Lưu</button></div>';
?>
<header>
  <p class="eyebrow">Hệ thống</p>
  <h1 class="mt-1 text-4xl">Cài đặt cửa hàng</h1>
  <p class="mt-1 text-sm text-ink-soft">Thay đổi có hiệu lực ngay trên cửa hàng, hoá đơn và trang thanh toán.</p>
</header>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
  <form class="<?= $card ?> space-y-2" data-settings-form="<?= e(url('/admin/api/settings/store')) ?>">
    <h2 class="text-2xl">Thông tin cửa hàng</h2>
    <p class="text-xs text-ink-soft">In trên hoá đơn, phiếu giao hàng và chân trang.</p>
    <?= $field('store', 'name', 'Tên cửa hàng') ?>
    <?= $field('store', 'address', 'Địa chỉ') ?>
    <div class="grid gap-x-4 sm:grid-cols-2">
      <?= $field('store', 'phone', 'Hotline', 'tel') ?>
      <?= $field('store', 'email', 'Email', 'email') ?>
    </div>
    <?= $field('store', 'hours', 'Giờ làm việc') ?>
    <?= $save ?>
  </form>

  <form class="<?= $card ?> space-y-2" data-settings-form="<?= e(url('/admin/api/settings/bank')) ?>">
    <h2 class="text-2xl">Tài khoản ngân hàng</h2>
    <p class="text-xs text-ink-soft">Hiện cho khách chọn “Chuyển khoản” ở trang thanh toán và trang đặt hàng thành công.</p>
    <?= $field('bank', 'name', 'Ngân hàng (và chi nhánh)') ?>
    <?= $field('bank', 'account', 'Số tài khoản', 'text', 'Chỉ chữ số, có thể có dấu cách') ?>
    <?= $field('bank', 'holder', 'Chủ tài khoản', 'text', 'Viết hoa không dấu như trên thẻ, VD: NGUYEN VAN A') ?>
    <?= $save ?>
  </form>

  <form class="<?= $card ?> space-y-2" data-settings-form="<?= e(url('/admin/api/settings/shipping')) ?>">
    <h2 class="text-2xl">Vận chuyển</h2>
    <div class="grid gap-x-4 sm:grid-cols-2">
      <?= $field('shipping', 'fee', 'Phí vận chuyển (₫)', 'number') ?>
      <?= $field('shipping', 'free_from', 'Miễn phí cho đơn từ (₫)', 'number', 'Nhập 0 để không miễn phí') ?>
    </div>
    <?= $save ?>
  </form>

  <form class="<?= $card ?> space-y-2" data-settings-form="<?= e(url('/admin/api/settings/inventory')) ?>">
    <h2 class="text-2xl">Kho hàng</h2>
    <?= $field('inventory', 'low_stock', 'Cảnh báo “sắp hết” khi còn ≤', 'number', 'Dùng cho tổng quan, trang kho và nhãn “Chỉ còn … sản phẩm” trên cửa hàng') ?>
    <?= $save ?>
  </form>

  <form class="<?= $card ?> space-y-3" data-settings-form="<?= e(url('/admin/api/settings/reviews')) ?>">
    <h2 class="text-2xl">Đánh giá sản phẩm</h2>
    <p class="text-xs text-ink-soft">Chỉ khách có tài khoản và đã nhận hàng (đơn “Hoàn thành”) mới viết được đánh giá.</p>
    <label class="flex items-start gap-3 text-sm">
      <input type="hidden" name="auto_approve" value="0">
      <input type="checkbox" name="auto_approve" value="1" class="mt-0.5 h-4 w-4 accent-crimson" <?= !empty($v['reviews']['auto_approve']) ? 'checked' : '' ?>>
      <span><strong>Tự động hiển thị</strong> đánh giá mới<br><span class="text-xs text-ink-soft">Tắt: đánh giá chờ duyệt trong mục <a href="<?= e(url('/admin/danh-gia')) ?>" class="text-crimson underline">Đánh giá</a> trước khi hiện trên cửa hàng.</span></span>
    </label>
    <?= $save ?>
  </form>
</div>
