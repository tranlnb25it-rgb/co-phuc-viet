<?php
/** @var array $result @var array $filters */
use App\Core\View;
use App\Services\CustomerService as Customers;
?>
<p class="mb-3 text-sm text-ink-soft"><?= (int) $result['total'] ?> khách hàng</p>
<?php if (!$result['items']): ?>
  <div class="border border-dashed border-line px-6 py-16 text-center text-ink-soft">Không có khách hàng phù hợp.</div>
<?php else: ?>
<div class="overflow-x-auto border border-line bg-white/70">
  <table class="w-full min-w-[760px] text-sm">
    <thead class="border-b border-line bg-silk/60 text-left text-xs uppercase tracking-wider text-ink-soft">
      <tr>
        <th class="px-4 py-3 font-semibold">Khách hàng</th>
        <th class="px-3 py-3 text-right font-semibold">Đơn</th>
        <th class="px-3 py-3 text-right font-semibold">Chi tiêu</th>
        <th class="px-3 py-3 font-semibold">Mua gần nhất</th>
        <th class="px-4 py-3 font-semibold">Nhóm</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-line">
      <?php foreach ($result['items'] as $c): $href = e(url('/admin/khach-hang/' . rawurlencode($c['phone']))); ?>
        <tr class="cursor-pointer hover:bg-silk/40" data-href="<?= $href ?>">
          <td class="px-4 py-3">
            <a href="<?= $href ?>" class="font-medium hover:text-crimson"><?= e($c['name']) ?></a>
            <p class="text-xs text-ink-soft"><?= e($c['phone']) ?><?= $c['email'] ? ' · ' . e($c['email']) : '' ?></p>
          </td>
          <td class="px-3 py-3 text-right tabular-nums"><?= (int) $c['valid_orders'] ?><?php if ($c['orders'] > $c['valid_orders']): ?><span class="text-xs text-ink-soft"> (+<?= $c['orders'] - $c['valid_orders'] ?> huỷ)</span><?php endif; ?></td>
          <td class="px-3 py-3 text-right font-semibold tabular-nums"><?= money($c['spent']) ?></td>
          <td class="px-3 py-3 text-ink-soft"><?= e(date('d/m/Y', strtotime($c['last_order']))) ?></td>
          <td class="px-4 py-3">
            <div class="flex flex-wrap gap-1">
              <?php if ($c['spent'] >= Customers::VIP_FROM): ?><span class="rounded-full bg-gold/20 px-2 py-0.5 text-xs font-medium text-gold-dark">VIP</span><?php endif; ?>
              <?php if ($c['valid_orders'] >= 2): ?><span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Mua lại</span><?php endif; ?>
              <?php if (strtotime($c['first_order']) >= strtotime('-30 days')): ?><span class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-800">Mới</span><?php endif; ?>
              <?php if ($c['custom_items']): ?><span class="inline-flex items-center gap-0.5 rounded-full bg-crimson/10 px-2 py-0.5 text-xs font-medium text-crimson"><?= icon('scissors', 'h-3 w-3') ?> May đo</span><?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= View::partial('admin/partials/pagination', ['result' => $result, 'filters' => $filters, 'path' => '/admin/khach-hang']) ?>
<?php endif; ?>
