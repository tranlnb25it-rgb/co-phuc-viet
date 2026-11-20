<?php
/**
 * Customer-facing order detail: progress tracker, items, totals, delivery and payment.
 * Shared by "My account → order" and the public order lookup.
 * @var array $order AccountService::order()/lookup()   @var bool $public
 */
use App\Core\Lang;
use App\Services\AdminOrderService;

$public ??= false;
$status = $order['status'];
$cancelled = $status === 'cancelled';
$steps = array_values(array_filter(['pending', 'confirmed', $order['has_custom'] ? 'tailoring' : null, 'shipping', 'completed']));
$current = array_search($status, $steps, true);
if ($current === false) $current = -1;
$stepIcon = ['pending' => 'shopping-bag', 'confirmed' => 'check', 'tailoring' => 'scissors', 'shipping' => 'truck', 'completed' => 'award'];
$fmt = fn(?string $d) => $d ? date('H:i · d/m/Y', strtotime($d)) : '';
$isBank = $order['payment_method'] === 'BANK_TRANSFER';
$method = Lang::raw('checkout.methods.' . $order['payment_method']);
// account view of a completed order: offer "write a review" for items not reviewed yet
$reviewed = !$public && $status === 'completed' && App\Support\Customer::id() ? App\Services\Reviews::reviewedIds(App\Support\Customer::id()) : null;
?>
<div class="space-y-8" data-order="<?= e($order['order_code']) ?>">
  <!-- Header -->
  <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
      <p class="text-xs uppercase tracking-[0.2em] text-ink-soft"><?= e(t('order.codeLabel')) ?></p>
      <p class="font-mono text-2xl font-semibold tracking-wider text-crimson"><?= e($order['order_code']) ?></p>
      <p class="mt-1 text-sm text-ink-soft"><?= e(t('account.orders.placed', ['date' => date('H:i d/m/Y', strtotime($order['created_at']))])) ?></p>
    </div>
    <div class="flex flex-wrap gap-2 text-xs font-semibold">
      <span class="rounded-full px-3 py-1 <?= AdminOrderService::STATUSES[$status][1] ?>"><?= e(t('account.status.' . $status)) ?></span>
      <span class="rounded-full px-3 py-1 <?= AdminOrderService::PAYMENT_STATUSES[$order['payment_status']][1] ?>"><?= e(t('account.payment.' . $order['payment_status'])) ?></span>
    </div>
  </div>

  <!-- Progress -->
  <?php if ($cancelled): ?>
    <div class="flex items-center gap-3 border border-crimson/30 bg-crimson/5 p-4 text-sm text-crimson" role="status">
      <?= icon('x', 'h-5 w-5 shrink-0') ?>
      <span><strong><?= e(t('account.orders.cancelledBanner')) ?></strong>
        <?php if (isset($order['reached']['cancelled'])): ?> · <?= e($fmt($order['reached']['cancelled'])) ?><?php endif; ?></span>
    </div>
  <?php else: ?>
    <section aria-label="<?= e(t('account.orders.progress')) ?>" class="border border-line bg-white/60 p-5 sm:p-6">
      <ol class="grid gap-4 sm:flex sm:items-start sm:gap-0">
        <?php foreach ($steps as $i => $st): $done = $i <= $current; $isCur = $i === $current; ?>
          <li class="relative flex items-center gap-3 sm:flex-1 sm:flex-col sm:text-center" <?= $isCur ? 'aria-current="step"' : '' ?>>
            <?php if ($i > 0): ?>
              <span class="absolute right-1/2 top-5 hidden h-0.5 w-full -translate-y-1/2 sm:block <?= $done ? 'bg-crimson' : 'bg-line' ?>" aria-hidden="true"></span>
            <?php endif; ?>
            <span class="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 transition
                         <?= $done ? 'border-crimson bg-crimson text-ivory' : 'border-line bg-ivory text-ink-soft/60' ?> <?= $isCur ? 'ring-4 ring-crimson/15' : '' ?>">
              <?= icon($stepIcon[$st], 'h-4 w-4') ?>
            </span>
            <span class="sm:mt-2">
              <span class="block text-sm <?= $done ? 'font-semibold text-ink' : 'text-ink-soft' ?>"><?= e(t('account.status.' . $st)) ?></span>
              <?php if ($done && isset($order['reached'][$st])): ?>
                <span class="block text-[11px] text-ink-soft"><?= e($fmt($order['reached'][$st])) ?></span>
              <?php endif; ?>
            </span>
          </li>
        <?php endforeach; ?>
      </ol>
      <p class="mt-5 border-t border-line pt-3 text-xs text-ink-soft"><?= e(t('account.orders.updated', ['date' => $fmt($order['updated_at'])])) ?></p>
    </section>
  <?php endif; ?>

  <?php if ($isBank && $order['payment_status'] === 'pending' && !$cancelled): $bank = bank_info(); ?>
    <section class="flex flex-col gap-4 border border-gold/50 bg-silk/60 p-5 sm:flex-row sm:items-center">
      <div class="palette-light shrink-0 border border-line bg-white p-2 text-ink"><?= qr_placeholder($order['order_code'], 'h-24 w-24') ?></div>
      <div class="text-sm">
        <p class="font-serif text-xl font-semibold"><?= e(t('account.orders.awaitingTransfer')) ?></p>
        <p class="mt-1 text-ink-soft"><?= e($bank['name']) ?> · <strong class="text-ink"><?= e($bank['account']) ?></strong> · <?= e($bank['holder']) ?></p>
        <p class="mt-1"><?= e(t('order.bank.amount')) ?>: <strong class="text-crimson"><?= money($order['total']) ?></strong>
          · <?= e(t('order.bank.content')) ?>: <strong class="font-mono text-crimson"><?= e($order['order_code']) ?></strong>
          <button type="button" data-copy="<?= e($order['order_code']) ?>" data-copied="<?= e(t('order.bank.copied')) ?>" class="ml-1 rounded-sm border border-line px-1.5 py-0.5 text-[11px] text-ink-soft hover:border-gold hover:text-crimson"><?= e(t('order.bank.copy')) ?></button></p>
      </div>
    </section>
  <?php endif; ?>

  <div class="grid gap-8 lg:grid-cols-[1.5fr_1fr]">
    <!-- Items + totals -->
    <section>
      <h3 class="text-2xl"><?= e(t('order.items')) ?></h3>
      <ul class="mt-3 divide-y divide-line border-y border-line">
        <?php foreach ($order['items'] as $it):
          $m = $it['custom_measurements'] ? json_decode($it['custom_measurements'], true) : null;
          $link = $it['slug'] && $it['product_active'] ? url('/san-pham/' . $it['slug']) : null; ?>
          <li class="flex gap-4 py-4 text-sm">
            <div class="h-20 w-16 shrink-0 overflow-hidden border border-line bg-silk [&_span]:hidden [&_svg]:h-6 [&_svg]:w-6">
              <?= product_image($it['image'], $it['product_name'], $it['tint']) ?>
            </div>
            <div class="min-w-0 flex-1">
              <p class="font-medium">
                <?php if ($link): ?><a href="<?= e($link) ?>" class="hover:text-crimson"><?= e($it['product_name']) ?></a><?php else: ?><?= e($it['product_name']) ?><?php endif; ?>
                <span class="text-ink-soft">× <?= (int) $it['quantity'] ?></span>
              </p>
              <p class="text-xs text-ink-soft">
                <?= e(implode(' · ', array_filter([$it['fabric_name'], $it['color'],
                    $it['size'] === 'CUSTOM' ? t('cart.customMade') : ($it['size'] !== 'FREE' ? 'Size ' . $it['size'] : null)]))) ?>
              </p>
              <?php if ($m): ?><p class="mt-0.5 text-[11px] text-ink-soft"><?= e(measurements_text($m)) ?></p><?php endif; ?>
              <?php if ($reviewed !== null && $link && !in_array((int) $it['product_id'], $reviewed, true)): ?>
                <a href="<?= e($link) ?>#write-review" class="mt-1.5 inline-flex items-center gap-1 text-xs font-medium text-crimson hover:underline"><?= icon('star', 'h-3.5 w-3.5') ?> <?= e(t('reviews.write')) ?></a>
              <?php endif; ?>
            </div>
            <div class="text-right">
              <p class="font-medium"><?= money($it['unit_price'] * $it['quantity']) ?></p>
              <?php if ($it['original_price']): ?><p class="text-xs text-ink-soft line-through"><?= money($it['original_price'] * $it['quantity']) ?></p><?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
      <dl class="mt-4 space-y-1.5 text-sm">
        <div class="flex justify-between"><dt class="text-ink-soft"><?= e(t('cart.subtotal')) ?></dt><dd><?= money($order['subtotal']) ?></dd></div>
        <?php if ((int) $order['discount'] > 0): ?>
          <div class="flex justify-between text-emerald-700"><dt><?= e(t('cart.discount')) ?><?= $order['coupon_code'] ? ' (' . e($order['coupon_code']) . ')' : '' ?></dt><dd>−<?= money($order['discount']) ?></dd></div>
        <?php endif; ?>
        <div class="flex justify-between"><dt class="text-ink-soft"><?= e(t('cart.shipping')) ?></dt>
          <dd><?= (int) $order['shipping_fee'] ? money($order['shipping_fee']) : e(t('cart.free')) ?></dd></div>
        <div class="flex justify-between border-t border-line pt-2 text-base font-semibold"><dt><?= e(t('cart.total')) ?></dt>
          <dd class="text-crimson"><?= money($order['total']) ?></dd></div>
      </dl>
    </section>

    <!-- Delivery + payment -->
    <section class="space-y-6 text-sm">
      <div class="border border-line bg-silk/50 p-5">
        <h3 class="flex items-center gap-2 text-xl"><?= icon('map-pin', 'h-4 w-4 text-crimson') ?> <?= e(t('order.shipTo')) ?></h3>
        <p class="mt-2 font-medium"><?= e($order['customer_name']) ?></p>
        <p class="text-ink-soft"><?= e($order['phone']) ?><?= !$public && $order['email'] ? ' · ' . e($order['email']) : '' ?></p>
        <p class="text-ink-soft"><?= e($order['shipping_address']) ?></p>
        <?php if ($order['note']): ?><p class="mt-2 italic text-ink-soft">“<?= e($order['note']) ?>”</p><?php endif; ?>
      </div>
      <div class="border border-line bg-silk/50 p-5">
        <h3 class="flex items-center gap-2 text-xl"><?= icon($isBank ? 'landmark' : 'banknote', 'h-4 w-4 text-crimson') ?> <?= e(t('order.payment')) ?></h3>
        <p class="mt-2"><?= e($method['title']) ?></p>
        <?php if ($order['paid_at'] && $order['payment_status'] === 'paid'): ?>
          <p class="text-emerald-700"><?= e(t('account.orders.paidAt', ['date' => $fmt($order['paid_at'])])) ?></p>
        <?php endif; ?>
      </div>
      <p class="text-xs text-ink-soft"><?= e(t('account.orders.help', ['phone' => store_info()['phone']])) ?></p>
    </section>
  </div>
</div>
