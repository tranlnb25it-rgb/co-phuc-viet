<?php
/**
 * @var array $cart   Cart::summary()
 * @var array $old    previous input (after a failed classic submit)
 * @var array $errors field => message
 * @var array|null $member signed-in customer  @var array $addresses their address book
 */
use App\Core\{Lang, View};

$fieldLabels  = Lang::raw('checkout.fields');
$placeholders = Lang::raw('checkout.placeholders');

/** Text input with label + error slot (errors are also filled in by Ajax). */
$field = function (string $name, string $type = 'text', string $extra = '') use ($old, $errors, $fieldLabels, $placeholders): string {
    $err = $errors[$name] ?? '';
    return '<label class="block">'
         . '<span class="text-sm font-medium">' . e($fieldLabels[$name]) . '</span>'
         . '<input type="' . $type . '" name="' . $name . '" id="f-' . $name . '" value="' . e($old[$name] ?? '') . '" ' . $extra
         . ' placeholder="' . e($placeholders[$name] ?? '') . '" aria-describedby="err-' . $name . '"' . ($err ? ' aria-invalid="true"' : '')
         . ' class="input mt-1.5 aria-[invalid=true]:border-crimson aria-[invalid=true]:ring-2 aria-[invalid=true]:ring-crimson/20">'
         . '<span id="err-' . $name . '" class="mt-1 block min-h-4 text-xs text-crimson" data-error-for="' . $name . '">' . e($err) . '</span>'
         . '</label>';
};
$step = fn(int $n, string $title) => '<h2 class="flex items-center gap-3 text-2xl"><span class="flex h-8 w-8 items-center justify-center rounded-full border border-gold font-sans text-sm font-semibold text-crimson">' . $n . '</span>' . e($title) . '</h2>';
?>

<section class="border-b border-line bg-silk/60">
  <div class="container-page py-8">
    <nav aria-label="<?= e(t('catalog.breadcrumb')) ?>" class="flex items-center gap-1.5 text-xs text-ink-soft">
      <a href="<?= e(url('/')) ?>" class="hover:text-crimson"><?= e(t('nav.home')) ?></a>
      <?= icon('chevron-right', 'h-3 w-3') ?>
      <span class="text-ink"><?= e(t('checkout.title')) ?></span>
    </nav>
    <h1 class="mt-3 text-4xl sm:text-5xl"><?= e(t('checkout.title')) ?></h1>
  </div>
</section>

<?php if (!$cart['lines']): ?>
  <div class="container-page flex flex-col items-center py-24 text-center">
    <?= lotus('h-14 w-14 text-gold/60') ?>
    <p class="mt-4 font-serif text-3xl"><?= e(t('cart.empty')) ?></p>
    <p class="mt-1 text-ink-soft"><?= e(t('cart.emptyText')) ?></p>
    <a href="<?= e(url('/bo-suu-tap')) ?>" class="btn-primary mt-8"><?= e(t('cart.browse')) ?></a>
  </div>
