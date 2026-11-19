<?php
/** Variants / stock table + generator (page render + Ajax). @var array $product @var array $fabrics */
use App\Services\AdminProductService as Products;

$variants = $product['variants'];
$total = array_sum(array_column($variants, 'stock'));
?>
<div class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h2 class="text-2xl">Tồn kho theo biến thể</h2>
    <p class="text-xs text-ink-soft"><?= count($variants) ?> biến thể · tổng <strong data-stock-total><?= $total ?></strong> sản phẩm. Sửa số lượng trực tiếp — tự lưu khi rời ô.</p>
  </div>
</div>

<?php if ($variants): ?>
  <div class="mt-4 overflow-x-auto border border-line">
    <table class="w-full min-w-[640px] text-sm">
      <thead class="border-b border-line bg-silk/60 text-left text-xs uppercase tracking-wider text-ink-soft">
        <tr><th class="px-4 py-2.5">Chất liệu</th><th class="px-3 py-2.5">Màu</th><th class="px-3 py-2.5">Size</th><th class="px-3 py-2.5">SKU</th><th class="px-3 py-2.5 text-right">Tồn kho</th><th class="px-4 py-2.5"></th></tr>
      </thead>
      <tbody class="divide-y divide-line">
        <?php foreach ($variants as $vr): $low = (int) $vr['stock'] <= low_stock(); ?>
          <tr class="hover:bg-silk/40">
            <td class="px-4 py-2"><?= e($vr['fabric_name'] ?? '—') ?></td>
            <td class="px-3 py-2"><span class="inline-flex items-center gap-2"><span class="h-4 w-4 rounded-full border border-ink/15" style="background:<?= e($vr['color_hex']) ?>"></span><?= e($vr['color_vi']) ?></span></td>
            <td class="px-3 py-2 font-medium"><?= e($vr['size']) ?></td>
            <td class="px-3 py-2 font-mono text-xs text-ink-soft"><?= e($vr['sku']) ?></td>
            <td class="px-3 py-2 text-right">
              <input type="number" min="0" max="100000" value="<?= (int) $vr['stock'] ?>" aria-label="Tồn kho <?= e($vr['sku']) ?>"
                     data-stock-url="<?= e(url('/admin/api/variants/' . $vr['id'])) ?>" data-saved="<?= (int) $vr['stock'] ?>"
                     class="w-20 rounded-sm border px-2 py-1 text-right transition focus:border-gold focus:outline-none focus:ring-2 focus:ring-gold/30 <?= $low ? 'border-crimson/50 bg-crimson/5 text-crimson' : 'border-line bg-white' ?>">
            </td>
            <td class="px-4 py-2 text-right">
              <button type="button" class="rounded-sm p-1.5 text-ink-soft hover:bg-crimson/10 hover:text-crimson" aria-label="Xoá biến thể <?= e($vr['sku']) ?>"
                      data-delete-variant="<?= e(url('/admin/api/variants/' . $vr['id'])) ?>" data-confirm="Xoá biến thể <?= e($vr['sku']) ?>?">
                <?= icon('trash', 'h-4 w-4') ?>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <p class="mt-4 border border-dashed border-line px-6 py-8 text-center text-sm text-ink-soft">Chưa có biến thể — sản phẩm sẽ hiện “Hết hàng”. Tạo biến thể bên dưới.</p>
<?php endif; ?>

<!-- Generator: one colour × chosen sizes × chosen fabrics -->
<form class="mt-6 border border-gold/40 bg-silk/50 p-5" data-variant-generator="<?= e(url('/admin/api/products/' . $product['id'] . '/variants')) ?>">
  <h3 class="font-serif text-xl font-semibold">Tạo nhanh biến thể</h3>
  <p class="text-xs text-ink-soft">Tạo mọi tổ hợp size × chất liệu cho một màu. Tổ hợp đã có sẽ được bỏ qua.</p>
  <div class="mt-4 grid gap-5 lg:grid-cols-2">
    <fieldset><legend class="text-sm font-medium">Size</legend>
      <div class="mt-2 flex flex-wrap gap-3">
        <?php foreach (Products::SIZES as $s): ?>
          <label class="flex items-center gap-1.5 text-sm"><input type="checkbox" name="sizes" value="<?= $s ?>" class="h-4 w-4 accent-crimson" <?= $s !== 'FREE' ? 'checked' : '' ?>> <?= $s === 'FREE' ? 'Free size' : $s ?></label>
        <?php endforeach; ?>
      </div>
      <span class="mt-1 block text-xs text-crimson" data-error-for="sizes"></span>
    </fieldset>
    <fieldset><legend class="text-sm font-medium">Chất liệu <span class="font-normal text-ink-soft">(bỏ trống với phụ kiện)</span></legend>
      <div class="mt-2 flex flex-wrap gap-3">
        <?php foreach ($fabrics as $f): ?>
          <label class="flex items-center gap-1.5 text-sm"><input type="checkbox" name="fabrics" value="<?= $f['id'] ?>" class="h-4 w-4 accent-crimson" checked> <?= e($f['name_vi']) ?></label>
        <?php endforeach; ?>
      </div>
    </fieldset>
    <div class="grid grid-cols-[auto_1fr_1fr] gap-3 lg:col-span-2 lg:grid-cols-[auto_1fr_1fr_120px_auto] lg:items-end">
      <label class="block"><span class="text-sm font-medium">Màu</span>
        <input type="color" name="color_hex" value="#9E2A2B" class="mt-1.5 block h-[42px] w-14 cursor-pointer rounded-sm border border-line bg-white p-1"></label>
      <label class="block"><span class="text-sm font-medium">Tên màu (VI)</span>
        <input name="color_vi" class="input mt-1.5" placeholder="Đỏ son"><span class="text-xs text-crimson" data-error-for="color_vi"></span></label>
      <label class="block"><span class="text-sm font-medium">Colour (EN)</span>
        <input name="color_en" class="input mt-1.5" placeholder="Vermilion"></label>
      <label class="block"><span class="text-sm font-medium">Tồn kho mỗi biến thể</span>
        <input type="number" name="stock" min="0" value="5" class="input mt-1.5"></label>
      <button class="btn-primary col-span-3 lg:col-span-1"><?= icon('plus', 'h-4 w-4') ?> Tạo</button>
    </div>
  </div>
  <span class="block text-xs text-crimson" data-error-for="color_hex"></span>
</form>
