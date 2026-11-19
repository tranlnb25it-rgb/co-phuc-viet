<?php
/**
 * @var array $filters    normalised filters (ProductFilters::fromQuery)
 * @var array $categories category tree
 * @var array $dynasties
 * @var array $results    CatalogController::results()
 */
use App\Repositories\ProductRepository;
use App\Support\ProductFilters;

$f = $filters;
$option = 'flex cursor-pointer items-center gap-3 py-1.5 text-sm text-ink transition hover:text-crimson';
$control = 'h-4 w-4 shrink-0 accent-crimson';
$legend = 'mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-gold-dark';
?>

<!-- ========== Page heading ========== -->
<section class="border-b border-line bg-silk/60">
  <div class="container-page py-10 lg:py-14">
    <nav aria-label="<?= e(t('catalog.breadcrumb')) ?>" class="flex items-center gap-1.5 text-xs text-ink-soft">
      <a href="<?= e(url('/')) ?>" class="hover:text-crimson"><?= e(t('nav.home')) ?></a>
      <?= icon('chevron-right', 'h-3 w-3') ?>
      <span class="text-ink"><?= e(t('catalog.title')) ?></span>
    </nav>
    <h1 class="mt-4 text-4xl sm:text-5xl" data-catalog-title><?= e($results['title']) ?></h1>
    <p class="mt-3 max-w-2xl text-ink-soft" data-catalog-desc><?= e($results['description']) ?></p>
  </div>
</section>

