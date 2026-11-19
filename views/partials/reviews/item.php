<?php
/** A single published review. @var array $r */
use App\Services\Reviews;
?>
<article class="border-b border-line py-6" id="review-<?= (int) $r['id'] ?>">
  <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
    <?= stars((float) $r['rating']) ?>
    <?php if ($r['title']): ?><h3 class="font-sans text-base font-semibold"><?= e($r['title']) ?></h3><?php endif; ?>
  </div>
  <p class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-ink-soft">
    <span class="font-medium text-ink"><?= e(Reviews::displayName($r['author_name'])) ?></span>
    <?php if ($r['order_id'] || $r['user_id']): ?>
      <span class="inline-flex items-center gap-1 text-emerald-700"><?= icon('check', 'h-3 w-3') ?> <?= e(t('reviews.verified')) ?></span>
    <?php endif; ?>
    <span>· <time datetime="<?= e(date('c', strtotime($r['created_at']))) ?>"><?= e(date('d/m/Y', strtotime($r['created_at']))) ?></time></span>
    <?php if ($r['variant_label']): ?><span>· <?= e($r['variant_label']) ?></span><?php endif; ?>
    <?php if ($r['fit']): ?><span class="rounded-sm bg-silk px-1.5 py-0.5 text-ink"><?= e(t('reviews.fit.' . $r['fit'])) ?></span><?php endif; ?>
  </p>
  <p class="mt-3 whitespace-pre-line leading-relaxed"><?= e($r['body']) ?></p>

  <?php if ($r['images']): ?>
    <div class="mt-3 flex flex-wrap gap-2">
      <?php foreach ($r['images'] as $url): ?>
        <button type="button" data-review-image="<?= e(url($url)) ?>" class="h-20 w-20 overflow-hidden border border-line transition hover:border-gold" aria-label="<?= e(t('reviews.viewPhoto')) ?>">
          <img src="<?= e(url($url)) ?>" alt="" loading="lazy" class="h-full w-full object-cover">
        </button>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($r['reply']): ?>
    <div class="mt-4 border-l-2 border-gold bg-silk/60 px-4 py-3 text-sm">
      <p class="flex items-center gap-1.5 font-semibold text-crimson"><?= lotus('h-4 w-4 text-gold') ?> <?= e(t('reviews.shopReply')) ?></p>
      <p class="mt-1 whitespace-pre-line text-ink-soft"><?= e($r['reply']) ?></p>
    </div>
  <?php endif; ?>

  <button type="button" data-review-helpful="<?= e(url('/api/reviews/' . $r['id'] . '/helpful')) ?>" aria-pressed="<?= !empty($r['voted']) ? 'true' : 'false' ?>"
          <?= !empty($r['voted']) ? 'disabled' : '' ?>
          class="mt-4 inline-flex items-center gap-1.5 rounded-full border border-line px-3 py-1 text-xs text-ink-soft transition hover:border-gold hover:text-crimson aria-pressed:border-crimson aria-pressed:text-crimson">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5" aria-hidden="true"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/></svg>
    <span><?= (int) $r['helpful_count'] ? e(t('reviews.helpfulCount', ['count' => (int) $r['helpful_count']])) : e(t('reviews.helpful')) ?></span>
  </button>
</article>
