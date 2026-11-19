<?php
/** @var array $result @var array $filters */
use App\Core\View;
?>
<div class="mb-3 flex items-center justify-between text-sm text-ink-soft">
  <span><?= (int) $result['total'] ?> đánh giá</span>
  <?php if ($result['items']): ?>
    <label class="flex items-center gap-2"><input type="checkbox" class="h-4 w-4 accent-crimson" data-review-check-all> Chọn tất cả trang này</label>
  <?php endif; ?>
</div>
<?php if (!$result['items']): ?>
  <div class="border border-dashed border-line px-6 py-16 text-center text-ink-soft">Không có đánh giá nào.</div>
<?php else: ?>
  <div class="space-y-3">
    <?php foreach ($result['items'] as $r) echo View::partial('admin/reviews/card', ['r' => $r]); ?>
  </div>
  <?= View::partial('admin/partials/pagination', ['result' => $result, 'path' => '/admin/danh-gia',
      'filters' => ['status' => $filters['status'], 'rating' => $filters['rating'], 'q' => $filters['q']]]) ?>
<?php endif; ?>
