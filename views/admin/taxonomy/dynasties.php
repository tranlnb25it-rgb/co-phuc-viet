<?php
/** @var array $lists */
use App\Core\View;

$fields = [
    ['name' => 'name_vi', 'label' => 'Tên (VI)'],
    ['name' => 'name_en', 'label' => 'Tên (EN)'],
    ['name' => 'slug', 'label' => 'Slug', 'placeholder' => 'tự tạo nếu bỏ trống'],
    ['name' => 'period', 'label' => 'Giai đoạn', 'placeholder' => '1428 – 1789'],
    ['name' => 'sort_order', 'label' => 'Thứ tự', 'type' => 'number'],
];
$grid = 'grid-cols-1 md:grid-cols-[1.3fr_1.3fr_1.2fr_1fr_70px_150px]';
?>
<div class="border border-line bg-white/70">
  <div class="hidden gap-2 border-b border-line bg-silk/60 px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-ink-soft md:grid <?= $grid ?>">
    <span>Tên (VI)</span><span>Tên (EN)</span><span>Slug</span><span>Giai đoạn</span><span>Thứ tự</span><span></span>
  </div>
  <div class="divide-y divide-line">
    <?php foreach ($lists['dynasties'] as $d): ?>
      <?= View::partial('admin/taxonomy/row', ['type' => 'dynasties', 'row' => $d, 'fields' => $fields, 'grid' => $grid, 'usage' => $d['product_count'] . ' SP']) ?>
    <?php endforeach; ?>
    <?= View::partial('admin/taxonomy/row', ['type' => 'dynasties', 'row' => null, 'fields' => $fields, 'grid' => $grid, 'usage' => null]) ?>
  </div>
</div>
