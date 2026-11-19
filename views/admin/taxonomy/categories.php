<?php
/** @var array $lists */
use App\Core\View;

$parents = ['' => '— Cấp 1 —'];
foreach ($lists['categories'] as $c) if ($c['parent_id'] === null) $parents[$c['id']] = $c['name_vi'];
$fields = [
    ['name' => 'name_vi', 'label' => 'Tên (VI)'],
    ['name' => 'name_en', 'label' => 'Tên (EN)'],
    ['name' => 'slug', 'label' => 'Slug', 'placeholder' => 'tự tạo nếu bỏ trống'],
    ['name' => 'parent_id', 'label' => 'Thuộc danh mục', 'type' => 'select', 'options' => $parents],
    ['name' => 'sort_order', 'label' => 'Thứ tự', 'type' => 'number'],
    ['name' => 'description_vi', 'label' => 'Mô tả tiếng Việt (hiện đầu trang Bộ sưu tập)', 'span' => 'md:col-span-3 md:col-start-1', 'show_label' => true],
    ['name' => 'description_en', 'label' => 'Mô tả tiếng Anh', 'span' => 'md:col-span-2', 'show_label' => true],
];
$grid = 'grid-cols-1 md:grid-cols-[1.3fr_1.3fr_1.2fr_1fr_70px_150px]';
?>
<div class="border border-line bg-white/70">
  <div class="hidden gap-2 border-b border-line bg-silk/60 px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-ink-soft md:grid <?= $grid ?>">
    <span>Tên (VI)</span><span>Tên (EN)</span><span>Slug</span><span>Cấp cha</span><span>Thứ tự</span><span></span>
  </div>
  <div class="divide-y divide-line">
    <?php foreach ($lists['categories'] as $c): ?>
      <div class="<?= $c['parent_id'] ? 'border-l-4 border-gold/40' : '' ?>">
        <?= View::partial('admin/taxonomy/row', ['type' => 'categories', 'row' => $c, 'fields' => $fields, 'grid' => $grid,
            'usage' => $c['product_count'] . ' SP' . ($c['child_count'] ? ' · ' . $c['child_count'] . ' con' : '')]) ?>
      </div>
    <?php endforeach; ?>
    <?= View::partial('admin/taxonomy/row', ['type' => 'categories', 'row' => null, 'fields' => $fields, 'grid' => $grid, 'usage' => null]) ?>
  </div>
</div>