<?php else: ?>
<div class="container-page grid gap-10 py-10 lg:grid-cols-[1fr_420px] lg:gap-14 lg:py-14">

  <form id="checkout-form" method="post" action="<?= e(url('/thanh-toan')) ?>" novalidate class="space-y-10"
        data-api="<?= e(url('/api/orders')) ?>">
    <?= csrf_field() ?>

    <div class="<?= isset($errors['_cart']) ? '' : 'hidden' ?> flex items-start gap-2 border border-crimson/40 bg-crimson/5 p-4 text-sm text-crimson" data-checkout-error role="alert">
      <?= icon('alert', 'mt-0.5 h-4 w-4 shrink-0') ?> <span><?= e($errors['_cart'] ?? '') ?></span>
    </div>

    <?php if ($member): ?>
      <p class="flex items-center gap-2 border border-line bg-white/60 px-4 py-3 text-sm">
        <?= icon('user', 'h-4 w-4 text-crimson') ?> <?= e(t('checkout.signedInAs', ['email' => $member['email']])) ?>
      </p>
    <?php else: ?>
      <p class="flex items-center gap-2 border border-gold/50 bg-gold/10 px-4 py-3 text-sm">
        <?= icon('user', 'h-4 w-4 shrink-0 text-crimson') ?>
        <span><?= strtr(e(t('checkout.loginPrompt')), [':link' => '<a href="' . e(url('/dang-nhap', ['next' => '/thanh-toan'])) . '" class="font-medium text-crimson underline underline-offset-2">' . e(t('checkout.loginLink')) . '</a>']) ?></span>
      </p>
    <?php endif; ?>

    <?php if ($addresses): ?>
      <section class="space-y-3" data-address-picker>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gold-dark"><?= e(t('checkout.savedAddresses')) ?></p>
        <div class="grid gap-2 sm:grid-cols-2">
          <?php foreach ($addresses as $a):
            $vals = ['customer_name' => $a['recipient_name'], 'phone' => $a['phone'], 'address' => $a['address'], 'city' => $a['city']]; ?>
            <label class="flex cursor-pointer items-start gap-3 border border-line bg-white/60 p-3 text-sm transition hover:border-gold has-[:checked]:border-crimson has-[:checked]:bg-crimson/5">
              <input type="radio" name="address_id" value="<?= (int) $a['id'] ?>" class="mt-1 accent-crimson" data-fill="<?= e(json_encode($vals, JSON_UNESCAPED_UNICODE)) ?>"
                     <?= (int) ($old['address_id'] ?? 0) === (int) $a['id'] ? 'checked' : '' ?>>
              <span class="min-w-0">
                <span class="block font-medium"><?= e($a['recipient_name']) ?> · <?= e($a['phone']) ?>
                  <?php if ($a['is_default']): ?><span class="ml-1 rounded-sm bg-crimson px-1.5 text-[10px] font-semibold text-ivory"><?= e(t('account.addresses.default')) ?></span><?php endif; ?></span>
                <span class="block truncate text-xs text-ink-soft"><?= e($a['address']) ?>, <?= e($a['city']) ?></span>
              </span>
            </label>
          <?php endforeach; ?>
          <label class="flex cursor-pointer items-center gap-3 border border-dashed border-line p-3 text-sm transition hover:border-gold has-[:checked]:border-crimson">
            <input type="radio" name="address_id" value="" class="accent-crimson" data-fill="" <?= empty($old['address_id']) ? 'checked' : '' ?>>
            <?= icon('plus', 'h-4 w-4 text-crimson') ?> <?= e(t('checkout.newAddress')) ?>
          </label>
        </div>
      </section>
    <?php endif; ?>

    <section class="space-y-5">
      <?= $step(1, t('checkout.contact')) ?>
      <div class="grid gap-x-5 sm:grid-cols-2">
        <?= $field('customer_name', 'text', 'autocomplete="name" required maxlength="120"') ?>
        <?= $field('phone', 'tel', 'autocomplete="tel" inputmode="tel" required maxlength="20"') ?>
        <div class="sm:col-span-2"><?= $field('email', 'email', 'autocomplete="email" maxlength="190"') ?></div>
      </div>
    </section>

    <section class="space-y-5">
      <?= $step(2, t('checkout.shipping')) ?>
      <div class="grid gap-x-5 sm:grid-cols-[2fr_1fr]">
        <?= $field('address', 'text', 'autocomplete="street-address" required maxlength="180"') ?>
        <?= $field('city', 'text', 'autocomplete="address-level1" required maxlength="70"') ?>
      </div>
      <label class="block">
        <span class="text-sm font-medium"><?= e($fieldLabels['note']) ?></span>
        <textarea name="note" rows="3" maxlength="500" class="input mt-1.5 resize-y" placeholder="<?= e($placeholders['note']) ?>"><?= e($old['note'] ?? '') ?></textarea>
      </label>
      <?php if ($member): ?>
        <label class="<?= !empty($old['address_id']) ? 'hidden' : 'flex' ?> items-center gap-2 text-sm" data-save-address>
          <input type="checkbox" name="save_address" value="1" class="h-4 w-4 accent-crimson" <?= !$addresses || !empty($old['save_address']) ? 'checked' : '' ?>>
          <?= e(t('checkout.saveAddress')) ?>
        </label>
      <?php endif; ?>
    </section>

    <section class="space-y-5">
      <?= $step(3, t('checkout.payment')) ?>
      <div class="grid gap-3" role="radiogroup">
        <?php foreach (['COD' => 'banknote', 'BANK_TRANSFER' => 'landmark'] as $method => $ic): $m = Lang::raw("checkout.methods.$method"); ?>
          <label class="group flex cursor-pointer items-start gap-4 border border-line bg-white/60 p-4 transition hover:border-gold has-[:checked]:border-crimson has-[:checked]:bg-crimson/5 has-[:checked]:ring-1 has-[:checked]:ring-crimson">
            <input type="radio" name="payment_method" value="<?= $method ?>" class="mt-1 h-4 w-4 accent-crimson" <?= ($old['payment_method'] ?? 'COD') === $method ? 'checked' : '' ?>>
            <span class="flex-1">
              <span class="flex items-center gap-2 font-medium"><?= icon($ic, 'h-5 w-5 text-crimson') ?> <?= e($m['title']) ?></span>
              <span class="mt-1 block text-sm text-ink-soft"><?= e($m['text']) ?></span>
              <?php if ($method === 'BANK_TRANSFER'): $bank = bank_info(); ?>
                <span class="mt-3 hidden gap-4 border-t border-line pt-3 text-xs text-ink-soft group-has-[:checked]:flex">
                  <span class="text-gold-dark"><?= qr_placeholder('preview', 'h-16 w-16') ?></span>
                  <span class="space-y-0.5">
                    <span class="block"><?= e(t('order.bank.bank')) ?>: <strong class="text-ink"><?= e($bank['name']) ?></strong></span>
                    <span class="block"><?= e(t('order.bank.account')) ?>: <strong class="text-ink"><?= e($bank['account']) ?></strong></span>
                    <span class="block"><?= e(t('order.bank.holder')) ?>: <strong class="text-ink"><?= e($bank['holder']) ?></strong></span>
                  </span>
                </span>
              <?php endif; ?>
            </span>
          </label>
        <?php endforeach; ?>
      </div>
      <span class="block text-xs text-crimson" data-error-for="payment_method"><?= e($errors['payment_method'] ?? '') ?></span>
    </section>

    <div class="border-t border-line pt-6">
      <button type="submit" class="btn-primary h-14 w-full text-base" data-place-order
              data-label="<?= e(t('checkout.place')) ?>" data-busy-label="<?= e(t('checkout.placing')) ?>"
              <?= $cart['all_available'] ? '' : 'disabled' ?>>
        <?= icon('lock', 'h-4 w-4') ?> <span><?= e(t('checkout.place')) ?></span> · <span data-order-total><?= money($cart['total']) ?></span>
      </button>
      <p class="mt-3 flex items-center justify-center gap-1.5 text-xs text-ink-soft"><?= icon('lock', 'h-3.5 w-3.5') ?> <?= e(t('checkout.secure')) ?></p>
    </div>
  </form>

  <aside class="lg:sticky lg:top-28 lg:self-start">
    <div class="border border-line bg-silk/60 p-6" data-order-summary>
      <?= View::partial('partials/order-summary', ['cart' => $cart]) ?>
    </div>
  </aside>
</div>
<?php endif; ?>
