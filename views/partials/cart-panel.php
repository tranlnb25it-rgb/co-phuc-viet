<?php
/**
 * Contents of the slide-over cart. Rendered in the layout and re-rendered by every /api/cart call.
 * @var array $cart Cart::summary()
 */
$stepBtn = 'flex h-8 w-8 items-center justify-center text-ink-soft transition hover:text-crimson disabled:opacity-30 disabled:hover:text-ink-soft';
$pct = $cart['subtotal'] > 0 ? min(100, (int) round($cart['subtotal'] / $cart['free_shipping_from'] * 100)) : 0;
?>
<div class="flex items-center justify-between border-b border-line px-5 py-4">
  <h2 class="font-serif text-2xl font-semibold" id="cart-title">
    <?= e(t('cart.title')) ?> <span class="text-base font-normal text-ink-soft">(<?= (int) $cart['count'] ?>)</span>
  </h2>
  <button type="button" data-cart-close class="rounded-full p-1.5 transition hover:bg-silk" aria-label="<?= e(t('nav.close')) ?>"><?= icon('x') ?></button>
</div>

<?php if (!$cart['lines']): ?>
  <div class="flex flex-1 flex-col items-center justify-center px-8 text-center">
    <?= lotus('h-14 w-14 text-gold/60') ?>
    <p class="mt-4 font-serif text-2xl"><?= e(t('cart.empty')) ?></p>
    <p class="mt-1 text-sm text-ink-soft"><?= e(t('cart.emptyText')) ?></p>
    <a href="<?= e(url('/bo-suu-tap')) ?>" class="btn-primary mt-6"><?= e(t('cart.browse')) ?></a>
  </div>
