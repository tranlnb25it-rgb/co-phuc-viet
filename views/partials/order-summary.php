<?php
/** Checkout sidebar. Re-rendered by /api/cart?view=summary and the coupon endpoints. @var array $cart */
$hasCustom = in_array(true, array_column($cart['lines'], 'custom'), true);
$coupon = $cart['coupon'];
?>
<div class="flex items-center justify-between">
  <h2 class="text-2xl"><?= e(t('checkout.summary')) ?> <span class="text-base font-normal text-ink-soft">(<?= (int) $cart['count'] ?>)</span></h2>
  <button type="button" data-cart-open class="text-xs font-medium text-crimson underline underline-offset-4"><?= e(t('checkout.edit')) ?></button>
</div>

<ul class="mt-5 space-y-4">
  <?php foreach ($cart['lines'] as $line): ?>
    <li class="flex gap-3">
      <div class="relative h-20 w-16 shrink-0">
        <div class="h-full w-full overflow-hidden border border-line bg-silk [&_span]:hidden [&_svg]:h-6 [&_svg]:w-6">
          <?= product_image($line['image'], $line['name'], $line['tint']) ?>
        </div>
        <span class="absolute -right-2 -top-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-ink px-1 text-[10px] font-semibold text-ivory"><?= (int) $line['qty'] ?></span>
      </div>
      <div class="min-w-0 flex-1 text-sm">
        <p class="font-medium leading-snug"><?= e($line['name']) ?></p>
        <p class="text-xs text-ink-soft">
          <?= e(implode(' · ', array_filter([$line['fabric_name'], $line['color_name'],
              $line['custom'] ? t('cart.customMade') : ($line['size'] !== 'FREE' ? 'Size ' . $line['size'] : null)]))) ?>
        </p>
        <?php if (!$line['available']): ?>
          <p class="text-xs text-crimson"><?= e(t('cart.unavailable')) ?></p>
        <?php endif; ?>
      </div>
      <p class="text-right text-sm font-medium tabular-nums"><?= money($line['line_total']) ?>
        <?php if ($line['on_sale']): ?><span class="block text-[11px] font-normal text-ink-soft line-through"><?= money($line['original_unit_price'] * $line['qty']) ?></span><?php endif; ?>
      </p>
    </li>
  <?php endforeach; ?>
</ul>

<!-- Coupon -->
<div class="mt-6 border-t border-line pt-4" data-coupon-box
     data-apply-url="<?= e(url('/api/cart/coupon')) ?>">
  <?php if ($coupon): ?>
    <div class="flex items-start justify-between gap-3 border border-dashed <?= $coupon['ok'] ? 'border-emerald-600 bg-emerald-50' : 'border-crimson/50 bg-crimson/5' ?> px-3 py-2.5 text-sm">
      <div class="min-w-0">
        <p class="font-mono font-semibold tracking-wider <?= $coupon['ok'] ? 'text-emerald-800' : 'text-crimson' ?>"><?= e($coupon['code']) ?></p>
        <p class="text-xs <?= $coupon['ok'] ? 'text-emerald-800' : 'text-crimson' ?>">
          <?= $coupon['ok'] ? e($coupon['description']) : e(t('coupon.notApplied') . ': ' . $coupon['error']) ?>
        </p>
      </div>
      <button type="button" class="shrink-0 text-xs font-medium text-ink-soft underline underline-offset-4 hover:text-crimson" data-coupon-remove><?= e(t('coupon.remove')) ?></button>
    </div>
  <?php else: ?>
    <form class="flex gap-2" data-coupon-form>
      <label class="sr-only" for="coupon-code"><?= e(t('coupon.label')) ?></label>
      <input id="coupon-code" name="code" autocomplete="off" maxlength="40" placeholder="<?= e(t('coupon.placeholder')) ?>"
             class="input py-2! font-mono uppercase placeholder:font-sans placeholder:normal-case aria-[invalid=true]:border-crimson">
      <button class="btn-outline shrink-0 px-4! py-2!"><?= e(t('coupon.apply')) ?></button>
    </form>
    <p class="mt-1 min-h-4 text-xs text-crimson" data-error-for="coupon" role="alert"></p>
  <?php endif; ?>
</div>

<dl class="mt-3 space-y-2 text-sm tabular-nums">
  <div class="flex justify-between"><dt class="text-ink-soft"><?= e(t('cart.subtotal')) ?></dt><dd><?= money($cart['subtotal']) ?></dd></div>
  <?php if ($cart['discount'] > 0): ?>
    <div class="flex justify-between text-emerald-700"><dt><?= e(t('cart.discount')) ?></dt><dd>−<?= money($cart['discount']) ?></dd></div>
  <?php endif; ?>
  <div class="flex justify-between"><dt class="text-ink-soft"><?= e(t('cart.shipping')) ?></dt>
    <dd><?= $cart['shipping'] ? money($cart['shipping']) : '<span class="text-emerald-700">' . e(t('cart.free')) . '</span>' ?></dd></div>
  <div class="flex items-baseline justify-between border-t border-line pt-3">
    <dt class="font-semibold"><?= e(t('cart.total')) ?></dt>
    <dd class="font-serif text-3xl font-semibold text-crimson"><?= money($cart['total']) ?></dd>
  </div>
</dl>
<?php if ($cart['sale_savings'] + $cart['discount'] > 0): ?>
  <p class="mt-2 text-right text-xs text-emerald-700"><?= e(t('cart.saleSavings', ['amount' => money($cart['sale_savings'] + $cart['discount'])])) ?></p>
<?php endif; ?>

<?php if ($hasCustom): ?>
  <p class="mt-4 flex gap-2 border border-gold/40 bg-gold/10 p-3 text-xs leading-relaxed text-ink">
    <?= icon('scissors', 'h-4 w-4 shrink-0 text-crimson') ?> <?= e(t('checkout.customNotice')) ?>
  </p>
<?php endif; ?>
