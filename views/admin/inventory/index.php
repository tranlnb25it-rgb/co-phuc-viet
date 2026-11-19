<?php
/** @var string $tab stock|history @var array $filters @var array $result */
use App\Core\View;
use App\Services\Inventory;

$tabCls = fn(bool $on) => 'border-b-2 px-1 pb-3 text-sm font-medium transition ' . ($on ? 'border-crimson text-crimson' : 'border-transparent text-ink-soft hover:text-ink');
?>
<header>
  <p class="eyebrow">Hàng hoá</p>
  <h1 class="mt-1 text-4xl">Kho hàng</h1>
</header>

<?php if ($tab === 'stock'): $s = $result['summary']; ?>
  <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <?php foreach ([['Tổng tồn kho', number_format((int) $s['units'], 0, ',', '.') . ' sản phẩm', ''],
                    ['Số biến thể', (string) (int) $s['variants'], ''],
                    ['Sắp hết (≤ ' . low_stock() . ')', (string) (int) $s['low_count'], 'low'],
                    ['Hết hàng', (string) (int) $s['out_count'], 'out']] as [$label, $value, $level]): ?>
      <a href="<?= e(url('/admin/kho', array_filter(['level' => $level]))) ?>" class="block border border-line bg-white/70 p-5 transition hover:border-gold">
        <p class="text-sm text-ink-soft"><?= e($label) ?></p>
        <p class="mt-2 text-3xl font-semibold tracking-tight <?= $level && $value !== '0' ? 'text-crimson' : '' ?>"><?= e($value) ?></p>
      </a>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<nav class="mt-8 flex gap-6 border-b border-line" aria-label="Kho">
  <a href="<?= e(url('/admin/kho')) ?>" class="<?= $tabCls($tab === 'stock') ?>" <?= $tab === 'stock' ? 'aria-current="page"' : '' ?>>Tồn kho</a>
  <a href="<?= e(url('/admin/kho', ['tab' => 'history'])) ?>" class="<?= $tabCls($tab === 'history') ?>" <?= $tab === 'history' ? 'aria-current="page"' : '' ?>>Lịch sử nhập xuất</a>
</nav>

<form class="mt-5 flex flex-wrap items-end gap-3" method="get" action="<?= e(url('/admin/kho')) ?>" data-ajax-filter="#inventory-list">
  <?php if ($tab === 'history'): ?><input type="hidden" name="tab" value="history"><?php endif; ?>
  <label class="relative block min-w-64 flex-1 sm:max-w-md">
    <span class="sr-only">Tìm kiếm</span>
    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-ink-soft"><?= icon('search', 'h-4 w-4') ?></span>
    <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Tên sản phẩm, SKU<?= $tab === 'history' ? ', ghi chú' : ', màu' ?>…" class="input pl-9" autocomplete="off">
  </label>
  <?php if ($tab === 'stock'): ?>
    <select name="level" class="input w-auto!" aria-label="Mức tồn">
      <option value="">Mọi mức tồn</option>
      <option value="low" <?= $filters['level'] === 'low' ? 'selected' : '' ?>>Sắp hết</option>
      <option value="out" <?= $filters['level'] === 'out' ? 'selected' : '' ?>>Hết hàng</option>
    </select>
  <?php else: ?>
    <select name="reason" class="input w-auto!" aria-label="Lý do">
      <option value="">Mọi lý do</option>
      <?php foreach (Inventory::REASONS as $k => [$label]): ?><option value="<?= $k ?>" <?= $filters['reason'] === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
    </select>
    <?php if ($filters['variant']): ?><input type="hidden" name="variant" value="<?= (int) $filters['variant'] ?>"><?php endif; ?>
    <label class="text-xs text-ink-soft">Từ ngày<input type="date" name="from" value="<?= e($filters['from']) ?>" class="input mt-1 w-auto! py-2!"></label>
    <label class="text-xs text-ink-soft">Đến ngày<input type="date" name="to" value="<?= e($filters['to']) ?>" class="input mt-1 w-auto! py-2!"></label>
  <?php endif; ?>
  <noscript><button class="btn-primary">Lọc</button></noscript>
</form>

<div id="inventory-list" class="mt-5 transition-opacity" data-endpoint="<?= e(url('/admin/api/inventory')) ?>">
  <?= View::partial("admin/inventory/$tab", ['result' => $result, 'filters' => $filters]) ?>
</div>