<?php else: ?>
  <!-- Free-shipping progress -->
  <div class="border-b border-line bg-silk/60 px-5 py-3 text-xs">
    <p class="flex items-center gap-1.5 <?= $cart['free_shipping_remaining'] ? 'text-ink-soft' : 'font-medium text-emerald-700' ?>">
      <?= icon($cart['free_shipping_remaining'] ? 'truck' : 'check', 'h-4 w-4') ?>
      <?= $cart['free_shipping_remaining']
          ? e(t('cart.freeShipLeft', ['amount' => money($cart['free_shipping_remaining'])]))
          : e(t('cart.freeShipOk')) ?>
    </p>
    <div class="mt-2 h-1 overflow-hidden rounded-full bg-line">
      <div class="h-full rounded-full bg-gradient-to-r from-gold to-crimson transition-all duration-500" style="width: <?= $pct ?>%"></div>
    </div>
  </div>

  <ul class="flex-1 divide-y divide-line overflow-y-auto px-5" data-cart-lines>
    <?php foreach ($cart['lines'] as $line): ?>
      <li class="flex gap-4 py-5" data-line="<?= e($line['key']) ?>">
        <a href="<?= e($line['url']) ?>" class="block h-28 w-22 shrink-0 overflow-hidden border border-line bg-silk [&_span]:hidden [&_svg]:h-7 [&_svg]:w-7">
          <?= product_image($line['image'], $line['name'], $line['tint']) ?>
        </a>
        <div class="flex min-w-0 flex-1 flex-col">
          <div class="flex items-start justify-between gap-2">
            <a href="<?= e($line['url']) ?>" class="font-serif text-lg leading-tight hover:text-crimson"><?= e($line['name']) ?></a>
            <button type="button" data-cart-remove="<?= e($line['key']) ?>" class="shrink-0 p-1 text-ink-soft transition hover:text-crimson" aria-label="<?= e(t('cart.remove')) ?>">
              <?= icon('trash', 'h-4 w-4') ?>
            </button>
          </div>
          <p class="mt-1 text-xs text-ink-soft">
            <?= e(implode(' · ', array_filter([$line['fabric_name'], $line['color_name']]))) ?>
            <?php if ($line['custom']): ?>
              · <span class="font-medium text-crimson"><?= e(t('cart.customMade')) ?></span>
            <?php elseif ($line['size'] !== 'FREE'): ?>
              · Size <?= e($line['size']) ?>
            <?php endif; ?>
          </p>
          <?php if ($line['custom'] && $line['measurements']): ?>
            <p class="mt-1 text-[11px] leading-snug text-ink-soft"><?= e(measurements_text($line['measurements'])) ?></p>
          <?php endif; ?>
          <?php if (!$line['available']): ?>
            <p class="mt-1 flex items-center gap-1 text-xs text-crimson"><?= icon('alert', 'h-3.5 w-3.5') ?> <?= e(t('cart.unavailable')) ?></p>
          <?php endif; ?>

          <div class="mt-auto flex items-center justify-between pt-3">
            <div class="flex items-center border border-line">
              <button type="button" class="<?= $stepBtn ?>" data-cart-qty="<?= e($line['key']) ?>" data-qty-value="<?= $line['qty'] - 1 ?>"
                      aria-label="<?= e(t('pdp.decrease')) ?>" <?= $line['qty'] <= 1 ? 'disabled' : '' ?>><?= icon('minus', 'h-3.5 w-3.5') ?></button>
              <span class="w-8 text-center text-sm" aria-live="polite"><?= (int) $line['qty'] ?></span>
              <button type="button" class="<?= $stepBtn ?>" data-cart-qty="<?= e($line['key']) ?>" data-qty-value="<?= $line['qty'] + 1 ?>"
                      aria-label="<?= e(t('pdp.increase')) ?>" <?= $line['qty'] >= $line['max_qty'] ? 'disabled' : '' ?>><?= icon('plus', 'h-3.5 w-3.5') ?></button>
            </div>
            <p class="text-right text-sm font-semibold text-crimson"><?= money($line['line_total']) ?>
              <?php if ($line['on_sale']): ?><span class="block text-[11px] font-normal text-ink-soft line-through"><?= money($line['original_unit_price'] * $line['qty']) ?></span><?php endif; ?>
            </p>
          </div>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>

  <div class="border-t border-line bg-ivory px-5 py-5">
    <dl class="space-y-1.5 text-sm">
      <div class="flex justify-between"><dt class="text-ink-soft"><?= e(t('cart.subtotal')) ?></dt><dd><?= money($cart['subtotal']) ?></dd></div>
      <?php if ($cart['discount'] > 0): ?>
        <div class="flex justify-between text-emerald-700"><dt><?= e(t('cart.discount')) ?> <span class="font-mono text-xs">(<?= e($cart['coupon']['code']) ?>)</span></dt><dd>−<?= money($cart['discount']) ?></dd></div>
      <?php endif; ?>
      <div class="flex justify-between"><dt class="text-ink-soft"><?= e(t('cart.shipping')) ?></dt>
        <dd><?= $cart['shipping'] ? money($cart['shipping']) : '<span class="text-emerald-700">' . e(t('cart.free')) . '</span>' ?></dd></div>
      <div class="flex justify-between border-t border-line pt-2 text-base font-semibold">
        <dt><?= e(t('cart.total')) ?></dt><dd class="text-crimson"><?= money($cart['total']) ?></dd></div>
    </dl>
    <?php if ($cart['sale_savings'] + $cart['discount'] > 0): ?>
      <p class="mt-2 text-center text-xs text-emerald-700"><?= e(t('cart.saleSavings', ['amount' => money($cart['sale_savings'] + $cart['discount'])])) ?></p>
    <?php endif; ?>
    <a href="<?= e(url('/thanh-toan')) ?>" class="btn-primary mt-4 w-full <?= $cart['all_available'] ? '' : 'pointer-events-none opacity-50' ?>"
       <?= $cart['all_available'] ? '' : 'aria-disabled="true"' ?>>
      <?= icon('lock', 'h-4 w-4') ?> <?= e(t('cart.checkout')) ?>
    </a>
    <button type="button" data-cart-close class="mt-2 w-full py-2 text-sm text-ink-soft underline-offset-4 hover:text-crimson hover:underline"><?= e(t('cart.continue')) ?></button>
  </div>
<?php endif; ?>
