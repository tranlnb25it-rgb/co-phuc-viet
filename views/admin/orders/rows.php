<?php
/** Orders table (page render + Ajax). @var array $result @var array $filters */
use App\Core\View;
use App\Services\AdminOrderService as Orders;
?>
<p class="mb-3 text-sm text-ink-soft"><?= (int) $result['total'] ?> đơn hàng</p>

<?php if (!$result['items']): ?>
  <div class="border border-dashed border-line px-6 py-16 text-center text-ink-soft">Không có đơn hàng phù hợp.</div>
<?php else: ?>
<div class="overflow-x-auto border border-line bg-white/70">
  <table class="w-full min-w-[860px] text-sm">
    <thead class="border-b border-line bg-silk/60 text-left text-xs uppercase tracking-wider text-ink-soft">
      <tr>
        <th class="w-10 py-3 pl-4"><input type="checkbox" class="h-4 w-4 accent-crimson" aria-label="Chọn tất cả đơn trên trang" data-select-all></th>
        <th class="px-4 py-3 font-semibold">Mã đơn</th>
        <th class="px-3 py-3 font-semibold">Khách hàng</th>
        <th class="px-3 py-3 font-semibold">Sản phẩm</th>
        <th class="px-3 py-3 font-semibold">Thanh toán</th>
        <th class="px-3 py-3 font-semibold">Trạng thái</th>
        <th class="px-4 py-3 text-right font-semibold">Tổng</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-line">
      <?php foreach ($result['items'] as $o): $href = e(url('/admin/don-hang/' . $o['order_code'])); ?>
        <tr class="cursor-pointer hover:bg-silk/40 has-[:checked]:bg-gold/10" data-href="<?= $href ?>">
          <td class="py-3 pl-4"><input type="checkbox" value="<?= e($o['order_code']) ?>" class="h-4 w-4 accent-crimson" aria-label="Chọn đơn <?= e($o['order_code']) ?>" data-select></td>
          <td class="px-4 py-3">
            <a href="<?= $href ?>" class="font-mono font-medium text-crimson hover:underline"><?= e($o['order_code']) ?></a>
            <p class="text-xs text-ink-soft"><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></p>
          </td>
          <td class="px-3 py-3"><?= e($o['customer_name']) ?><p class="text-xs text-ink-soft"><?= e($o['phone']) ?></p></td>
          <td class="px-3 py-3"><?= (int) $o['item_count'] ?> món
            <?php if ($o['custom_count']): ?><span class="ml-1 inline-flex items-center gap-0.5 text-xs text-crimson"><?= icon('scissors', 'h-3 w-3') ?> may đo</span><?php endif; ?></td>
          <td class="px-3 py-3">
            <p class="text-xs text-ink-soft"><?= e(Orders::PAYMENT_METHODS[$o['payment_method']]) ?></p>
            <?= View::partial('admin/partials/badge', ['map' => Orders::PAYMENT_STATUSES, 'key' => $o['payment_status']]) ?>
          </td>
          <td class="px-3 py-3"><?= View::partial('admin/partials/badge', ['map' => Orders::STATUSES, 'key' => $o['status']]) ?></td>
          <td class="px-4 py-3 text-right font-semibold tabular-nums"><?= money($o["total"]) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= View::partial('admin/partials/pagination', ['result' => $result, 'filters' => $filters, 'path' => '/admin/don-hang']) ?>
<?php endif; ?>
