<?php
/** Customer & shipping card with inline edit form. Re-rendered after changes. @var array $order */
$o = $order;
$editable = !in_array($o['status'], ['completed', 'cancelled'], true) && App\Support\Auth::can('orders.manage');
$in = 'input mt-1 aria-[invalid=true]:border-crimson aria-[invalid=true]:ring-2 aria-[invalid=true]:ring-crimson/20';
?>
<div class="flex items-center justify-between">
  <h2 class="text-2xl">Khách hàng</h2>
  <?php if ($editable): ?>
    <button type="button" class="text-xs font-medium text-crimson hover:underline" data-edit-customer>Sửa</button>
  <?php endif; ?>
</div>

<div data-customer-view>
  <p class="mt-3 font-medium"><?= e($o['customer_name']) ?></p>
  <p><a href="tel:<?= e($o['phone']) ?>" class="text-crimson hover:underline"><?= e($o['phone']) ?></a></p>
  <?php if ($o['email']): ?><p><a href="mailto:<?= e($o['email']) ?>" class="hover:underline"><?= e($o['email']) ?></a></p><?php endif; ?>
  <?php if (!empty($o['account_email'])): ?>
    <p class="mt-2 inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800" title="<?= e($o['account_name']) ?>"><?= icon('user', 'h-3 w-3') ?> Thành viên · <?= e($o['account_email']) ?></p>
  <?php else: ?>
    <p class="mt-2 inline-block rounded-full bg-ink/5 px-2 py-0.5 text-[11px] text-ink-soft">Khách vãng lai</p>
  <?php endif; ?>
  <p class="mt-3 text-xs font-semibold uppercase tracking-wider text-ink-soft">Giao đến</p>
  <p><?= e($o['shipping_address']) ?></p>
  <?php if ($o['note']): ?>
    <p class="mt-3 text-xs font-semibold uppercase tracking-wider text-ink-soft">Lời nhắn của khách</p>
    <p class="italic">“<?= e($o['note']) ?>”</p>
  <?php endif; ?>
</div>

<?php if ($editable): ?>
  <form class="mt-3 hidden space-y-2" data-customer-form="<?= e(url('/admin/api/orders/' . $o['order_code'] . '/customer')) ?>">
    <label class="block text-xs font-medium">Họ tên<input name="customer_name" value="<?= e($o['customer_name']) ?>" class="<?= $in ?>"></label>
    <span class="block text-xs text-crimson" data-error-for="customer_name"></span>
    <label class="block text-xs font-medium">Số điện thoại<input name="phone" value="<?= e($o['phone']) ?>" class="<?= $in ?>"></label>
    <span class="block text-xs text-crimson" data-error-for="phone"></span>
    <label class="block text-xs font-medium">Email<input name="email" value="<?= e($o['email']) ?>" class="<?= $in ?>"></label>
    <span class="block text-xs text-crimson" data-error-for="email"></span>
    <label class="block text-xs font-medium">Địa chỉ giao hàng<textarea name="shipping_address" rows="2" class="<?= $in ?> resize-y"><?= e($o['shipping_address']) ?></textarea></label>
    <span class="block text-xs text-crimson" data-error-for="shipping_address"></span>
    <div class="flex gap-2 pt-1">
      <button class="btn-primary flex-1 py-2!">Lưu</button>
      <button type="button" class="btn-outline py-2!" data-edit-cancel>Huỷ</button>
    </div>
  </form>
<?php endif; ?>
