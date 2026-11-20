<?php
/** @var array $order OrderService::find() */
use App\Core\{Lang, View};

$isBank = $order['payment_method'] === 'BANK_TRANSFER';
$method = Lang::raw('checkout.methods.' . $order['payment_method']);
$bank = bank_info();
$copyRow = fn(string $label, string $value, bool $strong = false) =>
    '<div class="flex items-center justify-between gap-3 border-b border-line py-2.5 last:border-0">'
    . '<dt class="text-ink-soft">' . e($label) . '</dt>'
    . '<dd class="flex items-center gap-2 text-right ' . ($strong ? 'font-semibold text-crimson' : 'font-medium') . '">' . e($value)
    . '<button type="button" data-copy="' . e($value) . '" data-copied="' . e(t('order.bank.copied')) . '" class="rounded-sm border border-line px-1.5 py-0.5 text-[11px] font-normal text-ink-soft transition hover:border-gold hover:text-crimson" aria-label="' . e(t('order.bank.copy') . ' ' . $label) . '">'
    . e(t('order.bank.copy')) . '</button></dd></div>';
?>
<div class="container-page max-w-4xl py-14 lg:py-20">
  <div class="text-center">
    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-crimson text-ivory shadow-lg"><?= icon('check', 'h-8 w-8') ?></span>
    <h1 class="mt-6 text-4xl sm:text-5xl"><?= e(t('order.thanks')) ?></h1>
    <p class="mt-3 text-ink-soft"><?= e(t('order.text', ['phone' => $order['phone']])) ?></p>
    <p class="mt-6 inline-flex items-center gap-3 border border-gold/60 bg-silk px-5 py-3">
      <span class="text-xs uppercase tracking-[0.2em] text-ink-soft"><?= e(t('order.codeLabel')) ?></span>
      <span class="font-mono text-lg font-semibold tracking-wider text-crimson"><?= e($order['order_code']) ?></span>
    </p>
  </div>

  <?= View::partial('partials/motif-divider', ['class' => 'my-12']) ?>

  <?php if ($isBank): ?>
    <section class="mb-10 grid gap-8 border border-gold/50 bg-silk/60 p-6 sm:grid-cols-[auto_1fr] sm:p-8">
      <div class="text-center">
        <div class="palette-light inline-block border border-line bg-white p-3 text-ink"><?= qr_placeholder($order['order_code'], 'h-48 w-48') ?></div>
        <p class="mx-auto mt-2 max-w-48 text-[11px] leading-snug text-ink-soft"><?= e(t('order.bank.qrNote')) ?></p>
      </div>
      <div>
        <h2 class="text-2xl"><?= e(t('order.bank.title')) ?></h2>
        <p class="mt-1 text-sm text-ink-soft"><?= e(t('order.bank.text')) ?></p>
        <dl class="mt-4 text-sm">
          <?= $copyRow(t('order.bank.bank'), $bank['name']) ?>
          <?= $copyRow(t('order.bank.account'), $bank['account']) ?>
          <?= $copyRow(t('order.bank.holder'), $bank['holder']) ?>
          <?= $copyRow(t('order.bank.amount'), money($order['total']), true) ?>
          <?= $copyRow(t('order.bank.content'), $order['order_code'], true) ?>
        </dl>
      </div>
    </section>
  <?php endif; ?>

  <div class="grid gap-10 md:grid-cols-[1.4fr_1fr]">
    <section>
      <h2 class="text-2xl"><?= e(t('order.items')) ?></h2>
      <ul class="mt-4 divide-y divide-line border-y border-line">
        <?php foreach ($order['items'] as $it):
          $m = $it['custom_measurements'] ? json_decode($it['custom_measurements'], true) : null; ?>
          <li class="flex justify-between gap-4 py-4 text-sm">
            <div>
              <p class="font-medium"><?= e($it['product_name']) ?> <span class="text-ink-soft">× <?= (int) $it['quantity'] ?></span></p>
              <p class="text-xs text-ink-soft">
                <?= e(implode(' · ', array_filter([$it['fabric_name'], $it['color'],
                    $it['size'] === 'CUSTOM' ? t('cart.customMade') : ($it['size'] !== 'FREE' ? 'Size ' . $it['size'] : null)]))) ?>
              </p>
              <?php if ($m): ?><p class="mt-0.5 text-[11px] text-ink-soft"><?= e(measurements_text($m)) ?></p><?php endif; ?>
            </div>
            <p class="font-medium"><?= money($it['unit_price'] * $it['quantity']) ?></p>
          </li>
        <?php endforeach; ?>
      </ul>
      <dl class="mt-4 space-y-1.5 text-sm">
        <div class="flex justify-between"><dt class="text-ink-soft"><?= e(t('cart.subtotal')) ?></dt><dd><?= money($order['subtotal']) ?></dd></div>
