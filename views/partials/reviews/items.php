<?php
/** One page of reviews + "show more". Appended / replaced by app.js. @var array $list Reviews::forProduct() */
use App\Core\View;
?>
<?php if (!$list['items']): ?>
  <p class="py-12 text-center text-ink-soft"><?= e(t('reviews.empty')) ?></p>
<?php endif; ?>
<?php foreach ($list['items'] as $r) echo View::partial('partials/reviews/item', ['r' => $r]); ?>
<?php if ($list['page'] < $list['pages']): ?>
  <div class="pt-6 text-center" data-review-more-wrap>
    <button type="button" class="btn-outline" data-review-more="<?= $list['page'] + 1 ?>"><?= e(t('reviews.more')) ?>
      <span class="text-xs opacity-70">(<?= $list['total'] - $list['page'] * App\Services\Reviews::PER_PAGE ?>)</span></button>
  </div>
<?php endif; ?>