<div class="container-page grid gap-10 py-10 lg:grid-cols-[250px_1fr] lg:py-14">

  <!-- ========== Filter panel (sidebar on desktop, slide-over on mobile) ========== -->
  <div id="filter-panel" class="fixed inset-0 z-50 hidden lg:static lg:z-auto lg:block" data-filter-panel>
    <div class="absolute inset-0 bg-ink/40 lg:hidden" data-filter-close></div>

    <aside class="absolute inset-y-0 left-0 flex w-80 max-w-[85vw] flex-col bg-ivory shadow-xl lg:static lg:w-auto lg:bg-transparent lg:shadow-none">
      <div class="flex items-center justify-between border-b border-line p-5 lg:hidden">
        <span class="font-serif text-2xl font-semibold"><?= e(t('catalog.filters')) ?></span>
        <button type="button" data-filter-close aria-label="<?= e(t('nav.close')) ?>"><?= icon('x') ?></button>
      </div>

      <form id="catalog-filters" method="get" action="<?= e(url('/bo-suu-tap')) ?>"
            class="flex-1 space-y-8 overflow-y-auto p-5 lg:sticky lg:top-28 lg:max-h-[calc(100vh-8rem)] lg:p-0 lg:pr-2">

        <!-- Garment type -->
        <fieldset>
          <legend class="<?= $legend ?>"><?= e(t('catalog.type')) ?></legend>
          <label class="<?= $option ?>">
            <input type="radio" name="category" value="" class="<?= $control ?>" <?= !$f['category'] ? 'checked' : '' ?>>
            <?= e(t('catalog.all')) ?>
          </label>
          <?php foreach ($categories as $c): ?>
            <label class="<?= $option ?>">
              <input type="radio" name="category" value="<?= e($c['slug']) ?>" class="<?= $control ?>" <?= $f['category'] === $c['slug'] ? 'checked' : '' ?>>
              <span class="flex-1"><?= e($c['name']) ?></span>
              <span class="text-xs text-ink-soft"><?= (int) $c['product_count'] ?></span>
            </label>
            <?php foreach ($c['children'] as $child): ?>
              <label class="<?= $option ?> pl-7">
                <input type="radio" name="category" value="<?= e($child['slug']) ?>" class="<?= $control ?>" <?= $f['category'] === $child['slug'] ? 'checked' : '' ?>>
                <span class="flex-1"><?= e($child['name']) ?></span>
                <span class="text-xs text-ink-soft"><?= (int) $child['product_count'] ?></span>
              </label>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </fieldset>

        <div class="h-px bg-line"></div>

        <!-- Era (multiple) -->
        <fieldset>
          <legend class="<?= $legend ?>"><?= e(t('catalog.era')) ?></legend>
          <?php foreach ($dynasties as $d): ?>
            <label class="<?= $option ?>">
              <input type="checkbox" name="dynasty[]" value="<?= e($d['slug']) ?>" class="<?= $control ?> rounded-sm" <?= in_array($d['slug'], $f['dynasty'], true) ? 'checked' : '' ?>>
              <span class="flex-1"><?= e($d['name']) ?></span>
              <span class="text-[11px] text-ink-soft"><?= e($d['period']) ?></span>
            </label>
          <?php endforeach; ?>
        </fieldset>

        <div class="h-px bg-line"></div>

        <!-- Gender -->
        <fieldset>
          <legend class="<?= $legend ?>"><?= e(t('catalog.gender')) ?></legend>
          <div class="grid grid-cols-3 gap-2">
            <?php foreach (['' => t('catalog.all'), 'nam' => t('product.gender.nam'), 'nu' => t('product.gender.nu')] as $val => $label): ?>
              <label class="cursor-pointer">
                <input type="radio" name="gender" value="<?= e($val) ?>" class="peer sr-only" <?= ($f['gender'] ?? '') === $val ? 'checked' : '' ?>>
                <span class="block rounded-sm border border-line py-2 text-center text-sm transition peer-checked:border-crimson peer-checked:bg-crimson peer-checked:text-ivory peer-focus-visible:outline-2 peer-focus-visible:outline-gold hover:border-gold">
                  <?= e($label) ?>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        </fieldset>

        <div class="h-px bg-line"></div>

        <!-- Price -->
        <fieldset>
          <legend class="<?= $legend ?>"><?= e(t('catalog.price')) ?></legend>
          <label class="<?= $option ?>">
            <input type="radio" name="price" value="" class="<?= $control ?>" <?= !$f['price'] ? 'checked' : '' ?>>
            <?= e(t('catalog.all')) ?>
          </label>
          <?php foreach (array_keys(ProductFilters::PRICE_RANGES) as $slug): ?>
            <label class="<?= $option ?>">
              <input type="radio" name="price" value="<?= e($slug) ?>" class="<?= $control ?>" <?= $f['price'] === $slug ? 'checked' : '' ?>>
              <?= e(t('catalog.prices.' . $slug)) ?>
            </label>
          <?php endforeach; ?>
        </fieldset>

        <div class="h-px bg-line"></div>

        <!-- On sale -->
        <label class="flex cursor-pointer items-center justify-between gap-3 border border-crimson/30 bg-crimson/5 px-3 py-2.5 text-sm font-medium text-crimson transition hover:bg-crimson/10">
          <span class="flex items-center gap-2"><span class="rounded-sm bg-crimson px-1.5 py-0.5 text-[10px] font-bold text-ivory">%</span> <?= e(t('catalog.onSale')) ?></span>
          <input type="checkbox" name="sale" value="1" class="h-4 w-4 accent-crimson" <?= !empty($f['on_sale']) ? 'checked' : '' ?>>
        </label>

        <!-- Without JavaScript the form is submitted normally -->
        <div class="flex gap-2" data-nojs-only>
          <button type="submit" class="btn-primary flex-1"><?= e(t('catalog.apply')) ?></button>
          <a href="<?= e(url('/bo-suu-tap')) ?>" class="btn-outline"><?= e(t('catalog.clearAll')) ?></a>
        </div>
      </form>

      <div class="border-t border-line p-5 lg:hidden">
        <button type="button" class="btn-primary w-full" data-filter-close>
          <?= e(t('catalog.showResults')) ?> (<span data-catalog-total><?= (int) $results['total'] ?></span>)
        </button>
      </div>
    </aside>
  </div>

  <!-- ========== Results ========== -->
  <div class="min-w-0">
    <!-- Search (real-time) -->
    <label class="relative block">
      <span class="sr-only"><?= e(t('nav.search')) ?></span>
      <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-ink-soft"><?= icon('search', 'h-5 w-5') ?></span>
      <input type="search" name="q" form="catalog-filters" value="<?= e($f['q']) ?>" autocomplete="off"
             placeholder="<?= e(t('catalog.searchPlaceholder')) ?>"
             class="input h-12 rounded-full! pl-12! pr-12! text-base" data-catalog-search>
      <span class="absolute inset-y-0 right-4 hidden items-center text-gold" data-catalog-spinner><?= icon('loader', 'h-5 w-5 animate-spin') ?></span>
    </label>

    <!-- Toolbar -->
    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-b border-line pb-4">
      <p class="text-sm text-ink-soft" aria-live="polite" data-catalog-count><?= e($results['count_text']) ?></p>
      <div class="flex items-center gap-2">
        <button type="button" class="btn-outline px-4! py-2! lg:hidden" data-filter-open aria-controls="filter-panel">
          <?= e(t('catalog.filters')) ?>
          <span data-filter-badge class="<?= $results['active_count'] ? '' : 'hidden' ?> rounded-full bg-crimson px-1.5 text-[10px] font-semibold leading-4 text-ivory"><?= (int) $results['active_count'] ?></span>
        </button>
        <label class="flex items-center gap-2 text-sm">
          <span class="hidden text-ink-soft sm:inline"><?= e(t('catalog.sort')) ?></span>
          <select name="sort" form="catalog-filters" class="input w-auto! py-2! pr-8">
            <?php foreach (array_keys(ProductRepository::SORTS) as $s): ?>
              <option value="<?= e($s) ?>" <?= $f['sort'] === $s ? 'selected' : '' ?>><?= e(t('catalog.sorts.' . $s)) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
    </div>

    <div id="catalog-results" class="relative mt-6 scroll-mt-28 transition-opacity duration-300"
         data-error="<?= e(t('common.error')) ?>" data-title-suffix=" · <?= e(t('brand')) ?>">
      <?= $results['html'] ?>
    </div>
  </div>
</div>