<?php if ((int) ($order['discount'] ?? 0) > 0): ?>
        <div class="flex justify-between text-emerald-700"><dt><?= e(t('cart.discount')) ?><?= $order['coupon_code'] ? ' (' . e($order['coupon_code']) . ')' : '' ?></dt><dd>−<?= money($order['discount']) ?></dd></div>
        <?php endif; ?>
        <div class="flex justify-between"><dt class="text-ink-soft"><?= e(t('cart.shipping')) ?></dt>
          <dd><?= (int) $order['shipping_fee'] ? money($order['shipping_fee']) : e(t('cart.free')) ?></dd></div>
        <div class="flex justify-between border-t border-line pt-2 text-base font-semibold"><dt><?= e(t('cart.total')) ?></dt>
          <dd class="text-crimson"><?= money($order['total']) ?></dd></div>
      </dl>
    </section>

    <section class="space-y-6 text-sm">
      <div>
        <h3 class="text-xl"><?= e(t('order.shipTo')) ?></h3>
        <p class="mt-2 font-medium"><?= e($order['customer_name']) ?></p>
        <p class="text-ink-soft"><?= e($order['phone']) ?><?= $order['email'] ? ' · ' . e($order['email']) : '' ?></p>
        <p class="text-ink-soft"><?= e($order['shipping_address']) ?></p>
        <?php if ($order['note']): ?><p class="mt-2 italic text-ink-soft">“<?= e($order['note']) ?>”</p><?php endif; ?>
      </div>
      <div>
        <h3 class="text-xl"><?= e(t('order.payment')) ?></h3>
        <p class="mt-2"><?= e($method['title']) ?></p>
      </div>
      <?php if ((int) $order['user_id'] && (int) $order['user_id'] === App\Support\Customer::id()): ?>
        <a href="<?= e(url('/tai-khoan/don-hang/' . $order['order_code'])) ?>" class="btn-primary w-full"><?= icon('package', 'h-4 w-4') ?> <?= e(t('member.viewOrder')) ?></a>
      <?php elseif (!App\Support\Customer::user()): ?>
        <div class="border border-gold/50 bg-silk/70 p-5">
          <p class="flex items-center gap-2 font-serif text-xl font-semibold"><?= icon('user', 'h-5 w-5 text-crimson') ?> <?= e(t('member.createTitle')) ?></p>
          <p class="mt-1 text-ink-soft"><?= e(t('member.createText')) ?></p>
          <a href="<?= e(url('/dang-ky', ['next' => '/tai-khoan/don-hang/' . $order['order_code']])) ?>" class="btn-primary mt-4 w-full"><?= e(t('member.create')) ?></a>
          <a href="<?= e(url('/tra-cuu-don-hang', ['code' => $order['order_code']])) ?>" class="mt-2 block text-center text-xs text-crimson underline underline-offset-2"><?= e(t('member.track')) ?></a>
        </div>
      <?php endif; ?>
      <a href="<?= e(url('/bo-suu-tap')) ?>" class="btn-outline w-full"><?= e(t('order.continue')) ?></a>
    </section>
  </div>
</div>
