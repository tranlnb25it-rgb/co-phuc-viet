<?php
/**
 * "Write a review" — the form for eligible customers, otherwise a short explanation.
 * @var array $product @var array $eligibility Reviews::eligibility() @var bool $freeSize
 */
use App\Core\Lang;
use App\Services\Reviews;

$el = $eligibility;
$words = Lang::raw('reviews.ratingWords');
?>
<div id="write-review" class="scroll-mt-28" data-review-form-box>
<?php if ($el['state'] !== 'can'): ?>
  <div class="flex items-start gap-3 border border-line bg-silk/60 p-4 text-sm">
    <span class="text-crimson"><?= icon($el['state'] === 'reviewed' ? 'check' : 'lock', 'h-5 w-5') ?></span>
    <div>
      <?php if ($el['state'] === 'reviewed' && $el['review']['status'] === 'pending'): ?>
        <p><?= e(t('reviews.state.pending')) ?></p>
      <?php else: ?>
        <p><?= e(t('reviews.state.' . $el['state'])) ?></p>
      <?php endif; ?>
      <?php if ($el['state'] === 'guest'): ?>
        <a href="<?= e(url('/dang-nhap', ['next' => '/san-pham/' . $product['slug']])) ?>" class="mt-2 inline-block font-medium text-crimson underline underline-offset-4"><?= e(t('reviews.login')) ?></a>
      <?php endif; ?>
    </div>
  </div>
<?php else: ?>
  <form class="border border-gold/50 bg-silk/50 p-5" data-review-form="<?= e(url('/api/products/' . $product['slug'] . '/reviews')) ?>" novalidate>
    <h3 class="text-2xl"><?= e(t('reviews.writeTitle')) ?></h3>
    <?php if (!empty($el['variant'])): ?><p class="mt-1 text-xs text-ink-soft"><?= e(t('reviews.boughtAs', ['variant' => $el['variant']])) ?></p><?php endif; ?>

    <fieldset class="mt-4">
      <legend class="text-sm font-medium"><?= e(t('reviews.yourRating')) ?></legend>
      <div class="mt-2 flex items-center gap-3">
        <!-- reversed DOM order so "peer" hover/checked can colour every star up to the chosen one -->
        <div class="flex flex-row-reverse justify-end gap-1 [&>input:checked~label]:text-gold [&>label:hover]:text-gold [&>label:hover~label]:text-gold" data-star-picker>
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" name="rating" value="<?= $i ?>" id="rv-star-<?= $i ?>" class="peer sr-only" data-word="<?= e($words[$i]) ?>">
            <label for="rv-star-<?= $i ?>" class="cursor-pointer text-line transition hover:scale-110" title="<?= e($words[$i]) ?>">
              <svg viewBox="0 0 24 24" fill="currentColor" class="h-8 w-8" aria-hidden="true"><path d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.6L12 17.3l-5.9 3.3 1.3-6.6-4.9-4.6 6.6-.8z"/></svg>
              <span class="sr-only"><?= $i ?> — <?= e($words[$i]) ?></span>
            </label>
          <?php endfor; ?>
        </div>
        <span class="text-sm font-medium text-crimson" data-star-word></span>
      </div>
      <span class="mt-1 block min-h-4 text-xs text-crimson" data-error-for="rating"></span>
    </fieldset>

    <label class="block text-sm"><span class="font-medium"><?= e(t('reviews.titleLabel')) ?></span>
      <input name="title" maxlength="120" class="input mt-1" placeholder="<?= e(t('reviews.titlePlaceholder')) ?>"></label>

    <label class="mt-3 block text-sm"><span class="font-medium"><?= e(t('reviews.bodyLabel')) ?></span>
      <textarea name="body" rows="5" maxlength="<?= Reviews::MAX_BODY ?>" class="input mt-1 resize-y aria-[invalid=true]:border-crimson"
                placeholder="<?= e(t('reviews.bodyPlaceholder')) ?>" data-counter="<?= e(t('reviews.chars', ['count' => '__N__', 'max' => Reviews::MAX_BODY])) ?>"></textarea></label>
    <p class="mt-1 flex justify-between text-xs"><span class="text-crimson" data-error-for="body"></span><span class="text-ink-soft" data-counter-out></span></p>

    <?php if (!$freeSize): ?>
      <fieldset class="mt-3">
        <legend class="text-sm font-medium"><?= e(t('reviews.fitQuestion')) ?></legend>
        <div class="mt-2 flex flex-wrap gap-2">
          <?php foreach (Reviews::FITS as $k): ?>
            <label class="cursor-pointer">
              <input type="radio" name="fit" value="<?= $k ?>" class="peer sr-only">
              <span class="inline-block rounded-full border border-line bg-white/60 px-3 py-1 text-sm transition hover:border-gold peer-checked:border-crimson peer-checked:bg-crimson peer-checked:text-ivory peer-focus-visible:outline-2 peer-focus-visible:outline-gold"><?= e(t('reviews.fit.' . $k)) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </fieldset>
    <?php endif; ?>

    <div class="mt-4">
      <p class="text-sm font-medium"><?= e(t('reviews.photos', ['max' => Reviews::MAX_IMAGES])) ?></p>
      <div class="mt-2 flex flex-wrap gap-2" data-photo-previews>
        <label class="flex h-20 w-20 cursor-pointer flex-col items-center justify-center gap-1 border border-dashed border-gold/70 bg-white/60 text-xs text-ink-soft transition hover:border-crimson hover:text-crimson" data-photo-add>
          <?= icon('plus', 'h-5 w-5') ?>
          <input type="file" accept="image/jpeg,image/png,image/webp" multiple class="sr-only" data-photo-input data-max="<?= Reviews::MAX_IMAGES ?>">
        </label>
      </div>
      <span class="mt-1 block min-h-4 text-xs text-crimson" data-error-for="images"></span>
    </div>

    <p class="hidden text-sm text-crimson" data-form-error role="alert"></p>
    <button class="btn-primary mt-3 w-full" data-label="<?= e(t('reviews.submit')) ?>" data-busy-label="<?= e(t('reviews.submitting')) ?>"
            data-remove-label="<?= e(t('reviews.removePhoto')) ?>"><span><?= e(t('reviews.submit')) ?></span></button>
  </form>
<?php endif; ?>
</div>
