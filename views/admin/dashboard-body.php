<?php
/**
 * Everything the range filter scopes. Re-rendered by /admin/api/dashboard.
 * @var array $d AdminDashboardService::build()
 */
use App\Core\View;
use App\Services\AdminOrderService as Orders;
use App\Services\AdminProductService;

$k = $d['kpis'];
$periodLabel = $d['days'] . ' ngày trước đó';
$tiles = [
    ['Doanh thu', money($k['revenue']), 'revenue', true],
    ['Số đơn hàng', number_format($k['orders'], 0, ',', '.'), 'orders', true],
    ['Giá trị trung bình / đơn', money($k['aov']), 'aov', true],
    ['Sản phẩm may đo', str_replace('.', ',', (string) $k['custom_share']) . '%', 'custom_share', null],   // neither good nor bad
];

$statusRows = [];
foreach (Orders::STATUSES as $key => [$label]) {
    $n = $d['statuses'][$key];
    $statusRows[] = ['label' => $label, 'value' => $n, 'display' => (string) $n,
                     'href' => url('/admin/don-hang', ['status' => $key, 'from' => $d['start']->format('Y-m-d'), 'to' => $d['end']->format('Y-m-d')])];
}
$topRows = array_map(fn($p) => [
    'label'   => $p['name'],
    'sub'     => $p['qty'] . ' sản phẩm' . ($p['custom_qty'] ? ' · ' . $p['custom_qty'] . ' may đo' : ''),
    'value'   => (int) $p['revenue'],
    'display' => money_compact((int) $p['revenue']),
    'href'    => $p['product_id'] ? url('/admin/san-pham/' . $p['product_id']) : null,
], $d['top']);
$card = 'border border-line bg-white/70';
?>
<p class="mt-3 text-sm text-ink-soft"><?= e($d['start']->format('d/m/Y') . ' – ' . $d['end']->format('d/m/Y')) ?> · so sánh với <?= e($periodLabel) ?></p>

<!-- Stat tiles -->
<section class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
  <?php foreach ($tiles as [$label, $value, $key, $upIsGood]): $delta = $d['deltas'][$key]; ?>
    <div class="<?= $card ?> p-5">
      <p class="text-sm text-ink-soft"><?= e($label) ?></p>
      <p class="mt-2 text-3xl font-semibold tracking-tight text-ink"><?= e($value) ?></p>
      <p class="mt-1.5 flex items-center gap-1 text-xs">
        <?php if ($delta === null): ?>
          <span class="text-ink-soft">Chưa có dữ liệu kỳ trước</span>
        <?php else:
          $up = $delta >= 0;
          $cls = $upIsGood === null || $delta == 0 ? 'text-ink-soft' : ($up === $upIsGood ? 'text-emerald-700' : 'text-crimson'); ?>
          <span class="inline-flex items-center gap-0.5 font-semibold <?= $cls ?>">
            <?= $up ? '▲' : '▼' ?> <?= ($up ? '+' : '−') . str_replace('.', ',', (string) abs($delta)) ?>%
          </span>
          <span class="text-ink-soft">so với <?= e($periodLabel) ?></span>
        <?php endif; ?>
      </p>
    </div>
  <?php endforeach; ?>
</section>

<!-- Revenue over time -->
<section class="<?= $card ?> mt-6 p-5 sm:p-6">
  <div class="flex flex-wrap items-baseline justify-between gap-2">
    <h2 class="text-2xl">Doanh thu theo <?= $d['series']['bucket'] === 'week' ? 'tuần' : 'ngày' ?></h2>
    <p class="text-xs text-ink-soft">Không tính đơn đã huỷ</p>
  </div>
  <div class="mt-5">
    <?= View::partial('admin/charts/columns', [
        'points'  => $d['series']['points'],
        'caption' => 'Biểu đồ cột doanh thu theo ' . ($d['series']['bucket'] === 'week' ? 'tuần' : 'ngày'),
    ]) ?>
  </div>
