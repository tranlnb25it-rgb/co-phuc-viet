<?php
/** Image grid (page render + Ajax). Drag to reorder; change type per image. @var array $product */
use App\Services\AdminProductService as Products;

$tint = $product['variants'][0]['color_hex'] ?? null;
?>
<?php if (!$product['images']): ?>
  <p class="border border-dashed border-line px-6 py-10 text-center text-sm text-ink-soft">Chưa có ảnh.</p>
<?php else: ?>
  <p class="mb-3 text-xs text-ink-soft">Kéo thả để sắp xếp. Trên cửa hàng, ảnh được nhóm theo loại (mặt trước → mặt sau → chi tiết) rồi theo thứ tự này.</p>
  <ul class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-6" data-image-list="<?= e(url('/admin/api/products/' . $product['id'] . '/images/order')) ?>">
    <?php foreach ($product['images'] as $img): $placeholder = str_starts_with($img['url'], 'placeholder:'); ?>
      <li class="group relative cursor-grab active:cursor-grabbing" draggable="true" data-image-id="<?= (int) $img['id'] ?>">
        <div class="pointer-events-none aspect-[3/4] overflow-hidden border border-line bg-silk transition group-[.is-dragging]:opacity-40 [&_span]:text-[10px]">
          <?= product_image($img['url'], $product['name_vi'], $tint) ?>
        </div>
        <div class="mt-1 flex items-center gap-1">
          <select class="min-w-0 flex-1 rounded-sm border border-line bg-white px-1.5 py-1 text-xs" aria-label="Loại ảnh"
                  data-image-type="<?= e(url('/admin/api/images/' . $img['id'])) ?>">
            <?php foreach (Products::VIEW_TYPES as $k => $label): ?><option value="<?= $k ?>" <?= $img['view_type'] === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
          </select>
          <button type="button" class="rounded-sm p-1 text-ink-soft hover:bg-crimson/10 hover:text-crimson" aria-label="Xoá ảnh"
                  data-delete-image="<?= e(url('/admin/api/images/' . $img['id'])) ?>"><?= icon('trash', 'h-3.5 w-3.5') ?></button>
        </div>
        <?php if ($placeholder): ?><p class="text-[10px] text-ink-soft">ảnh tạm</p><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
