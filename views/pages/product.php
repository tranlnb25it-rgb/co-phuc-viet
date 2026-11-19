<?php
/**
 * @var array $product   ProductRepository::findBySlug()
 * @var array $options   ProductQuote::options()
 * @var array $quote     ProductQuote::quote() for the initial selection
 * @var array $sizeChart @var array $fields @var array $related
 * @var array $savedMeasurements [{id, name, values}] for signed-in customers
 * @var array $reviewSummary @var array $reviewList @var array $eligibility (Reviews service)
 */
use App\Core\{Lang, View};
use App\Support\Tailoring;

$sel     = $quote['selection'];
$tint    = $sel['color'] ?? null;
$images  = $product['images'] ?: [['id' => 0, 'url' => null, 'view_type' => 'front']];
$isCustom = $sel['size'] === Tailoring::CUSTOM;
$fieldLabels = Lang::raw('pdp.custom.fields');
$legend = 'text-xs font-semibold uppercase tracking-[0.2em] text-gold-dark';
$statusDot = ['in_stock' => 'bg-emerald-600', 'low' => 'bg-gold', 'out' => 'bg-crimson', 'made_to_order' => 'bg-ink'];
?>

<div class="container-page py-8 lg:py-12">
  <!-- Breadcrumb -->
  <nav aria-label="<?= e(t('catalog.breadcrumb')) ?>" class="flex flex-wrap items-center gap-1.5 text-xs text-ink-soft">
    <a href="<?= e(url('/')) ?>" class="hover:text-crimson"><?= e(t('nav.home')) ?></a>
    <?= icon('chevron-right', 'h-3 w-3') ?>
    <a href="<?= e(url('/bo-suu-tap')) ?>" class="hover:text-crimson"><?= e(t('catalog.title')) ?></a>
    <?= icon('chevron-right', 'h-3 w-3') ?>
    <a href="<?= e(url('/bo-suu-tap', ['category' => $product['category_slug']])) ?>" class="hover:text-crimson"><?= e($product['category_name']) ?></a>
    <?= icon('chevron-right', 'h-3 w-3') ?>
    <span class="text-ink"><?= e($product['name']) ?></span>
  </nav>

  <div class="mt-6 grid gap-10 lg:grid-cols-[1.1fr_1fr] lg:gap-14">

    <!-- ========== Gallery ========== -->
    <section aria-label="<?= e(t('pdp.gallery')) ?>" class="lg:sticky lg:top-28 lg:self-start" data-gallery>
      <div class="group relative aspect-[4/5] overflow-hidden border border-line bg-silk" data-gallery-stage>
        <?php foreach ($images as $i => $img): ?>
          <div class="absolute inset-0 transition-opacity duration-500 ease-silk <?= $i === 0 ? '' : 'pointer-events-none opacity-0' ?>"
               data-gallery-slide="<?= $i ?>" <?= $i === 0 ? '' : 'aria-hidden="true"' ?>>
            <div class="h-full w-full transition-transform duration-300 ease-out" data-zoom>
              <?= product_image($img['url'], $product['name'] . ' — ' . t('pdp.views.' . $img['view_type']), $tint) ?>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if (count($images) > 1): ?>
          <button type="button" data-gallery-prev class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-ivory/85 p-2 opacity-0 shadow transition group-hover:opacity-100 focus:opacity-100" aria-label="<?= e(t('catalog.prev')) ?>">
            <?= icon('chevron-right', 'h-5 w-5 rotate-180') ?>
          </button>
          <button type="button" data-gallery-next class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-ivory/85 p-2 opacity-0 shadow transition group-hover:opacity-100 focus:opacity-100" aria-label="<?= e(t('catalog.next')) ?>">
            <?= icon('chevron-right', 'h-5 w-5') ?>
          </button>
        <?php endif; ?>
      </div>

      <?php if (count($images) > 1): ?>
        <div class="mt-3 grid grid-cols-4 gap-3" role="tablist">
          <?php foreach ($images as $i => $img): ?>
            <button type="button" role="tab" data-gallery-thumb="<?= $i ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                    class="group/thumb relative aspect-square overflow-hidden border-2 transition <?= $i === 0 ? 'border-crimson' : 'border-transparent hover:border-gold' ?>">
              <span class="pointer-events-none block h-full w-full [&_span]:hidden [&_svg]:h-7 [&_svg]:w-7"><?= product_image($img['url'], t('pdp.views.' . $img['view_type']), $tint) ?></span>
              <span class="absolute inset-x-0 bottom-0 bg-ink/70 py-0.5 text-center text-[10px] uppercase tracking-wider text-ivory"><?= e(t('pdp.views.' . $img['view_type'])) ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- ========== Info + options ========== -->
    <section>
      <div class="flex flex-wrap items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.2em]">
        <?php if ($product['dynasty_name']): ?>
          <a href="<?= e(url('/bo-suu-tap', ['dynasty' => [$product['dynasty_slug']]])) ?>" class="border border-crimson/30 px-2 py-0.5 text-crimson hover:bg-crimson hover:text-ivory">
            <?= e($product['dynasty_name']) ?><?= $product['dynasty_period'] ? ' · ' . e($product['dynasty_period']) : '' ?>
          </a>
        <?php endif; ?>
        <span class="text-ink-soft"><?= e($product['category_name']) ?> · <?= e(t('product.gender.' . $product['gender'])) ?></span>
      </div>

      <h1 class="mt-3 text-4xl leading-tight sm:text-5xl"><?= e($product['name']) ?></h1>
      <a href="#reviews" class="mt-2 inline-flex items-center gap-2 text-sm text-ink-soft hover:text-crimson">
        <?php if ($reviewSummary['count']): ?>
          <?= stars($reviewSummary['avg']) ?>
          <span class="font-medium text-ink"><?= e(number_format($reviewSummary['avg'], 1, ',', '')) ?></span>
          <span class="underline underline-offset-4"><?= e(t('reviews.count', ['count' => $reviewSummary['count']])) ?></span>
        <?php else: ?>
          <?= stars(0) ?> <span class="underline underline-offset-4"><?= e(t('reviews.none')) ?></span>
        <?php endif; ?>
      </a>

      <div class="mt-5">
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
          <p class="font-serif text-4xl font-semibold text-crimson" data-quote-unit><?= e($quote['unit_price_text']) ?></p>
          <p class="<?= $quote['on_sale'] ? '' : 'hidden' ?> text-lg text-ink-soft line-through" data-quote-original><?= e($quote['original_unit_price_text'] ?? '') ?></p>
          <?php if ($quote['on_sale']): ?>
            <span class="rounded-sm bg-crimson px-2 py-0.5 text-sm font-semibold text-ivory"><?= e(t('pdp.saleBadge', ['percent' => $quote['sale_percent']])) ?></span>
          <?php endif; ?>
        </div>
        <div data-quote-breakdown><?= View::partial('partials/price-breakdown', ['quote' => $quote]) ?></div>
        <?php if ($quote['on_sale'] && $quote['sale_ends_at']): $units = App\Core\Lang::raw('pdp.units'); ?>
          <div class="mt-3 inline-flex flex-wrap items-center gap-2 border border-crimson/30 bg-crimson/5 px-3 py-2 text-sm"
               data-countdown="<?= e(date('c', strtotime($quote['sale_ends_at']))) ?>" data-units="<?= e(json_encode($units, JSON_UNESCAPED_UNICODE)) ?>">
            <span class="text-crimson"><?= e(t('pdp.saleEndsIn')) ?></span>
            <strong class="tabular-nums" data-countdown-text><?= e(t('pdp.saleUntil', ['date' => date('H:i d/m/Y', strtotime($quote['sale_ends_at']))])) ?></strong>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($product['short_description']): ?>
        <p class="mt-5 leading-relaxed text-ink-soft"><?= e($product['short_description']) ?></p>
      <?php endif; ?>

      <?= View::partial('partials/motif-divider', ['class' => 'my-7']) ?>

      <form id="product-form" class="space-y-7" novalidate
            data-quote-url="<?= e(url('/api/products/' . $product['slug'] . '/quote')) ?>"
            data-slug="<?= e($product['slug']) ?>">

        <!-- Fabric -->
        <?php if ($options['fabrics']): ?>
          <fieldset>
            <legend class="<?= $legend ?>"><?= e(t('pdp.fabric')) ?></legend>
            <div class="mt-3 grid grid-cols-3 gap-2">
              <?php foreach ($options['fabrics'] as $fab): ?>
                <label class="cursor-pointer">
                  <input type="radio" name="fabric" value="<?= e($fab['slug']) ?>" class="peer sr-only" <?= $sel['fabric'] === $fab['slug'] ? 'checked' : '' ?>>
                  <span class="flex h-full flex-col items-center justify-center gap-0.5 border border-line bg-white/60 px-2 py-3 text-center transition hover:border-gold peer-checked:border-crimson peer-checked:bg-crimson/5 peer-checked:ring-1 peer-checked:ring-crimson peer-focus-visible:outline-2 peer-focus-visible:outline-gold">
                    <span class="text-sm font-medium"><?= e($fab['name']) ?></span>
                    <span class="text-[11px] text-ink-soft"><?= $fab['price_modifier'] > 0 ? e(t('pdp.surcharge', ['price' => money($fab['price_modifier'])])) : '&nbsp;' ?></span>
                  </span>
                </label>
              <?php endforeach; ?>
            </div>
          </fieldset>
        <?php endif; ?>

        <!-- Colour -->
        <fieldset>
          <legend class="<?= $legend ?>">
            <?= e(t('pdp.color')) ?>: <span class="font-sans normal-case tracking-normal text-ink" data-color-name><?= e($quote['color_name']) ?></span>
          </legend>
          <div class="mt-3 flex flex-wrap gap-3">
            <?php foreach ($options['colors'] as $c): ?>
              <label class="cursor-pointer" title="<?= e($c['name']) ?>">
                <input type="radio" name="color" value="<?= e($c['hex']) ?>" data-name="<?= e($c['name']) ?>" class="peer sr-only" <?= $sel['color'] === $c['hex'] ? 'checked' : '' ?>>
                <span class="block h-10 w-10 rounded-full border border-ink/15 ring-offset-2 ring-offset-ivory transition peer-checked:ring-2 peer-checked:ring-crimson peer-focus-visible:ring-2 peer-focus-visible:ring-gold"
                      style="background:<?= e($c['hex']) ?>"></span>
                <span class="sr-only"><?= e($c['name']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </fieldset>

        <!-- Size -->
        <fieldset>
          <div class="flex items-center justify-between">
            <legend class="<?= $legend ?>"><?= e(t('pdp.size')) ?></legend>
            <?php if (!$options['free_size']): ?>
              <button type="button" data-dialog-open="size-guide" class="text-xs font-medium text-crimson underline underline-offset-4 hover:text-crimson-dark"><?= e(t('pdp.sizeGuide')) ?></button>
            <?php endif; ?>
          </div>

          <?php if ($options['free_size']): ?>
            <input type="hidden" name="size" value="FREE">
            <p class="mt-3 inline-block border border-line bg-white/60 px-4 py-2 text-sm"><?= e(t('pdp.freeSize')) ?></p>
          <?php else: ?>
            <div class="mt-3 flex flex-wrap gap-2">
              <?php foreach ($options['sizes'] as $size): $oos = ($quote['size_stock'][$size] ?? 0) <= 0; ?>
                <label class="cursor-pointer">
                  <input type="radio" name="size" value="<?= e($size) ?>" class="peer sr-only" <?= $sel['size'] === $size ? 'checked' : '' ?>>
                  <span data-size-chip="<?= e($size) ?>" <?= $oos ? 'data-oos' : '' ?>
                        class="flex h-11 min-w-14 items-center justify-center border border-line bg-white/60 px-3 text-sm font-medium transition hover:border-gold peer-checked:border-crimson peer-checked:bg-crimson peer-checked:text-ivory peer-focus-visible:outline-2 peer-focus-visible:outline-gold data-[oos]:text-ink-soft/60 data-[oos]:line-through">
                    <?= e($size) ?>
                  </span>
                </label>
              <?php endforeach; ?>
              <?php if ($options['custom']): ?>
                <label class="cursor-pointer">
                  <input type="radio" name="size" value="<?= Tailoring::CUSTOM ?>" class="peer sr-only" <?= $isCustom ? 'checked' : '' ?>>
                  <span class="flex h-11 items-center justify-center gap-1.5 border border-dashed border-gold bg-gold/10 px-4 text-sm font-medium text-crimson transition hover:border-crimson peer-checked:border-solid peer-checked:border-crimson peer-checked:bg-crimson peer-checked:text-ivory peer-focus-visible:outline-2 peer-focus-visible:outline-gold">
                    <?= icon('scissors', 'h-4 w-4') ?> <?= e(t('pdp.customSize')) ?>
                  </span>
                </label>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </fieldset>

        <!-- Custom tailoring -->
        <?php if ($options['custom']): ?>
          <div class="<?= $isCustom ? '' : 'hidden' ?> border border-gold/50 bg-silk/70 p-5" data-custom-panel>
            <div class="flex items-start gap-3">
              <span class="rounded-full bg-crimson p-2 text-ivory"><?= icon('scissors', 'h-4 w-4') ?></span>
              <div>
                <p class="font-serif text-xl font-semibold"><?= e(t('pdp.custom.title')) ?></p>
                <p class="mt-1 text-sm text-ink-soft"><?= e(t('pdp.custom.intro', ['days' => Tailoring::LEAD_TIME_DAYS])) ?></p>
                <p class="mt-1 text-sm"><?= e(t('pdp.custom.fee')) ?>: <strong class="text-crimson">+<?= money($product['custom_tailoring_fee']) ?></strong></p>
              </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-2 border-y border-gold/30 py-3 text-sm" data-saved-measurements
                 data-profiles="<?= e(json_encode($savedMeasurements, JSON_UNESCAPED_UNICODE)) ?>">
              <?php if (App\Support\Customer::user()): ?>
                <label class="flex items-center gap-2 <?= $savedMeasurements ? '' : 'hidden' ?>" data-saved-picker>
                  <span class="font-medium"><?= icon('ruler', 'inline h-4 w-4 text-crimson') ?> <?= e(t('pdp.custom.useSaved')) ?></span>
                  <select class="input w-auto py-1.5!" data-saved-select>
                    <option value=""><?= e(t('pdp.custom.choose')) ?></option>
                    <?php foreach ($savedMeasurements as $sm): ?><option value="<?= $sm['id'] ?>"><?= e($sm['name']) ?></option><?php endforeach; ?>
                  </select>
                </label>
                <button type="button" class="ml-auto text-xs font-medium text-crimson underline underline-offset-4" data-save-measurements>
                  <?= e(t('pdp.custom.saveProfile')) ?></button>
                <div class="hidden w-full items-center gap-2" data-save-row data-url="<?= e(url('/api/account/measurements?from=pdp')) ?>">
                  <input type="text" maxlength="60" class="input flex-1 py-1.5!" placeholder="<?= e(t('pdp.custom.saveName')) ?>" data-save-name aria-label="<?= e(t('account.measurements.name')) ?>">
                  <button type="button" class="btn-primary px-4! py-1.5!" data-save-confirm><?= e(t('account.measurements.save')) ?></button>
                </div>
              <?php else: ?>
                <a href="<?= e(url('/dang-nhap', ['next' => '/san-pham/' . $product['slug']])) ?>" class="inline-flex items-center gap-1.5 text-crimson underline underline-offset-4">
                  <?= icon('ruler', 'h-4 w-4') ?> <?= e(t('pdp.custom.loginToUse')) ?></a>
              <?php endif; ?>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-3">
              <?php foreach ($fields as $key => [$min, $max, $unit]): ?>
                <label class="block">
                  <span class="text-xs font-medium text-ink"><?= e($fieldLabels[$key]) ?></span>
                  <span class="relative mt-1 block">
                    <input type="number" name="measurements[<?= e($key) ?>]" min="<?= $min ?>" max="<?= $max ?>" step="0.5" inputmode="decimal"
                           class="input pr-10 aria-[invalid=true]:border-crimson aria-[invalid=true]:ring-2 aria-[invalid=true]:ring-crimson/20"
                           placeholder="<?= $min ?>–<?= $max ?>" aria-describedby="err-<?= e($key) ?>">
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-ink-soft"><?= e($unit) ?></span>
                  </span>
                  <span id="err-<?= e($key) ?>" class="mt-1 block min-h-4 text-xs text-crimson" data-error-for="<?= e($key) ?>"></span>
                </label>
              <?php endforeach; ?>
            </div>

            <label class="mt-2 block">
              <span class="text-xs font-medium text-ink"><?= e(t('pdp.custom.note')) ?></span>
              <textarea name="measurements[note]" rows="2" maxlength="300" class="input mt-1 resize-y" placeholder="<?= e(t('pdp.custom.notePlaceholder')) ?>"></textarea>
            </label>

            <p class="mt-3 hidden items-center gap-2 text-sm text-gold-dark" data-suggest
               data-template="<?= e(t('pdp.custom.suggest', ['size' => '__SIZE__'])) ?>"></p>
          </div>
        <?php endif; ?>

        <!-- Stock status -->
        <p class="flex items-center gap-2 text-sm" data-quote-status aria-live="polite">
          <span class="h-2 w-2 rounded-full <?= $statusDot[$quote['status']] ?>" data-status-dot></span>
          <span data-status-text><?= e($quote['status_text']) ?></span>
        </p>

        <!-- Quantity + add to cart -->
        <div class="flex gap-3">
          <div class="flex h-12 items-center border border-line bg-white/60">
            <button type="button" class="h-full px-3 text-lg transition hover:text-crimson" data-qty="-1" aria-label="<?= e(t('pdp.decrease')) ?>">−</button>
            <label class="sr-only" for="qty"><?= e(t('pdp.quantity')) ?></label>
            <input id="qty" type="number" name="qty" value="<?= (int) $sel['qty'] ?>" min="1" max="<?= App\Support\ProductQuote::MAX_QTY ?>"
                   class="h-full w-12 border-0 bg-transparent text-center text-sm [appearance:textfield] focus:outline-none [&::-webkit-inner-spin-button]:appearance-none">
            <button type="button" class="h-full px-3 text-lg transition hover:text-crimson" data-qty="1" aria-label="<?= e(t('pdp.increase')) ?>">+</button>
          </div>
          <button type="submit" class="btn-primary h-12 flex-1" data-add-to-cart <?= $quote['available'] ? '' : 'disabled' ?>
                  data-label="<?= e(t('pdp.addToCart')) ?>" data-busy-label="<?= e(t('pdp.checking')) ?>">
            <?= icon('shopping-bag', 'h-4 w-4') ?> <span><?= e(t('pdp.addToCart')) ?></span>
          </button>
          <?= View::partial('partials/wishlist-button', ['id' => (int) $product['id'], 'saved' => App\Support\Wishlist::has((int) $product['id']), 'large' => true, 'class' => 'shrink-0']) ?>
        </div>
        <p class="-mt-4 hidden text-sm" data-form-message role="status"></p>
      </form>

      <!-- Promises -->
      <ul class="mt-8 grid gap-3 border-y border-line py-5 text-sm sm:grid-cols-2">
        <?php foreach (array_slice(Lang::raw('home.promises'), 0, 4) as $p): ?>
          <li class="flex items-center gap-2.5"><span class="text-crimson"><?= icon($p['icon'], 'h-4 w-4') ?></span><?= e($p['title']) ?></li>
        <?php endforeach; ?>
      </ul>

      <!-- Details (accordions work without JavaScript) -->
      <div class="mt-2 divide-y divide-line">
        <?php
        $sections = [
            'history'  => [t('pdp.tabs.history'), $product['historical_context'], true],
            'fabric'   => [t('pdp.tabs.fabric'), t('pdp.fabricCare'), false],
            'shipping' => [t('pdp.tabs.shipping'), t('pdp.shippingInfo'), false],
        ];
        foreach ($sections as $key => [$heading, $body, $open]): if (!$body) continue; ?>
          <details class="group py-4" <?= $open ? 'open' : '' ?>>
            <summary class="flex cursor-pointer list-none items-center justify-between font-serif text-xl font-semibold marker:hidden [&::-webkit-details-marker]:hidden">
              <?= e($heading) ?>
              <span class="text-gold transition group-open:rotate-90"><?= icon('chevron-right', 'h-5 w-5') ?></span>
            </summary>
            <div class="mt-3 leading-relaxed text-ink-soft <?= $key === 'history' ? 'border-l-2 border-gold/60 pl-4' : '' ?>">
              <?= nl2br(e($body)) ?>
            </div>
          </details>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</div>

<?= View::partial('partials/reviews/section', ['product' => $product, 'summary' => $reviewSummary, 'list' => $reviewList,
                                                'eligibility' => $eligibility, 'freeSize' => $options['free_size']]) ?>

<?php if ($related): ?>
  <section class="border-t border-line bg-silk/40 py-16">
    <div class="container-page">
      <div class="text-center">
        <h2 class="text-4xl"><?= e(t('pdp.related')) ?></h2>
        <?= View::partial('partials/motif-divider', ['class' => 'mt-5']) ?>
      </div>
      <div class="mt-10 grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 lg:grid-cols-4">
        <?= View::partial('partials/product-grid', ['products' => $related]) ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if (!$options['free_size']): ?>
  <?= View::partial('partials/size-guide', ['sizeChart' => $sizeChart, 'fields' => $fields]) ?>
<?php endif; ?>