</section>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
  <section class="<?= $card ?> p-5 sm:p-6">
    <div class="flex items-baseline justify-between gap-2">
      <h2 class="text-2xl">Đơn theo trạng thái</h2>
      <a href="<?= e(url('/admin/don-hang', ['status' => 'pending'])) ?>" class="text-xs text-crimson hover:underline"><?= (int) $d['pending'] ?> đơn đang chờ xác nhận →</a>
    </div>
    <div class="mt-5"><?= View::partial('admin/charts/hbars', ['rows' => $statusRows, 'empty' => 'Chưa có đơn hàng trong khoảng này']) ?></div>
  </section>

  <section class="<?= $card ?> p-5 sm:p-6">
    <h2 class="text-2xl">Bán chạy nhất</h2>
    <p class="text-xs text-ink-soft">Theo doanh thu</p>
    <div class="mt-4"><?= View::partial('admin/charts/hbars', ['rows' => $topRows, 'empty' => 'Chưa bán được sản phẩm nào trong khoảng này']) ?></div>
  </section>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[1.6fr_1fr]">
  <section class="<?= $card ?>">
    <div class="flex items-center justify-between border-b border-line px-5 py-4">
      <h2 class="text-2xl">Đơn hàng mới</h2>
      <a href="<?= e(url('/admin/don-hang')) ?>" class="text-sm text-crimson hover:underline">Xem tất cả →</a>
    </div>
    <?php if (!$d['recent']): ?>
      <p class="px-5 py-10 text-center text-sm text-ink-soft">Chưa có đơn hàng nào.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full min-w-[520px] text-sm">
          <tbody class="divide-y divide-line">
            <?php foreach ($d['recent'] as $o): ?>
              <tr class="cursor-pointer hover:bg-silk/50" data-href="<?= e(url('/admin/don-hang/' . $o['order_code'])) ?>">
                <td class="px-5 py-3"><a href="<?= e(url('/admin/don-hang/' . $o['order_code'])) ?>" class="font-mono font-medium text-crimson hover:underline"><?= e($o['order_code']) ?></a>
                  <p class="text-xs text-ink-soft"><?= e(date('d/m H:i', strtotime($o['created_at']))) ?></p></td>
                <td class="px-3 py-3"><?= e($o['customer_name']) ?><p class="text-xs text-ink-soft"><?= e($o['phone']) ?></p></td>
                <td class="px-3 py-3"><?= View::partial('admin/partials/badge', ['map' => Orders::STATUSES, 'key' => $o['status']]) ?></td>
                <td class="px-5 py-3 text-right font-medium tabular-nums"><?= money($o['total']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="<?= $card ?>">
    <div class="border-b border-line px-5 py-4">
      <h2 class="text-2xl">Sắp hết hàng</h2>
      <p class="text-xs text-ink-soft">Biến thể còn ≤ <?= low_stock() ?> sản phẩm</p>
    </div>
    <?php if (!$d['low_stock']): ?>
      <p class="px-5 py-10 text-center text-sm text-ink-soft">Tồn kho đều ổn.</p>
    <?php else: ?>
      <ul class="divide-y divide-line text-sm">
        <?php foreach ($d['low_stock'] as $v): ?>
          <li class="flex items-center justify-between gap-3 px-5 py-3">
            <a href="<?= e(url('/admin/san-pham/' . $v['product_id'])) ?>#ton-kho" class="min-w-0 hover:text-crimson">
              <p class="truncate font-medium"><?= e($v['name_vi']) ?></p>
              <p class="text-xs text-ink-soft"><?= e(implode(' · ', array_filter([$v['fabric'], $v['color_vi'], 'Size ' . $v['size']]))) ?></p>
            </a>
            <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold <?= (int) $v['stock'] === 0 ? 'bg-crimson text-ivory' : 'bg-gold/20 text-gold-dark' ?>">
              <?= (int) $v['stock'] === 0 ? 'Hết' : 'Còn ' . (int) $v['stock'] ?>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>
