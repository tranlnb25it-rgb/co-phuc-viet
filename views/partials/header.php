<?php
$path = current_path();
$links = [
    ['href' => url('/'),                                       'label' => t('nav.home'),        'active' => $path === '/'],
    ['href' => url('/bo-suu-tap'),                             'label' => t('nav.collection'),  'active' => $path === '/bo-suu-tap' && ($_GET['category'] ?? '') !== 'phu-kien'],
    ['href' => url('/bo-suu-tap', ['category' => 'phu-kien']), 'label' => t('nav.accessories'), 'active' => ($_GET['category'] ?? '') === 'phu-kien'],
    ['href' => url('/') . '#cau-chuyen',                       'label' => t('nav.story'),       'active' => false],
];
$navLink = fn(bool $active) => 'nav-link ' . ($active ? 'is-active' : '');
?>
<header id="site-header" class="sticky top-0 z-40 border-b border-line bg-ivory/90 backdrop-blur transition-shadow">
  <div class="h-1 bg-gradient-to-r from-crimson via-gold to-crimson"></div>

  <div class="container-page flex h-16 items-center justify-between gap-4 lg:h-20">
    <button type="button" class="p-2 lg:hidden" data-menu-open aria-label="<?= e(t('nav.menu')) ?>" aria-controls="mobile-menu">
      <?= icon('menu') ?>
    </button>

    <a href="<?= e(url('/')) ?>" class="flex items-center gap-2.5 text-crimson">
      <?= lotus('h-8 w-8 text-gold') ?>
      <span class="font-serif text-2xl font-bold tracking-wide lg:text-[1.7rem]"><?= e(t('brand')) ?></span>
    </a>

    <nav class="hidden items-center gap-8 lg:flex" aria-label="Main">
      <?php foreach ($links as $l): ?>
        <a href="<?= e($l['href']) ?>" class="<?= $navLink($l['active']) ?>" <?= $l['active'] ? 'aria-current="page"' : '' ?>><?= e($l['label']) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="flex items-center gap-1 sm:gap-2">
      <a href="<?= e(url('/bo-suu-tap')) ?>" class="rounded-full p-2 transition hover:bg-silk" aria-label="<?= e(t('nav.search')) ?>">
        <?= icon('search') ?>
      </a>
      <a href="<?= e(url('/lang/' . t('lang.target'))) ?>"
         class="hidden rounded-sm border border-line px-2 py-1 text-xs font-semibold tracking-wider transition hover:border-gold hover:text-crimson sm:block"
         title="<?= e(t('lang.switch')) ?>" hreflang="<?= e(t('lang.target')) ?>"><?= e(t('lang.short')) ?></a>
      <?php $member = App\Support\Customer::user(); $wishCount = App\Support\Wishlist::count(); ?>
      <a href="<?= e(url($member ? '/tai-khoan' : '/dang-nhap')) ?>" class="hidden items-center gap-1.5 rounded-full p-2 transition hover:bg-silk sm:flex"
         aria-label="<?= e($member ? t('nav.account') : t('nav.login')) ?>" title="<?= e($member ? $member['name'] : t('nav.login')) ?>">
        <?= icon('user') ?>
        <?php if ($member): ?><span class="hidden max-w-24 truncate text-xs font-medium xl:inline"><?= e(trim(strrchr(' ' . $member['name'], ' '))) ?></span><?php endif; ?>
      </a>
      <a href="<?= e(url('/yeu-thich')) ?>" class="relative rounded-full p-2 transition hover:bg-silk" aria-label="<?= e(t('nav.wishlist')) ?>">
        <?= icon('heart') ?>
        <span data-wishlist-count class="<?= $wishCount ? '' : 'hidden' ?> absolute -right-0.5 -top-0.5 h-4 min-w-4 rounded-full bg-gold px-1 text-center text-[10px] font-semibold leading-4 text-ink"><?= $wishCount ?: '' ?></span>
      </a>
      <?php $cartCount = App\Support\Cart::count(); ?>
      <a href="<?= e(url('/thanh-toan')) ?>" class="relative rounded-full p-2 transition hover:bg-silk" aria-label="<?= e(t('nav.cart')) ?>"
         data-cart-open aria-controls="cart-drawer">
        <?= icon('shopping-bag') ?>
        <span data-cart-count class="<?= $cartCount ? '' : 'hidden' ?> absolute -right-0.5 -top-0.5 h-4 min-w-4 rounded-full bg-crimson px-1 text-center text-[10px] font-semibold leading-4 text-ivory"><?= $cartCount ?: '' ?></span>
      </a>
    </div>
  </div>

  <!-- Mobile menu -->
  <div id="mobile-menu" class="fixed inset-0 z-50 hidden lg:hidden" data-menu>
    <div class="absolute inset-0 bg-ink/40" data-menu-close></div>
    <div class="absolute inset-y-0 left-0 flex w-72 max-w-[85vw] flex-col bg-ivory p-6 shadow-xl">
      <div class="mb-8 flex items-center justify-between">
        <span class="font-serif text-xl font-bold text-crimson"><?= e(t('brand')) ?></span>
        <button type="button" data-menu-close aria-label="<?= e(t('nav.close')) ?>"><?= icon('x') ?></button>
      </div>
      <nav class="flex flex-col gap-5">
        <?php foreach ($links as $l): ?>
          <a href="<?= e($l['href']) ?>" class="<?= $navLink($l['active']) ?> self-start" data-menu-close><?= e($l['label']) ?></a>
        <?php endforeach; ?>
      </nav>
      <div class="mt-8 space-y-4 border-t border-line pt-6 text-sm">
        <a href="<?= e(url($member ? '/tai-khoan' : '/dang-nhap')) ?>" class="flex items-center gap-3 hover:text-crimson"><?= icon('user', 'h-4 w-4 text-crimson') ?> <?= e($member ? t('nav.account') : t('nav.login')) ?></a>
        <a href="<?= e(url('/yeu-thich')) ?>" class="flex items-center gap-3 hover:text-crimson"><?= icon('heart', 'h-4 w-4 text-crimson') ?> <?= e(t('nav.wishlist')) ?></a>
        <a href="<?= e(url('/tra-cuu-don-hang')) ?>" class="flex items-center gap-3 hover:text-crimson"><?= icon('package', 'h-4 w-4 text-crimson') ?> <?= e(t('nav.lookup')) ?></a>
      </div>
      <a href="<?= e(url('/lang/' . t('lang.target'))) ?>" class="btn-outline mt-auto"><?= icon('globe', 'h-4 w-4') ?> <?= e(t('lang.switch')) ?></a>
    </div>
  </div>
</header>
