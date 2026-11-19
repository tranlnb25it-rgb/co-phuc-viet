<?php
/** @var array $result @var array $filters */
use App\Core\View;
use App\Services\Coupons;
?>
<p class="mb-3 text-sm text-ink-soft"><?= (int) $result['total'] ?> mã</p>
<?php if (!$result['items']): ?>
  <div class="border border-dashed border-line px-6 py-16 text-center text-ink-soft">
    Chưa có mã giảm giá nào. <a href="<?= e(url('/admin/khuyen-mai/them')) ?>" class="text-crimson underline">Tạo mã đầu tiên</a>
  </div>
<?php else: ?>
<div class="overflow-x-auto border border-line bg-white/70">
  <table class="w-full min-w-[900px] text-sm">
    <thead class="border-b border-line bg-silk/60 text-left text-xs uppercase tracking-wider text-ink-soft">
      <tr>
        <th class="px-4 py-3 font-semibold">Mã</th>
        <th class="px-3 py-3 font-semibold">Ưu đãi</th>
        <th class="px-3 py-3 font-semibold">Thời gian</th>
        <th class="px-3 py-3 text-right font-semibold">Đã dùng</th>
        <th class="px-3 py-3 text-right font-semibold">Hiệu quả</th>
        <th class="px-4 py-3 font-semibold">Trạng thái</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-line">
      <?php foreach ($result['items'] as $c): [$st, $stCls] = Coupons::status($c); $href = e(url('/admin/khuyen-mai/' . $c['id'])); ?>
        <tr class="cursor-pointer hover:bg-silk/40" data-href="<?= $href ?>">
          <td class="px-4 py-3">
            <a href="<?= $href ?>" class="font-mono font-semibold tracking-wider text-crimson hover:underline"><?= e($c['code']) ?></a>
            <?php if ($c['description']): ?><p class="max-w-56 truncate text-xs text-ink-soft"><?= e($c['description']) ?></p><?php endif; ?>
          </td>
          <td class="px-3 py-3"><?= e(Coupons::describe($c)) ?>
            <?php if ($c['per_phone_limit']): ?><p class="text-xs text-ink-soft">Mỗi khách <?= (int) $c['per_phone_limit'] ?> lần</p><?php endif; ?></td>
          <td class="px-3 py-3 text-xs text-ink-soft">
            <?= $c['starts_at'] ? 'Từ ' . e(date('H:i d/m/Y', strtotime($c['starts_at']))) : 'Từ khi tạo' ?><br>
            <?= $c['ends_at'] ? 'Đến ' . e(date('H:i d/m/Y', strtotime($c['ends_at']))) : 'Không hết hạn' ?>
          </td>
          <td class="px-3 py-3 text-right tabular-nums">
            <?= (int) $c['used_count'] ?><?= $c['usage_limit'] !== null ? ' / ' . (int) $c['usage_limit'] : '' ?>
            <?php if ($c['usage_limit']): $pct = min(100, (int) round($c['used_count'] / $c['usage_limit'] * 100)); ?>
              <span class="mt-1 ml-auto block h-1 w-16 overflow-hidden rounded-full bg-line"><span class="block h-full rounded-full bg-crimson" style="width: <?= $pct ?>%"></span></span>
            <?php endif; ?>
          </td>
          <td class="px-3 py-3 text-right text-xs tabular-nums">
            <p><strong class="text-sm"><?= (int) $c['order_count'] ?></strong> đơn</p>
            <p class="text-ink-soft">Đã giảm <?= money_compact((int) $c['discount_total']) ?> · DT <?= money_compact((int) $c['revenue']) ?></p>
          </td>
          <td class="px-4 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium <?= $stCls ?>"><?= e($st) ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= View::partial('admin/partials/pagination', ['result' => $result, 'filters' => $filters, 'path' => '/admin/khuyen-mai']) ?>
<?php endif; ?>
