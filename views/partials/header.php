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
        <span data-wishlist-count class="<?= $wishCount ? '' : 'hidden' ?> absolute -right-0.5 -top-0.5 h-4 min-w-4 rounded-full bg-gold px-1 text-center text-[10px] font-semibold leading-4 text-[#2B2623]"><?= $wishCount ?: '' ?></span>
      </a>
      <?php $cartCount = App\Support\Cart::count(); ?>
      <a href="<?= e(url('/thanh-toan')) ?>" class="relative rounded-full p-2 transition hover:bg-silk" aria-label="<?= e(t('nav.cart')) ?>"
         data-cart-open aria-controls="cart-drawer">
        <?= icon('shopping-bag') ?>
        <span data-cart-count class="<?= $cartCount ? '' : 'hidden' ?> absolute -right-0.5 -top-0.5 h-4 min-w-4 rounded-full bg-crimson px-1 text-center text-[10px] font-semibold leading-4 text-ivory"><?= $cartCount ?: '' ?></span>
      </a>
    </div>
  </div>

</header>

<!-- Mobile menu. Kept OUTSIDE <header>: the header's backdrop-blur would otherwise trap this
     fixed overlay inside the header's own box (only ~70px tall), leaving the links on a transparent panel. -->
<div id="mobile-menu" class="fixed inset-0 z-50 hidden lg:hidden" data-menu role="dialog" aria-modal="true" aria-label="<?= e(t('nav.menu')) ?>">
  <div class="palette-light absolute inset-0 bg-ink/60 backdrop-blur-sm transition-opacity duration-300 starting:opacity-0" data-menu-close></div>

  <div class="absolute inset-y-0 left-0 flex w-80 max-w-[86vw] flex-col overflow-y-auto bg-ivory shadow-2xl transition-transform duration-300 ease-silk starting:-translate-x-full">
    <div class="h-1 shrink-0 bg-gradient-to-r from-crimson via-gold to-crimson"></div>
    <div class="flex items-center justify-between border-b border-line px-5 py-4">
      <a href="<?= e(url('/')) ?>" class="flex items-center gap-2 text-crimson">
        <?= lotus('h-7 w-7 text-gold') ?>
        <span class="font-serif text-2xl font-bold"><?= e(t('brand')) ?></span>
      </a>
      <button type="button" data-menu-close class="rounded-full p-2 transition hover:bg-silk" aria-label="<?= e(t('nav.close')) ?>"><?= icon('x') ?></button>
    </div>

    <?php if ($member): ?>
      <p class="border-b border-line bg-silk/60 px-5 py-3 text-sm"><?= e(t('account.hello', ['name' => $member['name']])) ?></p>
    <?php endif; ?>

    <!-- main sections -->
    <nav class="py-2" aria-label="<?= e(t('nav.menu')) ?>">
      <?php foreach ($links as $l): ?>
        <a href="<?= e($l['href']) ?>" data-menu-close <?= $l['active'] ? 'aria-current="page"' : '' ?>
           class="flex items-center justify-between border-l-4 px-5 py-3.5 font-serif text-xl transition
                  <?= $l['active'] ? 'border-crimson bg-crimson/5 font-semibold text-crimson' : 'border-transparent text-ink hover:border-gold hover:bg-silk/60 hover:text-crimson' ?>">
          <?= e($l['label']) ?>
          <?= icon('chevron-right', 'h-4 w-4 ' . ($l['active'] ? 'text-crimson' : 'text-gold')) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <!-- account shortcuts -->
    <div class="mx-5 border-t border-line pt-4 pb-2">
      <p class="eyebrow mb-2"><?= e($member ? t('nav.account') : t('nav.login')) ?></p>
      <?php
      $shortcuts = [
          [$member ? '/tai-khoan' : '/dang-nhap', 'user', $member ? t('nav.account') : t('nav.login'), null],
          ['/yeu-thich', 'heart', t('nav.wishlist'), $wishCount ?: null],
          ['/tra-cuu-don-hang', 'package', t('nav.lookup'), null],
      ];
      foreach ($shortcuts as [$href, $ic, $label, $badge]): ?>
        <a href="<?= e(url($href)) ?>" class="flex items-center gap-3 rounded-sm px-2 py-2.5 text-sm transition hover:bg-silk hover:text-crimson">
          <span class="flex h-8 w-8 items-center justify-center rounded-full bg-silk text-crimson"><?= icon($ic, 'h-4 w-4') ?></span>
          <span class="flex-1"><?= e($label) ?></span>
          <?php if ($badge): ?><span class="rounded-full bg-gold px-2 text-xs font-semibold text-[#2B2623]"><?= (int) $badge ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="mt-auto border-t border-line p-5">
      <a href="<?= e(url('/lang/' . t('lang.target'))) ?>" class="btn-outline w-full"><?= icon('globe', 'h-4 w-4') ?> <?= e(t('lang.switch')) ?></a>
    </div>
  </div>
</div>
