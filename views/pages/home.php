<?php
/**
 * @var array $featuredCategories
 * @var array $dynasties
 * @var array $featuredProducts
 */
use App\Core\{Lang, View};

$hero  = Lang::raw('home.hero');
$story = Lang::raw('home.story');
$categoryTint = ['ao-nhat-binh' => '#9E2A2B', 'ao-tac' => '#2E4A7D', 'ao-giao-linh' => '#6B3A2A', 'phu-kien' => '#C59B27'];
?>

<!-- ========== HERO ========== -->
<section class="hero-pattern relative overflow-hidden bg-crimson-dark text-ivory">
  <div class="container-page grid items-center gap-12 py-16 lg:grid-cols-[1.1fr_1fr] lg:py-24">
    <div class="reveal">
      <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-light"><?= e($hero['eyebrow']) ?></p>
      <h1 class="mt-5 text-5xl leading-[1.05] text-ivory sm:text-6xl xl:text-7xl"><?= $hero['title'] /* trusted markup from lang file */ ?></h1>
      <p class="mt-6 max-w-xl text-base leading-relaxed text-ivory/80 sm:text-lg"><?= e($hero['text']) ?></p>
      <div class="mt-9 flex flex-wrap gap-3">
        <a href="<?= e(url('/bo-suu-tap')) ?>" class="btn-gold"><?= e($hero['cta']) ?> <?= icon('arrow-right', 'h-4 w-4') ?></a>
        <a href="#cau-chuyen" class="btn border border-ivory/40 text-ivory hover:border-gold hover:text-gold-light"><?= icon('book-open', 'h-4 w-4') ?> <?= e($hero['secondary']) ?></a>
      </div>
      <dl class="mt-12 grid max-w-md grid-cols-3 gap-6 border-t border-ivory/15 pt-6">
        <?php foreach ($hero['stats'] as [$num, $label]): ?>
          <div>
            <dt class="sr-only"><?= e($label) ?></dt>
            <dd class="font-serif text-3xl font-semibold text-gold-light"><?= e($num) ?></dd>
            <dd class="mt-1 text-xs uppercase tracking-wider text-ivory/60"><?= e($label) ?></dd>
          </div>
        <?php endforeach; ?>
      </dl>
    </div>

    <!-- Showcase: three arched frames -->
    <div class="reveal relative mx-auto grid w-full max-w-lg grid-cols-3 items-end gap-3 sm:gap-4" style="--delay:150ms">
      <?php
      $showcase = [['Áo Tấc', '#2E4A7D', 'aspect-[3/5]'], ['Áo Nhật Bình', '#C59B27', 'aspect-[3/6]'], ['Áo Giao Lĩnh', '#6B3A2A', 'aspect-[3/5]']];
      foreach ($showcase as $i => [$name, $tint, $ratio]): ?>
        <div class="arch-frame <?= $ratio ?> <?= $i === 1 ? '' : 'translate-y-6' ?>">
          <?= product_image(null, $name, $tint) ?>
        </div>
      <?php endforeach; ?>
      <div class="pointer-events-none absolute -inset-6 -z-0 rounded-t-full border border-gold/20"></div>
    </div>
  </div>
  <div class="h-1.5 bg-gradient-to-r from-gold-dark via-gold-light to-gold-dark"></div>
</section>

<!-- ========== PROMISES ========== -->
<section class="border-b border-line bg-silk/60">
  <div class="container-page grid grid-cols-2 gap-6 py-8 lg:grid-cols-4">
    <?php foreach (Lang::raw('home.promises') as $p): ?>
      <div class="flex items-start gap-3">
        <span class="mt-0.5 rounded-full border border-gold/40 p-2 text-crimson"><?= icon($p['icon'], 'h-4 w-4') ?></span>
        <div>
          <p class="text-sm font-semibold"><?= e($p['title']) ?></p>
          <p class="text-xs leading-relaxed text-ink-soft"><?= e($p['text']) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ========== FEATURED CATEGORIES ========== -->
<section class="container-page py-20">
  <div class="text-center">
    <p class="eyebrow"><?= e(t('home.categories.eyebrow')) ?></p>
    <h2 class="mt-3 text-4xl sm:text-5xl"><?= e(t('home.categories.title')) ?></h2>
    <?= View::partial('partials/motif-divider', ['class' => 'mt-6']) ?>
  </div>

  <div class="mt-12 grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4">
    <?php foreach ($featuredCategories as $i => $c): ?>
      <a href="<?= e(url('/bo-suu-tap', ['category' => $c['slug']])) ?>"
         class="reveal group relative block overflow-hidden rounded-t-full border border-line bg-silk" style="--delay:<?= $i * 80 ?>ms">
        <div class="aspect-[3/4] transition duration-700 ease-silk group-hover:scale-105">
          <?= product_image($c['image_url'], $c['name'], $categoryTint[$c['slug']] ?? null) ?>
        </div>
        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-ink/85 via-ink/40 to-transparent p-5 pt-16 text-ivory">
          <h3 class="text-2xl text-ivory"><?= e($c['name']) ?></h3>
          <p class="mt-1 flex items-center justify-between text-xs uppercase tracking-wider text-ivory/70">
            <span><?= e(t('home.categories.count', ['count' => $c['product_count']])) ?></span>
            <span class="translate-x-0 text-gold-light transition group-hover:translate-x-1"><?= icon('arrow-right', 'h-4 w-4') ?></span>
          </p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- ========== FEATURED PRODUCTS (tabs load via Ajax) ========== -->
