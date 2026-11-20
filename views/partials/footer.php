<?php
use App\Core\View;
use App\Repositories\MetaRepository;
use App\Support\Customer;

$categories = MetaRepository::categoryTree(lang());
$store = store_info();   // edited in /admin/cai-dat
$member = Customer::user();

// Cloud-frame corners (cut from the cloud border artwork, white made transparent).
// The inner edge of each piece fades out so the cut never shows, whatever the screen width.
$corner = 'pointer-events-none absolute w-32 select-none sm:w-44 lg:w-60 xl:w-72';
$fadeRight = '[mask-image:linear-gradient(to_right,black_65%,transparent)]';
$fadeLeft  = '[mask-image:linear-gradient(to_left,black_65%,transparent)]';
$link = 'transition hover:text-crimson';
$heading = 'mb-4 text-xs font-semibold uppercase tracking-[0.25em] text-crimson';
?>
<footer class="palette-light relative isolate mt-24 overflow-hidden border-t border-gold/40 bg-ivory text-ink-soft">
  <img src="<?= asset('img/frame/clouds-tl.webp') ?>" alt="" aria-hidden="true" loading="lazy" class="<?= $corner ?> <?= $fadeRight ?> left-0 top-0 -z-10">
  <img src="<?= asset('img/frame/clouds-tr.webp') ?>" alt="" aria-hidden="true" loading="lazy" class="<?= $corner ?> <?= $fadeLeft ?> right-0 top-0 -z-10">
  <img src="<?= asset('img/frame/clouds-bl.webp') ?>" alt="" aria-hidden="true" loading="lazy" class="<?= $corner ?> <?= $fadeRight ?> bottom-0 left-0 -z-10">
  <img src="<?= asset('img/frame/clouds-br.webp') ?>" alt="" aria-hidden="true" loading="lazy" class="<?= $corner ?> <?= $fadeLeft ?> bottom-0 right-0 -z-10">

  <!-- content stays inside the clear centre of the frame -->
  <div class="mx-auto max-w-5xl px-8 pb-28 pt-32 sm:px-24 sm:pt-36 lg:px-28 lg:pb-24 lg:pt-20 xl:max-w-6xl">
    <?= View::partial('partials/motif-divider', ['class' => 'mb-12']) ?>
    <div class="grid gap-10 rounded-sm bg-ivory/80 sm:grid-cols-2 lg:grid-cols-4">
      <div class="lg:col-span-2">
        <p class="flex items-center gap-2 font-serif text-3xl font-bold text-crimson"><?= lotus('h-8 w-8 text-gold') ?> <?= e(t('brand')) ?></p>
        <p class="mt-1 font-serif text-lg italic text-gold-dark"><?= e(t('tagline')) ?></p>
        <p class="mt-4 max-w-md text-sm leading-relaxed"><?= e(t('footer.about')) ?></p>
      </div>

      <div>
        <p class="<?= $heading ?>"><?= e(t('footer.shop')) ?></p>
        <ul class="space-y-2 text-sm">
          <?php foreach ($categories as $c): ?>
            <li><a href="<?= e(url('/bo-suu-tap', ['category' => $c['slug']])) ?>" class="<?= $link ?>"><?= e($c['name']) ?></a></li>
          <?php endforeach; ?>
          <li class="pt-2"><a href="<?= e(url('/tra-cuu-don-hang')) ?>" class="<?= $link ?>"><?= e(t('nav.lookup')) ?></a></li>
          <li><a href="<?= e(url($member ? '/tai-khoan' : '/dang-nhap')) ?>" class="<?= $link ?>"><?= e($member ? t('nav.account') : t('nav.login')) ?></a></li>
          <li><a href="<?= e(url('/yeu-thich')) ?>" class="<?= $link ?>"><?= e(t('nav.wishlist')) ?></a></li>
        </ul>
      </div>

      <div>
        <p class="<?= $heading ?>"><?= e(t('footer.contact')) ?></p>
        <ul class="space-y-3 text-sm">
          <li class="flex gap-2"><?= icon('map-pin', 'h-4 w-4 shrink-0 text-gold-dark') ?> <?= e($store['address']) ?></li>
          <li class="flex gap-2"><?= icon('phone', 'h-4 w-4 shrink-0 text-gold-dark') ?> <a href="tel:<?= e(preg_replace('/\s+/', '', $store['phone'])) ?>" class="<?= $link ?>"><?= e($store['phone']) ?></a></li>
          <li class="flex gap-2"><?= icon('mail', 'h-4 w-4 shrink-0 text-gold-dark') ?> <a href="mailto:<?= e($store['email']) ?>" class="<?= $link ?> break-all"><?= e($store['email']) ?></a></li>
          <?php if (!empty($store['hours'])): ?><li class="pl-6 text-ink-soft/80"><?= e($store['hours']) ?></li><?php endif; ?>
        </ul>
      </div>
    </div>

    <p class="mt-12 border-t border-gold/30 pt-6 text-center text-xs text-ink-soft/80">
      © <?= date('Y') ?> <?= e(t('brand')) ?>. <?= e(t('footer.rights')) ?>
    </p>
  </div>
</footer>
