<?php
/**
 * "My account" frame: side menu + the current section. The menu links swap the section over Ajax.
 * @var string $body rendered section  @var string $section active menu key
 */
use App\Support\Customer;

$user = Customer::user();
$menu = [
    'overview'     => ['/tai-khoan',          'layout'],
    'orders'       => ['/tai-khoan/don-hang', 'package'],
    'addresses'    => ['/tai-khoan/dia-chi',  'map-pin'],
    'measurements' => ['/tai-khoan/so-do',    'ruler'],
    'reviews'      => ['/tai-khoan/danh-gia', 'star'],
    'wishlist'     => ['/yeu-thich',          'heart'],
    'profile'      => ['/tai-khoan/thong-tin','user'],
];
$initial = mb_strtoupper(mb_substr(trim(strrchr(' ' . $user['name'], ' ')), 0, 1));
?>
<section class="border-b border-line bg-silk/60">
  <div class="container-page flex flex-wrap items-center justify-between gap-4 py-8">
    <div class="flex items-center gap-4">
      <span class="flex h-14 w-14 items-center justify-center rounded-full border-2 border-gold bg-crimson font-serif text-2xl font-semibold text-ivory"><?= e($initial) ?></span>
      <div>
        <p class="eyebrow"><?= e(t('account.title')) ?></p>
        <h1 class="text-3xl sm:text-4xl" data-account-name><?= e(t('account.hello', ['name' => $user['name']])) ?></h1>
        <p class="text-xs text-ink-soft"><?= e(t('account.memberSince', ['date' => date('d/m/Y', strtotime($user['created_at']))])) ?></p>
      </div>
    </div>
    <form method="post" action="<?= e(url('/dang-xuat')) ?>">
      <?= csrf_field() ?>
      <button class="inline-flex items-center gap-2 text-sm text-ink-soft transition hover:text-crimson"><?= icon('log-out', 'h-4 w-4') ?> <?= e(t('auth.logout')) ?></button>
    </form>
  </div>
</section>

<div class="container-page grid gap-8 py-8 lg:grid-cols-[230px_1fr] lg:gap-12 lg:py-12">
  <nav aria-label="<?= e(t('account.title')) ?>" class="-mx-4 overflow-x-auto px-4 lg:mx-0 lg:overflow-visible lg:px-0" data-account-nav>
    <ul class="flex gap-2 lg:sticky lg:top-28 lg:flex-col lg:gap-1">
      <?php foreach ($menu as $key => [$href, $ic]): $on = $key === $section; ?>
        <li>
          <a href="<?= e(url($href)) ?>" data-account-link="<?= e($key) ?>" <?= $key === 'wishlist' ? 'data-full-load' : '' ?>
             class="flex shrink-0 items-center gap-3 whitespace-nowrap rounded-sm border px-3 py-2.5 text-sm transition
                    <?= $on ? 'border-crimson bg-crimson text-ivory' : 'border-line bg-white/60 hover:border-gold hover:text-crimson lg:border-transparent lg:bg-transparent' ?>"
             <?= $on ? 'aria-current="page"' : '' ?>>
            <?= icon($ic, 'h-4 w-4') ?> <?= e(t('account.nav.' . $key)) ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <div class="min-w-0 transition-opacity" data-account-body aria-live="polite"><?= $body ?></div>
</div>
