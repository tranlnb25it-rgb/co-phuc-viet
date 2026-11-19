<?php
/** @var array $order AdminOrderService::find() */
use App\Core\View;
use App\Services\AdminOrderService as Orders;

$o = $order;
$final = $o['status'] === 'cancelled';
$canManage = App\Support\Auth::can('orders.manage');
$card = 'border border-line bg-white/70 p-5 sm:p-6';
$printUrl = fn(string $type) => e(url('/admin/don-hang/in', ['codes' => $o['order_code'], 'type' => $type]));
?>
<header class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <a href="<?= e(url('/admin/don-hang')) ?>" class="text-sm text-ink-soft hover:text-crimson">← Đơn hàng</a>
    <h1 class="mt-1 flex flex-wrap items-center gap-3 text-4xl">
      <span class="font-mono text-3xl"><?= e($o['order_code']) ?></span>
      <span data-status-badge><?= View::partial('admin/partials/badge', ['map' => Orders::STATUSES, 'key' => $o['status']]) ?></span>
    </h1>
    <p class="mt-1 text-sm text-ink-soft">Đặt lúc <?= e(date('H:i, d/m/Y', strtotime($o['created_at']))) ?> · <?= e(Orders::PAYMENT_METHODS[$o['payment_method']]) ?></p>
  </div>
  <div class="flex flex-wrap gap-2">
    <a href="<?= $printUrl('invoice') ?>" target="_blank" rel="noopener" class="btn-outline px-4! py-2!"><?= icon('book-open', 'h-4 w-4') ?> In hoá đơn</a>
    <a href="<?= $printUrl('packing') ?>" target="_blank" rel="noopener" class="btn-outline px-4! py-2!"><?= icon('truck', 'h-4 w-4') ?> In phiếu giao hàng</a>
  </div>
</header>

<div class="mt-6 grid gap-6 xl:grid-cols-[1fr_360px]">
  <div class="space-y-6">
    <section class="<?= $card ?>">
      <h2 class="text-2xl">Sản phẩm</h2>
      <ul class="mt-4 divide-y divide-line border-y border-line">
        <?php foreach ($o['items'] as $it): $m = $it['custom_measurements'] ? json_decode($it['custom_measurements'], true) : null; ?>
          <li class="flex flex-wrap justify-between gap-4 py-4 text-sm">
            <div class="min-w-0">
              <p class="font-medium">
                <?php if ($it['product_id']): ?>
                  <a href="<?= e(url('/admin/san-pham/' . $it['product_id'])) ?>" class="hover:text-crimson"><?= e($it['product_name']) ?></a>
                <?php else: ?><?= e($it['product_name']) ?> <span class="text-xs text-ink-soft">(đã xoá)</span><?php endif; ?>
                <span class="text-ink-soft">× <?= (int) $it['quantity'] ?></span>
              </p>
              <p class="text-xs text-ink-soft"><?= e(implode(' · ', array_filter([$it['fabric_name'], $it['color'], $it['size'] === 'CUSTOM' ? null : 'Size ' . $it['size']]))) ?></p>
              <?php if ($m): ?>
                <div class="mt-2 inline-block border border-gold/40 bg-gold/10 px-3 py-2 text-xs">
                  <p class="flex items-center gap-1 font-semibold text-crimson"><?= icon('scissors', 'h-3.5 w-3.5') ?> May đo theo số đo</p>
                  <p class="mt-1"><?= e(measurements_text($m)) ?></p>
                  <?php if (!empty($m['note'])): ?><p class="mt-1 italic">“<?= e($m['note']) ?>”</p><?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
            <div class="text-right">
              <p class="font-medium tabular-nums"><?= money($it['unit_price'] * $it['quantity']) ?></p>
              <p class="text-xs text-ink-soft"><?= money($it['unit_price']) ?> / sp</p>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
      <dl class="ml-auto mt-4 max-w-xs space-y-1.5 text-sm tabular-nums">
        <div class="flex justify-between"><dt class="text-ink-soft">Tạm tính</dt><dd><?= money($o['subtotal']) ?></dd></div>
<?php if ((int) ($o['discount'] ?? 0) > 0): ?>
        <div class="flex justify-between text-emerald-700"><dt>Giảm giá<?= $o['coupon_code'] ? ' (' . e($o['coupon_code']) . ')' : '' ?></dt><dd>−<?= money($o['discount']) ?></dd></div>
        <?php endif; ?>
        <div class="flex justify-between"><dt class="text-ink-soft">Vận chuyển</dt><dd><?= (int) $o['shipping_fee'] ? money($o['shipping_fee']) : 'Miễn phí' ?></dd></div>
        <div class="flex justify-between border-t border-line pt-2 text-base font-semibold"><dt>Tổng cộng</dt><dd class="text-crimson"><?= money($o['total']) ?></dd></div>
      </dl>
    </section>

    <section class="<?= $card ?>">
      <h2 class="text-2xl">Lịch sử xử lý</h2>
      <div class="mt-4" data-timeline><?= View::partial('admin/orders/timeline', ['order' => $o]) ?></div>
    </section>
  </div>

  <div class="space-y-6">
    <section class="<?= $card ?>" data-order-update="<?= e(url('/admin/api/orders/' . $o['order_code'])) ?>">
      <h2 class="text-2xl">Xử lý đơn</h2>
      <label class="mt-4 block"><span class="text-sm font-medium">Trạng thái đơn</span>
        <select name="status" class="input mt-1.5" <?= $final || !$canManage ? 'disabled' : '' ?> data-current="<?= e($o['status']) ?>">
          <?php foreach (Orders::STATUSES as $k => [$label]): ?>
            <option value="<?= $k ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select></label>
      <label class="mt-4 block"><span class="text-sm font-medium">Thanh toán (<?= e(Orders::PAYMENT_METHODS[$o['payment_method']]) ?>)</span>
        <select name="payment_status" class="input mt-1.5" <?= $canManage ? '' : 'disabled' ?> data-current="<?= e($o['payment_status']) ?>">
          <?php foreach (Orders::PAYMENT_STATUSES as $k => [$label]): ?>
            <option value="<?= $k ?>" <?= $o['payment_status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select></label>
      <p class="mt-2" data-payment-badge><?= View::partial('admin/partials/badge', ['map' => Orders::PAYMENT_STATUSES, 'key' => $o['payment_status']]) ?></p>
      <p class="mt-3 text-xs text-ink-soft" data-final-note><?= $final
          ? 'Đơn đã huỷ — tồn kho đã được hoàn lại, không thể mở lại.'
          : ($canManage ? 'Chọn “Đã huỷ” sẽ hoàn lại tồn kho các sản phẩm may sẵn.' : 'Bạn chỉ có quyền xem và in đơn này.') ?></p>
    </section>

    <section class="<?= $card ?> text-sm" data-customer-card>
      <?= View::partial('admin/orders/customer', ['order' => $o]) ?>
    </section>
  </div>
</div>
