<?php
use App\Core\View;
use App\Repositories\MetaRepository;

$categories = MetaRepository::categoryTree(lang());
$store = store_info();   // edited in /admin/cai-dat
?>
<footer class="mt-24 bg-ink text-ivory/80">
  <div class="h-1 bg-gradient-to-r from-crimson via-gold to-crimson"></div>
  <div class="container-page py-14">
    <?= View::partial('partials/motif-divider', ['class' => 'mb-12']) ?>
    <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
      <div class="lg:col-span-2">
        <p class="font-serif text-2xl font-bold text-gold"><?= e(t('brand')) ?></p>
        <p class="mt-1 font-serif italic text-ivory/60"><?= e(t('tagline')) ?></p>
        <p class="mt-4 max-w-md text-sm leading-relaxed"><?= e(t('footer.about')) ?></p>
      </div>

      <div>
        <p class="mb-4 text-xs font-semibold uppercase tracking-[0.25em] text-gold"><?= e(t('footer.shop')) ?></p>
        <ul class="space-y-2 text-sm">
          <?php foreach ($categories as $c): ?>
            <li><a href="<?= e(url('/bo-suu-tap', ['category' => $c['slug']])) ?>" class="transition hover:text-gold"><?= e($c['name']) ?></a></li>
          <?php endforeach; ?>
          <li class="pt-2"><a href="<?= e(url('/tra-cuu-don-hang')) ?>" class="transition hover:text-gold"><?= e(t('nav.lookup')) ?></a></li>
          <li><a href="<?= e(url(App\Support\Customer::user() ? '/tai-khoan' : '/dang-nhap')) ?>" class="transition hover:text-gold"><?= e(App\Support\Customer::user() ? t('nav.account') : t('nav.login')) ?></a></li>
          <li><a href="<?= e(url('/yeu-thich')) ?>" class="transition hover:text-gold"><?= e(t('nav.wishlist')) ?></a></li>
        </ul>
      </div>

      <div>
        <p class="mb-4 text-xs font-semibold uppercase tracking-[0.25em] text-gold"><?= e(t('footer.contact')) ?></p>
        <ul class="space-y-3 text-sm">
          <li class="flex gap-2"><?= icon('map-pin', 'h-4 w-4 shrink-0 text-gold') ?> <?= e($store['address']) ?></li>
          <li class="flex gap-2"><?= icon('phone', 'h-4 w-4 shrink-0 text-gold') ?> <a href="tel:<?= e(preg_replace('/\s+/', '', $store['phone'])) ?>" class="hover:text-gold"><?= e($store['phone']) ?></a></li>
          <li class="flex gap-2"><?= icon('mail', 'h-4 w-4 shrink-0 text-gold') ?> <a href="mailto:<?= e($store['email']) ?>" class="hover:text-gold"><?= e($store['email']) ?></a></li>
          <?php if (!empty($store['hours'])): ?><li class="pl-6 text-ivory/60"><?= e($store['hours']) ?></li><?php endif; ?>
        </ul>
      </div>
    </div>

    <p class="mt-12 border-t border-ivory/10 pt-6 text-center text-xs text-ivory/50">
      © <?= date('Y') ?> <?= e(t('brand')) ?>. <?= e(t('footer.rights')) ?>
    </p>
  </div>
</footer>
