<?php
/** @var array $toReview Reviews::toReview() @var array $mine Reviews::mine() */
$badge = ['pending' => 'bg-gold/20 text-gold-dark', 'approved' => 'bg-emerald-100 text-emerald-800', 'rejected' => 'bg-ink/10 text-ink-soft'];
?>
<h2 class="text-3xl"><?= e(t('reviews.account.title')) ?></h2>

<section class="mt-6">
  <h3 class="flex items-center gap-2 text-2xl"><?= e(t('reviews.account.toReview')) ?>
    <?php if ($toReview): ?><span class="rounded-full bg-crimson px-2 py-0.5 font-sans text-xs font-semibold text-ivory"><?= count($toReview) ?></span><?php endif; ?></h3>
  <p class="mt-1 text-sm text-ink-soft"><?= e(t('reviews.account.toReviewText')) ?></p>
  <?php if ($toReview): ?>
    <div class="mt-4 grid gap-3 sm:grid-cols-2">
      <?php foreach ($toReview as $p): ?>
        <div class="flex items-center gap-4 border border-line bg-white/60 p-3">
          <span class="h-20 w-16 shrink-0 overflow-hidden border border-line bg-silk [&_span]:hidden [&_svg]:h-6 [&_svg]:w-6"><?= product_image($p['image'], $p['name'], $p['tint']) ?></span>
          <div class="min-w-0 flex-1">
            <p class="truncate font-medium"><?= e($p['name']) ?></p>
            <p class="text-xs text-ink-soft"><?= e(t('reviews.account.boughtOn', ['date' => date('d/m/Y', strtotime($p['bought_at']))])) ?></p>
            <a href="<?= e(url('/san-pham/' . $p['slug'])) ?>#write-review" class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-crimson hover:underline"><?= icon('star', 'h-4 w-4') ?> <?= e(t('reviews.write')) ?></a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="mt-4 border border-dashed border-line py-8 text-center text-sm text-ink-soft"><?= e(t('reviews.account.noneToReview')) ?></p>
  <?php endif; ?>
</section>

<section class="mt-10">
  <h3 class="text-2xl"><?= e(t('reviews.account.mine')) ?></h3>
  <?php if ($mine): ?>
    <div class="mt-4 space-y-3">
      <?php foreach ($mine as $r): ?>
        <article class="border border-line bg-white/60 p-4 text-sm">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <a href="<?= e(url('/san-pham/' . $r['slug'])) ?>#reviews" class="font-medium hover:text-crimson"><?= e($r['product_name']) ?></a>
            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold <?= $badge[$r['status']] ?>"><?= e(t('reviews.status.' . $r['status'])) ?></span>
          </div>
          <p class="mt-2 flex items-center gap-2"><?= stars((float) $r['rating']) ?>
            <?php if ($r['title']): ?><strong><?= e($r['title']) ?></strong><?php endif; ?>
            <span class="text-xs text-ink-soft">· <?= e(date('d/m/Y', strtotime($r['created_at']))) ?></span></p>
          <p class="mt-2 whitespace-pre-line text-ink-soft"><?= e($r['body']) ?></p>
          <?php if ($r['images']): ?>
            <div class="mt-2 flex gap-2"><?php foreach ($r['images'] as $u): ?><img src="<?= e(url($u)) ?>" alt="" class="h-14 w-14 border border-line object-cover"><?php endforeach; ?></div>
          <?php endif; ?>
          <?php if ($r['reply']): ?>
            <p class="mt-3 border-l-2 border-gold bg-silk/60 px-3 py-2 text-xs"><strong class="text-crimson"><?= e(t('reviews.shopReply')) ?>:</strong> <?= e($r['reply']) ?></p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="mt-4 border border-dashed border-line py-8 text-center text-sm text-ink-soft"><?= e(t('reviews.account.noneMine')) ?></p>
  <?php endif; ?>
</section>
