<?php
/**
 * Reviews block on the product page.
 * @var array $product @var array $summary Reviews::summary() @var array $list Reviews::forProduct()
 * @var array $eligibility Reviews::eligibility() @var bool $freeSize hides the size-fit question
 */
use App\Core\View;
use App\Services\Reviews;

$s = $summary;
$api = url('/api/products/' . $product['slug'] . '/reviews');
$fmt = fn(float $v) => number_format($v, 1, ',', '');
?>
<section id="reviews" class="scroll-mt-28 border-t border-line py-16" data-reviews="<?= e($api) ?>">
  <div class="container-page">
    <div class="text-center">
      <h2 class="text-4xl"><?= e(t('reviews.title')) ?></h2>
      <?= View::partial('partials/motif-divider', ['class' => 'mt-5']) ?>
    </div>

    <div class="mt-10 grid gap-10 lg:grid-cols-[340px_1fr] lg:gap-14">
      <!-- ===== Summary + write ===== -->
      <aside class="space-y-8 lg:sticky lg:top-28 lg:self-start">
        <?php if ($s['count']): ?>
          <div class="flex items-center gap-5">
            <p class="font-serif text-6xl font-semibold leading-none text-crimson"><?= e($fmt($s['avg'])) ?></p>
            <div>
              <?= stars($s['avg'], 'h-5 w-5') ?>
              <p class="mt-1 text-sm text-ink-soft"><?= e(t('reviews.basedOn', ['count' => $s['count']])) ?></p>
            </div>
          </div>
          <!-- distribution: each bar filters the list -->
          <ul class="space-y-1.5">
            <?php foreach ($s['dist'] as $star => $n): $pct = $s['count'] ? round($n / $s['count'] * 100) : 0; ?>
              <li>
                <button type="button" data-review-filter="rating" data-value="<?= $star ?>" <?= $n ? '' : 'disabled' ?>
                        class="group flex w-full items-center gap-3 text-sm disabled:cursor-default disabled:opacity-50" aria-label="<?= e(t('reviews.star', ['n' => $star])) ?>: <?= $n ?>">
                  <span class="w-10 shrink-0 text-left tabular-nums group-enabled:group-hover:text-crimson"><?= $star ?> ★</span>
                  <span class="h-2 flex-1 overflow-hidden rounded-full bg-line"><span class="block h-full rounded-full bg-gold transition-all group-enabled:group-hover:bg-crimson" style="width:<?= $pct ?>%"></span></span>
                  <span class="w-9 shrink-0 text-right tabular-nums text-ink-soft"><?= $pct ?>%</span>
                </button>
              </li>
            <?php endforeach; ?>
          </ul>

          <?php if ($s['fit_total'] && !$freeSize): ?>
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gold-dark"><?= e(t('reviews.fitTitle')) ?></p>
              <div class="mt-3 flex h-2 overflow-hidden rounded-full bg-line">
                <?php foreach (['small' => 'bg-sky-600', 'true' => 'bg-emerald-600', 'large' => 'bg-gold'] as $k => $cls): if (!$s['fit'][$k]) continue; ?>
                  <span class="<?= $cls ?>" style="width:<?= round($s['fit'][$k] / $s['fit_total'] * 100) ?>%"></span>
                <?php endforeach; ?>
              </div>
              <ul class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-ink-soft">
                <?php foreach (['small' => 'bg-sky-600', 'true' => 'bg-emerald-600', 'large' => 'bg-gold'] as $k => $cls): ?>
                  <li class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full <?= $cls ?>"></span><?= e(t('reviews.fit.' . $k)) ?> <?= round($s['fit'][$k] / $s['fit_total'] * 100) ?>%</li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <?php if ($s['photos']): ?>
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gold-dark"><?= e(t('reviews.photosTitle')) ?></p>
              <div class="mt-3 grid grid-cols-4 gap-2">
                <?php foreach ($s['photos'] as $ph): ?>
                  <button type="button" data-review-image="<?= e(url($ph['url'])) ?>" class="aspect-square overflow-hidden border border-line transition hover:border-gold" aria-label="<?= e(t('reviews.viewPhoto')) ?>">
                    <img src="<?= e(url($ph['url'])) ?>" alt="" loading="lazy" class="h-full w-full object-cover">
                  </button>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="border border-dashed border-line p-6 text-center">
            <?= stars(0, 'h-6 w-6') ?>
            <p class="mt-3 font-serif text-2xl"><?= e(t('reviews.none')) ?></p>
            <p class="mt-1 text-sm text-ink-soft"><?= e(t('reviews.noneText')) ?></p>
          </div>
        <?php endif; ?>

        <?= View::partial('partials/reviews/form', ['product' => $product, 'eligibility' => $eligibility, 'freeSize' => $freeSize]) ?>
      </aside>

      <!-- ===== List ===== -->
      <div class="min-w-0">
        <?php if ($s['count']): ?>
          <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line pb-4" data-review-toolbar>
            <div class="flex flex-wrap gap-2">
              <?php
              $chip = 'rounded-full border px-3 py-1 text-sm transition aria-pressed:border-crimson aria-pressed:bg-crimson aria-pressed:text-ivory border-line bg-white/60 hover:border-gold';
              ?>
              <button type="button" class="<?= $chip ?>" data-review-filter="all" aria-pressed="true"><?= e(t('reviews.all')) ?></button>
              <?php foreach ([5, 4, 3, 2, 1] as $star): if (!$s['dist'][$star]) continue; ?>
                <button type="button" class="<?= $chip ?>" data-review-filter="rating" data-value="<?= $star ?>" aria-pressed="false"><?= $star ?> ★ (<?= $s['dist'][$star] ?>)</button>
              <?php endforeach; ?>
              <?php if ($s['photo_reviews']): ?>
                <button type="button" class="<?= $chip ?>" data-review-filter="photos" data-value="1" aria-pressed="false"><?= e(t('reviews.withPhotos', ['count' => $s['photo_reviews']])) ?></button>
              <?php endif; ?>
            </div>
            <label class="flex items-center gap-2 text-sm">
              <span class="text-ink-soft"><?= e(t('reviews.sortLabel')) ?></span>
              <select class="input w-auto py-1.5!" data-review-sort>
                <?php foreach (array_keys(Reviews::SORTS) as $k): ?><option value="<?= $k ?>"><?= e(t('reviews.sort.' . $k)) ?></option><?php endforeach; ?>
              </select>
            </label>
          </div>
        <?php endif; ?>
        <div class="transition-opacity" data-review-list aria-live="polite">
          <?= $s['count'] ? View::partial('partials/reviews/items', ['list' => $list]) : '' ?>
        </div>
      </div>
    </div>
  </div>

  <dialog id="review-lightbox" class="m-auto max-h-[90vh] max-w-[min(900px,calc(100vw-2rem))] border-0 bg-transparent p-0 backdrop:bg-ink/80">
    <button type="button" data-dialog-close class="absolute right-2 top-2 rounded-full bg-ivory/90 p-1.5 text-ink shadow" aria-label="<?= e(t('nav.close')) ?>"><?= icon('x') ?></button>
    <img src="" alt="" class="max-h-[90vh] w-auto object-contain" data-lightbox-img>
  </dialog>
</section>
