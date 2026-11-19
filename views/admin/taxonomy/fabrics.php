<?php
/** @var array $lists */
use App\Core\View;

$fields = [
    ['name' => 'name_vi', 'label' => 'Tên (VI)'],
    ['name' => 'name_en', 'label' => 'Tên (EN)'],
    ['name' => 'slug', 'label' => 'Slug', 'placeholder' => 'tự tạo nếu bỏ trống'],
    ['name' => 'price_modifier', 'label' => 'Phụ phí (₫)', 'type' => 'number'],
];
$grid = 'grid-cols-1 md:grid-cols-[1.3fr_1.3fr_1.2fr_1fr_150px]';
?>
<p class="mb-3 text-sm text-ink-soft">Phụ phí được cộng vào giá gốc khi khách chọn chất liệu này. Đổi phụ phí sẽ áp dụng ngay cho đơn mới (đơn cũ giữ giá lúc đặt).</p>
<div class="border border-line bg-white/70">
  <div class="hidden gap-2 border-b border-line bg-silk/60 px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-ink-soft md:grid <?= $grid ?>">
    <span>Tên (VI)</span><span>Tên (EN)</span><span>Slug</span><span>Phụ phí</span><span></span>
  </div>
  <div class="divide-y divide-line">
    <?php foreach ($lists['fabrics'] as $f): ?>
      <?= View::partial('admin/taxonomy/row', ['type' => 'fabrics', 'row' => $f, 'fields' => $fields, 'grid' => $grid, 'usage' => $f['variant_count'] . ' biến thể']) ?>
    <?php endforeach; ?>
    <?= View::partial('admin/taxonomy/row', ['type' => 'fabrics', 'row' => null, 'fields' => $fields, 'grid' => $grid, 'usage' => null]) ?>
  </div>
</div>
