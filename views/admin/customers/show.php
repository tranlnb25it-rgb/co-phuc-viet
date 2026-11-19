<?php
/** @var array $c CustomerService::find() */
use App\Core\View;
use App\Services\AdminOrderService as Orders;
use App\Services\CustomerService as Customers;

$card = 'border border-line bg-white/70 p-5 sm:p-6';
$aov = $c['valid_orders'] ? (int) round($c['spent'] / $c['valid_orders']) : 0;
?>
<header class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <a href="<?= e(url('/admin/khach-hang')) ?>" class="text-sm text-ink-soft hover:text-crimson">← Khách hàng</a>
    <h1 class="mt-1 flex flex-wrap items-center gap-3 text-4xl"><?= e($c['name']) ?>
      <?php if ($c['spent'] >= Customers::VIP_FROM): ?><span class="rounded-full bg-gold/20 px-3 py-1 font-sans text-sm font-medium text-gold-dark">VIP</span><?php endif; ?>
    </h1>
    <p class="mt-1 text-sm text-ink-soft">
      <a href="tel:<?= e($c['phone']) ?>" class="text-crimson hover:underline"><?= e($c['phone']) ?></a>
      <?= $c['email'] ? ' · <a href="mailto:' . e($c['email']) . '" class="hover:underline">' . e($c['email']) . '</a>' : '' ?>
      · Khách từ <?= e(date('d/m/Y', strtotime($c['first_order']))) ?>
    </p>
  </div>
</header>

<section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
  <?php foreach ([['Tổng chi tiêu', money($c['spent'])], ['Số đơn thành công', (string) $c['valid_orders']],
                  ['Giá trị TB / đơn', money($aov)], ['Đơn gần nhất', date('d/m/Y', strtotime($c['last_order']))]] as [$l, $v]): ?>
    <div class="border border-line bg-white/70 p-5"><p class="text-sm text-ink-soft"><?= e($l) ?></p><p class="mt-2 text-2xl font-semibold tracking-tight"><?= e($v) ?></p></div>
  <?php endforeach; ?>
</section>

<div class="mt-6 grid gap-6 xl:grid-cols-[1fr_360px]">
  <section class="<?= $card ?>">
    <h2 class="text-2xl">Lịch sử đơn hàng</h2>
    <div class="mt-4 overflow-x-auto">
      <table class="w-full min-w-[560px] text-sm">
        <tbody class="divide-y divide-line">
          <?php foreach ($c['order_list'] as $o): $href = e(url('/admin/don-hang/' . $o['order_code'])); ?>
            <tr class="cursor-pointer hover:bg-silk/40" data-href="<?= $href ?>">
              <td class="py-3 pr-3"><a href="<?= $href ?>" class="font-mono font-medium text-crimson hover:underline"><?= e($o['order_code']) ?></a>
                <p class="text-xs text-ink-soft"><?= e(date('H:i d/m/Y', strtotime($o['created_at']))) ?></p></td>
              <td class="px-3 py-3"><?= (int) $o['item_count'] ?> món</td>
              <td class="px-3 py-3"><?= View::partial('admin/partials/badge', ['map' => Orders::STATUSES, 'key' => $o['status']]) ?></td>
              <td class="py-3 pl-3 text-right font-medium tabular-nums"><?= money($o['total']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <div class="space-y-6">
    <section class="<?= $card ?>">
      <h2 class="flex items-center gap-2 text-2xl"><?= icon('scissors', 'h-5 w-5 text-crimson') ?> Số đo đã dùng</h2>
      <?php if (!$c['measurements']): ?>
        <p class="mt-3 text-sm text-ink-soft">Khách chưa đặt may đo.</p>
      <?php else: ?>
        <ul class="mt-3 space-y-3 text-sm">
          <?php foreach ($c['measurements'] as $m): $mm = json_decode($m['custom_measurements'], true) ?: []; ?>
            <li class="border border-gold/40 bg-gold/10 px-3 py-2">
              <p class="font-medium"><?= e($m['product_name']) ?></p>
              <p class="text-xs"><?= e(measurements_text($mm)) ?></p>
              <?php if (!empty($mm['note'])): ?><p class="text-xs italic">“<?= e($mm['note']) ?>”</p><?php endif; ?>
              <p class="mt-1 text-[11px] text-ink-soft"><?= e($m['order_code']) ?> · <?= e(date('d/m/Y', strtotime($m['created_at']))) ?></p>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <section class="<?= $card ?> text-sm">
      <h2 class="text-2xl">Địa chỉ đã giao</h2>
      <ul class="mt-3 space-y-2">
        <?php foreach ($c['addresses'] as $i => $a): ?>
          <li class="flex gap-2"><?= icon('map-pin', 'mt-0.5 h-4 w-4 shrink-0 ' . ($i === 0 ? 'text-crimson' : 'text-ink-soft')) ?> <?= e($a) ?></li>
        <?php endforeach; ?>
      </ul>
      <?php if ($c['favorites']): ?>
        <h3 class="mt-5 text-xl">Sản phẩm hay mua</h3>
        <ul class="mt-2 space-y-1">
          <?php foreach ($c['favorites'] as $f): ?><li class="flex justify-between"><span><?= e($f['product_name']) ?></span><span class="text-ink-soft">× <?= (int) $f['qty'] ?></span></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
</div>
