<?php
/** Product table (page render + Ajax). @var array $result @var array $filters */
use App\Core\View;
use App\Services\AdminProductService as Products;

$switch = function (array $p, string $field, string $label): string {
    $on = (int) $p[$field] === 1;
    return '<button type="button" role="switch" aria-checked="' . ($on ? 'true' : 'false') . '" aria-label="' . e($label) . '"'
         . ' data-toggle-url="' . e(url('/admin/api/products/' . $p['id'] . '/toggle')) . '" data-field="' . $field . '"'
         . ' class="group relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition aria-checked:bg-crimson bg-line">'
         . '<span class="inline-block h-4 w-4 translate-x-0.5 rounded-full bg-white shadow transition group-aria-checked:translate-x-4.5"></span></button>';
};
?>
<p class="mb-3 text-sm text-ink-soft"><?= (int) $result['total'] ?> sản phẩm</p>

<?php if (!$result['items']): ?>
  <div class="border border-dashed border-line px-6 py-16 text-center text-ink-soft">Không có sản phẩm phù hợp.</div>
<?php else: ?>
<div class="overflow-x-auto border border-line bg-white/70">
  <table class="w-full min-w-[900px] text-sm">
    <thead class="border-b border-line bg-silk/60 text-left text-xs uppercase tracking-wider text-ink-soft">
      <tr>
        <th class="w-10 py-3 pl-4"><input type="checkbox" class="h-4 w-4 accent-crimson" aria-label="Chọn tất cả sản phẩm trên trang" data-select-all></th>
        <th class="px-4 py-3 font-semibold">Sản phẩm</th>
        <th class="px-3 py-3 font-semibold">Danh mục</th>
        <th class="px-3 py-3 text-right font-semibold">Giá gốc</th>
        <th class="px-3 py-3 text-right font-semibold">Tồn kho</th>
        <th class="px-3 py-3 text-center font-semibold">Đang bán</th>
        <th class="px-3 py-3 text-center font-semibold">Nổi bật</th>
        <th class="px-4 py-3"><span class="sr-only">Thao tác</span></th>
      </tr>
    </thead>
    <tbody class="divide-y divide-line">
      <?php foreach ($result['items'] as $p): ?>
        <tr class="hover:bg-silk/40 has-[:checked]:bg-gold/10" data-row>
          <td class="py-3 pl-4"><input type="checkbox" value="<?= (int) $p['id'] ?>" class="h-4 w-4 accent-crimson" aria-label="Chọn <?= e($p['name_vi']) ?>" data-select></td>
          <td class="px-4 py-3">
            <a href="<?= e(url('/admin/san-pham/' . $p['id'])) ?>" class="flex items-center gap-3">
              <span class="block h-14 w-11 shrink-0 overflow-hidden border border-line bg-silk [&_span]:hidden [&_svg]:h-5 [&_svg]:w-5">
                <?= product_image($p['image'], $p['name_vi'], $p['tint']) ?>
              </span>
              <span class="min-w-0">
                <span class="block font-medium hover:text-crimson"><?= e($p['name_vi']) ?></span>
                <span class="block truncate font-mono text-[11px] text-ink-soft"><?= e($p['slug']) ?></span>
              </span>
            </a>
          </td>
          <td class="px-3 py-3"><?= e($p['category']) ?><p class="text-xs text-ink-soft"><?= e($p['dynasty'] ?? '—') ?> · <?= e(Products::GENDERS[$p['gender']]) ?></p></td>
          <?php $sale = App\Support\Pricing::info($p); ?>
          <td class="px-3 py-3 text-right font-medium tabular-nums">
            <?php if ($sale['on_sale']): ?>
              <span class="text-crimson"><?= money($sale['price']) ?></span>
              <span class="block text-xs font-normal text-ink-soft"><span class="line-through"><?= money($sale['original']) ?></span> · −<?= $sale['percent'] ?>%</span>
            <?php else: ?>
              <?= money($p['base_price']) ?>
              <?php if ($sale['scheduled']): ?><span class="block text-xs font-normal text-sky-700">Sale từ <?= e(date('d/m', strtotime($p['sale_starts_at']))) ?></span><?php endif; ?>
            <?php endif; ?>
          </td>
          <td class="px-3 py-3 text-right">
            <span class="font-medium"><?= (int) $p['stock'] ?></span>
            <p class="text-xs text-ink-soft"><?= (int) $p['variant_count'] ?> biến thể<?php if ($p['low_count']): ?> · <span class="text-crimson"><?= (int) $p['low_count'] ?> sắp hết</span><?php endif; ?></p>
          </td>
          <td class="px-3 py-3 text-center"><?= $switch($p, 'is_active', 'Đang bán') ?></td>
          <td class="px-3 py-3 text-center"><?= $switch($p, 'is_featured', 'Nổi bật') ?></td>
          <td class="px-4 py-3">
            <div class="flex items-center justify-end gap-1">
              <a href="<?= e(url('/admin/san-pham/' . $p['id'])) ?>" class="rounded-sm px-2 py-1 text-xs font-medium text-crimson hover:bg-crimson/10">Sửa</a>
              <a href="<?= e(url('/san-pham/' . $p['slug'])) ?>" target="_blank" rel="noopener" class="rounded-sm px-2 py-1 text-xs text-ink-soft hover:bg-silk">Xem</a>
              <button type="button" class="rounded-sm p-1.5 text-ink-soft hover:bg-crimson/10 hover:text-crimson" aria-label="Xoá <?= e($p['name_vi']) ?>"
                      data-delete-url="<?= e(url('/admin/api/products/' . $p['id'])) ?>"
                      data-confirm="Xoá vĩnh viễn “<?= e($p['name_vi']) ?>”? Ảnh và tồn kho sẽ bị xoá; đơn hàng cũ vẫn giữ thông tin. Nếu chỉ muốn tạm ngừng bán, hãy tắt “Đang bán”.">
                <?= icon('trash', 'h-4 w-4') ?>
              </button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= View::partial('admin/partials/pagination', ['result' => $result, 'filters' => $filters, 'path' => '/admin/san-pham']) ?>
<?php endif; ?>
