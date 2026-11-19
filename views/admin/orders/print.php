<?php
/**
 * Printable invoice / packing slip, one order per A4 page. Standalone page (no admin chrome).
 * @var list<array> $orders @var string $type invoice|packing
 */
use App\Services\AdminOrderService as Orders;

$store = store_info();
$isInvoice = $type === 'invoice';
$codes = implode(',', array_column($orders, 'order_code'));
$switchUrl = url('/admin/don-hang/in', ['codes' => $codes, 'type' => $isInvoice ? 'packing' : 'invoice']);
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $isInvoice ? 'Hoá đơn' : 'Phiếu giao hàng' ?> · <?= e(count($orders) === 1 ? $orders[0]['order_code'] : count($orders) . ' đơn') ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&family=Cormorant+Garamond:wght@600;700&display=swap&subset=vietnamese">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <style>
    @page { size: A4; margin: 14mm; }
    @media print {
      .no-print { display: none !important; }
      body { background: #fff !important; }
      .sheet { box-shadow: none !important; margin: 0 !important; border: 0 !important; padding: 0 !important; }
      .sheet + .sheet { break-before: page; }
      a { color: inherit; text-decoration: none; }
    }
  </style>
</head>
<body class="bg-silk text-ink">
  <div class="no-print sticky top-0 z-10 flex flex-wrap items-center justify-between gap-3 border-b border-line bg-ivory px-6 py-3">
    <p class="text-sm"><strong><?= $isInvoice ? 'Hoá đơn' : 'Phiếu giao hàng' ?></strong> · <?= count($orders) ?> đơn</p>
    <div class="flex gap-2">
      <a href="<?= e($switchUrl) ?>" class="btn-outline px-4! py-2!"><?= $isInvoice ? 'Xem phiếu giao hàng' : 'Xem hoá đơn' ?></a>
      <button onclick="window.print()" class="btn-primary px-4! py-2!">In (Ctrl + P)</button>
    </div>
  </div>

  <?php foreach ($orders as $o):
    $collect = $o['payment_method'] === 'COD' && $o['payment_status'] !== 'paid' ? (int) $o['total'] : 0; ?>
  <article class="sheet mx-auto my-8 max-w-[210mm] border border-line bg-white p-10 text-sm shadow-lg">
    <header class="flex items-start justify-between gap-6 border-b-2 border-crimson pb-5">
      <div>
        <p class="font-serif text-3xl font-bold text-crimson"><?= e($store['name']) ?></p>
        <p class="mt-1 text-xs text-ink-soft"><?= e($store['address']) ?> · <?= e($store['phone']) ?> · <?= e($store['email']) ?></p>
      </div>
      <div class="text-right">
        <p class="text-lg font-bold uppercase tracking-wider"><?= $isInvoice ? 'Hoá đơn bán hàng' : 'Phiếu giao hàng' ?></p>
        <p class="font-mono text-base font-semibold"><?= e($o['order_code']) ?></p>
        <p class="text-xs text-ink-soft">Ngày đặt: <?= e(date('H:i d/m/Y', strtotime($o['created_at']))) ?></p>
      </div>
    </header>

    <?php if ($isInvoice): ?>
      <section class="mt-5 grid grid-cols-2 gap-6">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wider text-ink-soft">Khách hàng</p>
          <p class="mt-1 font-semibold"><?= e($o['customer_name']) ?></p>
          <p><?= e($o['phone']) ?><?= $o['email'] ? ' · ' . e($o['email']) : '' ?></p>
          <p><?= e($o['shipping_address']) ?></p>
        </div>
        <div class="text-right">
          <p class="text-xs font-semibold uppercase tracking-wider text-ink-soft">Thanh toán</p>
          <p class="mt-1"><?= e(Orders::PAYMENT_METHODS[$o['payment_method']]) ?></p>
          <p><?= e(Orders::PAYMENT_STATUSES[$o['payment_status']][0]) ?></p>
        </div>
      </section>
    <?php else: ?>
      <section class="mt-5 grid grid-cols-[1.4fr_1fr] gap-6">
        <div class="border-2 border-ink p-4">
          <p class="text-xs font-semibold uppercase tracking-wider">Người nhận</p>
          <p class="mt-1 text-xl font-bold"><?= e($o['customer_name']) ?></p>
          <p class="text-lg font-semibold"><?= e($o['phone']) ?></p>
          <p class="mt-1 text-base"><?= e($o['shipping_address']) ?></p>
          <?php if ($o['note']): ?><p class="mt-2 italic">Lời nhắn: “<?= e($o['note']) ?>”</p><?php endif; ?>
        </div>
        <div class="flex flex-col justify-center border-2 <?= $collect ? 'border-crimson' : 'border-line' ?> p-4 text-center">
          <p class="text-xs font-semibold uppercase tracking-wider">Thu hộ (COD)</p>
          <p class="mt-1 text-3xl font-bold <?= $collect ? 'text-crimson' : '' ?>"><?= $collect ? money($collect) : '0 ₫' ?></p>
          <p class="mt-1 text-xs text-ink-soft"><?= $collect ? 'Thu tiền khi giao hàng' : 'Đã thanh toán — không thu tiền' ?></p>
        </div>
      </section>
    <?php endif; ?>

    <table class="mt-6 w-full border-collapse text-sm">
      <thead>
        <tr class="border-b-2 border-ink text-left">
          <th class="py-2 pr-2 w-8">#</th>
          <th class="py-2 pr-2">Sản phẩm</th>
          <th class="py-2 px-2 text-center w-12">SL</th>
          <?php if ($isInvoice): ?><th class="py-2 px-2 text-right">Đơn giá</th><th class="py-2 pl-2 text-right">Thành tiền</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($o['items'] as $i => $it): $m = $it['custom_measurements'] ? json_decode($it['custom_measurements'], true) : null; ?>
          <tr class="border-b border-line align-top">
            <td class="py-2 pr-2"><?= $i + 1 ?></td>
            <td class="py-2 pr-2">
              <p class="font-medium"><?= e($it['product_name']) ?></p>
              <p class="text-xs text-ink-soft"><?= e(implode(' · ', array_filter([$it['fabric_name'], $it['color'], $it['size'] === 'CUSTOM' ? 'May đo' : 'Size ' . $it['size']]))) ?></p>
              <?php if ($m): ?><p class="text-xs">Số đo: <?= e(measurements_text($m)) ?><?= !empty($m['note']) ? ' · “' . e($m['note']) . '”' : '' ?></p><?php endif; ?>
            </td>
            <td class="py-2 px-2 text-center font-semibold"><?= (int) $it['quantity'] ?></td>
            <?php if ($isInvoice): ?>
              <td class="py-2 px-2 text-right tabular-nums"><?= money($it['unit_price']) ?><?php if (!empty($it['original_price'])): ?><span class="block text-[11px] text-ink-soft line-through"><?= money($it['original_price']) ?></span><?php endif; ?></td>
              <td class="py-2 pl-2 text-right tabular-nums"><?= money($it['unit_price'] * $it['quantity']) ?></td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($isInvoice): ?>
      <dl class="ml-auto mt-4 w-64 space-y-1 tabular-nums">
        <div class="flex justify-between"><dt>Tạm tính</dt><dd><?= money($o['subtotal']) ?></dd></div>
<?php if ((int) ($o['discount'] ?? 0) > 0): ?>
        <div class="flex justify-between "><dt>Giảm giá<?= $o['coupon_code'] ? ' (' . e($o['coupon_code']) . ')' : '' ?></dt><dd>−<?= money($o['discount']) ?></dd></div>
        <?php endif; ?>
        <div class="flex justify-between"><dt>Phí vận chuyển</dt><dd><?= (int) $o['shipping_fee'] ? money($o['shipping_fee']) : 'Miễn phí' ?></dd></div>
        <div class="flex justify-between border-t-2 border-ink pt-1 text-base font-bold"><dt>Tổng cộng</dt><dd><?= money($o['total']) ?></dd></div>
      </dl>
    <?php else: ?>
      <p class="mt-4 text-xs text-ink-soft">Tổng số sản phẩm: <strong><?= array_sum(array_column($o['items'], 'quantity')) ?></strong>. Vui lòng kiểm tra hàng trước khi nhận.</p>
    <?php endif; ?>

    <footer class="mt-12 grid grid-cols-2 gap-6 text-center text-xs">
      <div><p class="font-semibold"><?= $isInvoice ? 'Người mua hàng' : 'Người nhận hàng' ?></p><p class="text-ink-soft">(Ký, ghi rõ họ tên)</p></div>
      <div><p class="font-semibold"><?= $isInvoice ? 'Người bán hàng' : 'Người giao hàng' ?></p><p class="text-ink-soft">(Ký, ghi rõ họ tên)</p></div>
    </footer>
    <p class="mt-16 text-center font-serif text-sm italic text-ink-soft">Cảm ơn quý khách đã tin chọn <?= e($store['name']) ?>.</p>
  </article>
  <?php endforeach; ?>
</body>
</html>
