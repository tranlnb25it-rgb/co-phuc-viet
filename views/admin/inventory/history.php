<?php
/** Stock movement history (page render + Ajax). @var array $result @var array $filters */
use App\Core\View;
use App\Services\Inventory;
?>
<p class="mb-3 text-sm text-ink-soft"><?= (int) $result['total'] ?> lần thay đổi</p>
<?php if (!$result['items']): ?>
  <div class="border border-dashed border-line px-6 py-16 text-center text-ink-soft">Chưa có thay đổi nào phù hợp.</div>
<?php else: ?>
<div class="overflow-x-auto border border-line bg-white/70">
  <table class="w-full min-w-[900px] text-sm">
    <thead class="border-b border-line bg-silk/60 text-left text-xs uppercase tracking-wider text-ink-soft">
      <tr>
        <th class="px-4 py-3 font-semibold">Thời gian</th>
        <th class="px-3 py-3 font-semibold">Sản phẩm</th>
        <th class="px-3 py-3 font-semibold">Lý do</th>
        <th class="px-3 py-3 text-right font-semibold">Thay đổi</th>
        <th class="px-3 py-3 text-right font-semibold">Còn lại</th>
        <th class="px-4 py-3 font-semibold">Người làm / ghi chú</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-line">
      <?php foreach ($result['items'] as $m): [$label, $cls] = Inventory::REASONS[$m['reason']]; $ch = (int) $m['qty_change']; ?>
        <tr class="hover:bg-silk/40">
          <td class="whitespace-nowrap px-4 py-3 text-ink-soft"><?= e(date('H:i d/m/Y', strtotime($m['created_at']))) ?></td>
          <td class="px-3 py-3">
            <?php if ($m['product_id']): ?><a href="<?= e(url('/admin/san-pham/' . $m['product_id'])) ?>" class="font-medium hover:text-crimson"><?= e($m['product_name']) ?></a>
            <?php else: ?><span class="font-medium"><?= e($m['product_name']) ?></span> <span class="text-xs text-ink-soft">(đã xoá)</span><?php endif; ?>
            <p class="text-xs text-ink-soft"><?= e($m['variant_label']) ?> · <span class="font-mono"><?= e($m['sku']) ?></span></p>
          </td>
          <td class="px-3 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium <?= $cls ?>"><?= e($label) ?></span>
            <?php if ($m['order_code']): ?><a href="<?= e(url('/admin/don-hang/' . $m['order_code'])) ?>" class="ml-1 font-mono text-xs text-crimson hover:underline"><?= e($m['order_code']) ?></a><?php endif; ?></td>
          <td class="px-3 py-3 text-right font-semibold tabular-nums <?= $ch < 0 ? 'text-crimson' : 'text-emerald-700' ?>"><?= $ch > 0 ? '+' : '−' ?><?= abs($ch) ?></td>
          <td class="px-3 py-3 text-right tabular-nums"><?= (int) $m['stock_after'] ?></td>
          <td class="px-4 py-3 text-xs">
            <p><?= e($m['user_name'] ?? ($m['reason'] === 'order' ? 'Khách đặt hàng' : 'Hệ thống')) ?></p>
            <?php if ($m['note']): ?><p class="text-ink-soft"><?= e($m['note']) ?></p><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= View::partial('admin/partials/pagination', ['result' => $result, 'filters' => $filters, 'path' => '/admin/kho']) ?>
<?php endif; ?>
