<?php
/** Stock table with per-row adjustment (page render + Ajax). @var array $result @var array $filters */
use App\Core\View;
use App\Services\Inventory;

$low = low_stock();
?>
<p class="mb-3 text-sm text-ink-soft"><?= (int) $result['total'] ?> biến thể</p>
<?php if (!$result['items']): ?>
  <div class="border border-dashed border-line px-6 py-16 text-center text-ink-soft">Không có biến thể phù hợp.</div>
<?php else: ?>
<div class="overflow-x-auto border border-line bg-white/70">
  <table class="w-full min-w-[820px] text-sm">
    <thead class="border-b border-line bg-silk/60 text-left text-xs uppercase tracking-wider text-ink-soft">
      <tr>
        <th class="px-4 py-3 font-semibold">Sản phẩm / biến thể</th>
        <th class="px-3 py-3 font-semibold">SKU</th>
        <th class="px-3 py-3 text-right font-semibold">Bán 30 ngày</th>
        <th class="px-3 py-3 text-right font-semibold">Tồn kho</th>
        <th class="px-4 py-3"><span class="sr-only">Điều chỉnh</span></th>
      </tr>
    </thead>
    <tbody class="divide-y divide-line">
      <?php foreach ($result['items'] as $v): $stock = (int) $v['stock']; ?>
        <tr class="align-top hover:bg-silk/40">
          <td class="px-4 py-3">
            <a href="<?= e(url('/admin/san-pham/' . $v['product_id'])) ?>#ton-kho" class="font-medium hover:text-crimson"><?= e($v['name_vi']) ?></a>
            <?php if (!$v['is_active']): ?><span class="ml-1 text-xs text-ink-soft">(đang ẩn)</span><?php endif; ?>
            <p class="flex items-center gap-1.5 text-xs text-ink-soft"><span class="h-3 w-3 rounded-full border border-ink/15" style="background:<?= e($v['color_hex']) ?>"></span><?= e(Inventory::label(['fabric' => $v['fabric'], 'color_vi' => $v['color_vi'], 'size' => $v['size']])) ?></p>
          </td>
          <td class="px-3 py-3 font-mono text-xs text-ink-soft">
            <a href="<?= e(url('/admin/kho', ['tab' => 'history', 'variant' => $v['id']])) ?>" class="hover:text-crimson" title="Xem lịch sử"><?= e($v['sku']) ?></a>
          </td>
          <td class="px-3 py-3 text-right tabular-nums"><?= (int) $v['sold_30d'] ?></td>
          <td class="px-3 py-3 text-right">
            <span class="inline-flex min-w-10 justify-center rounded-full px-2 py-0.5 text-sm font-semibold tabular-nums <?= $stock === 0 ? 'bg-crimson text-ivory' : ($stock <= $low ? 'bg-gold/20 text-gold-dark' : '') ?>" data-stock-badge="<?= (int) $v['id'] ?>"><?= $stock ?></span>
          </td>
          <td class="px-4 py-3 text-right">
            <details class="group relative inline-block text-left" data-adjust>
              <summary class="cursor-pointer list-none rounded-sm border border-line px-3 py-1 text-xs font-medium hover:border-gold [&::-webkit-details-marker]:hidden">Điều chỉnh</summary>
              <form class="absolute right-0 z-20 mt-2 w-72 space-y-2 border border-line bg-white p-4 text-sm shadow-xl" data-adjust-form="<?= e(url('/admin/api/inventory/' . $v['id'])) ?>">
                <p class="font-medium"><?= e($v['sku']) ?> · đang có <?= $stock ?></p>
                <div class="grid grid-cols-2 gap-2">
                  <label class="flex items-center gap-1.5 text-xs"><input type="radio" name="mode" value="add" checked class="accent-crimson"> Cộng / trừ</label>
                  <label class="flex items-center gap-1.5 text-xs"><input type="radio" name="mode" value="set" class="accent-crimson"> Đặt bằng</label>
                </div>
                <input type="number" name="qty" class="input py-2!" placeholder="VD: 10 hoặc -2" required>
                <span class="block text-xs text-crimson" data-error-for="qty"></span>
                <select name="reason" class="input py-2!">
                  <?php foreach (Inventory::MANUAL as $r): ?><option value="<?= $r ?>"><?= e(Inventory::REASONS[$r][0]) ?></option><?php endforeach; ?>
                </select>
                <input name="note" maxlength="255" class="input py-2!" placeholder="Ghi chú (VD: nhập từ xưởng Huế)">
                <button class="btn-primary w-full py-2!">Lưu</button>
              </form>
            </details>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= View::partial('admin/partials/pagination', ['result' => $result, 'filters' => array_diff_key($filters, ['tab' => 1]), 'path' => '/admin/kho']) ?>
<?php endif; ?>