<section class="bg-silk/50 py-20">
  <div class="container-page">
    <div class="flex flex-col items-center justify-between gap-6 md:flex-row md:items-end">
      <div class="text-center md:text-left">
        <p class="eyebrow"><?= e(t('home.featured.eyebrow')) ?></p>
        <h2 class="mt-3 text-4xl sm:text-5xl"><?= e(t('home.featured.title')) ?></h2>
      </div>
      <div class="flex flex-wrap justify-center gap-2" role="tablist" data-featured-tabs>
        <button type="button" role="tab" class="tab is-active" aria-selected="true" data-dynasty=""><?= e(t('home.featured.all')) ?></button>
        <?php foreach ($dynasties as $d): ?>
          <button type="button" role="tab" class="tab" aria-selected="false" data-dynasty="<?= e($d['slug']) ?>"><?= e($d['name']) ?></button>
        <?php endforeach; ?>
      </div>
    </div>

    <div id="featured-grid" class="relative mt-10 grid grid-cols-2 gap-x-4 gap-y-10 transition-opacity duration-300 sm:gap-x-6 lg:grid-cols-4"
         aria-live="polite" data-error="<?= e(t('common.error')) ?>">
      <?= View::partial('partials/product-grid', ['products' => $featuredProducts]) ?>
    </div>

    <div class="mt-12 text-center">
      <a href="<?= e(url('/bo-suu-tap')) ?>" class="btn-outline"><?= e(t('home.featured.viewAll')) ?> <?= icon('arrow-right', 'h-4 w-4') ?></a>
    </div>
  </div>
</section>

<!-- ========== CULTURAL STORY ========== -->
<section id="cau-chuyen" class="scroll-mt-24 py-20">
  <div class="container-page">
    <div class="grid gap-14 lg:grid-cols-[1fr_1.15fr]">
      <div class="reveal lg:sticky lg:top-32 lg:self-start">
        <p class="eyebrow"><?= e($story['eyebrow']) ?></p>
        <h2 class="mt-3 text-4xl sm:text-5xl"><?= e($story['title']) ?></h2>
        <p class="mt-6 leading-relaxed text-ink-soft"><?= e($story['intro']) ?></p>
        <blockquote class="relative mt-8 border-l-2 border-gold pl-6 font-serif text-2xl italic leading-snug text-crimson">
          <?= e($story['quote']) ?>
        </blockquote>
        <a href="<?= e(url('/bo-suu-tap')) ?>" class="btn-primary mt-10"><?= e($story['cta']) ?> <?= icon('arrow-right', 'h-4 w-4') ?></a>
      </div>

      <!-- Timeline of eras -->
      <ol class="relative space-y-10 border-l border-gold/40 pl-8">
        <?php foreach ($story['eras'] as $i => $era): ?>
          <li class="reveal relative" style="--delay:<?= $i * 100 ?>ms">
            <span class="absolute -left-[41px] top-1 flex h-5 w-5 rotate-45 items-center justify-center border border-gold bg-ivory">
              <span class="h-2 w-2 bg-crimson"></span>
            </span>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-gold-dark"><?= e($era['period']) ?></p>
            <h3 class="mt-1 text-3xl"><?= e($era['name']) ?></h3>
            <p class="mt-3 leading-relaxed text-ink-soft"><?= e($era['text']) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>

    <?= View::partial('partials/motif-divider', ['class' => 'my-16']) ?>

    <div class="grid gap-px overflow-hidden border border-line bg-line sm:grid-cols-2 lg:grid-cols-4">
      <?php foreach ($story['garments'] as $i => $g): ?>
        <div class="reveal group bg-ivory p-7 transition-colors duration-500 hover:bg-crimson" style="--delay:<?= $i * 80 ?>ms">
          <span class="font-serif text-5xl text-gold/50 transition group-hover:text-gold-light"><?= sprintf('%02d', $i + 1) ?></span>
          <h3 class="mt-4 text-2xl transition group-hover:text-ivory"><?= e($g['name']) ?></h3>
          <p class="mt-2 text-sm leading-relaxed text-ink-soft transition group-hover:text-ivory/80"><?= e($g['text']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
