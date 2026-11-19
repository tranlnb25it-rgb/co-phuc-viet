<?php
/**
 * One editable row (a form laid out as a grid). Used by every taxonomy tab, for existing rows and the "add" row.
 * @var string $type @var array|null $row null = new
 * @var list<array{name:string, label:string, type?:string, options?:array, span?:string, placeholder?:string}> $fields
 * @var string $grid  grid-cols-* classes @var string|null $usage text like "3 sản phẩm"
 */
$isNew = $row === null;
$url = url('/admin/api/taxonomy/' . $type . ($isNew ? '' : '/' . $row['id']));
$in = 'w-full rounded-sm border border-line bg-white px-2.5 py-1.5 text-sm focus:border-gold focus:outline-none focus:ring-2 focus:ring-gold/30 aria-[invalid=true]:border-crimson';
?>
<form class="grid items-start gap-2 px-4 py-3 <?= $grid ?> <?= $isNew ? 'bg-gold/10' : 'hover:bg-silk/40' ?>"
      data-tax-form="<?= e($url) ?>" data-method="<?= $isNew ? 'POST' : 'PUT' ?>">
  <?php foreach ($fields as $f): $name = $f['name']; $val = $row[$name] ?? ''; ?>
    <label class="block min-w-0 <?= $f['span'] ?? '' ?>">
      <?php if (!empty($f['show_label'])): ?>
        <span class="mb-0.5 block text-[11px] font-medium text-ink-soft"><?= e($f['label']) ?></span>
      <?php else: ?>
        <span class="sr-only"><?= e($f['label']) ?></span>
      <?php endif; ?>
      <?php if (($f['type'] ?? 'text') === 'select'): ?>
        <select name="<?= $name ?>" class="<?= $in ?>">
          <?php foreach ($f['options'] as $k => $opt): ?><option value="<?= e((string) $k) ?>" <?= (string) $val === (string) $k ? 'selected' : '' ?>><?= e($opt) ?></option><?php endforeach; ?>
        </select>
      <?php else: ?>
        <input name="<?= $name ?>" value="<?= e((string) $val) ?>" type="<?= $f['type'] ?? 'text' ?>" placeholder="<?= e($f['placeholder'] ?? $f['label']) ?>" class="<?= $in ?>"
               <?= ($f['type'] ?? '') === 'number' ? 'inputmode="numeric"' : '' ?>>
      <?php endif; ?>
      <span class="block text-[11px] text-crimson" data-error-for="<?= $name ?>"></span>
    </label>
  <?php endforeach; ?>
  <div class="flex items-center justify-end gap-1">
    <?php if (!$isNew && $usage !== null): ?><span class="mr-1 whitespace-nowrap text-xs text-ink-soft"><?= e($usage) ?></span><?php endif; ?>
    <button class="rounded-sm bg-crimson px-3 py-1.5 text-xs font-medium text-ivory hover:bg-crimson-dark"><?= $isNew ? 'Thêm' : 'Lưu' ?></button>
    <?php if (!$isNew): ?>
      <button type="button" class="rounded-sm p-1.5 text-ink-soft hover:bg-crimson/10 hover:text-crimson" aria-label="Xoá"
              data-tax-delete="<?= e($url) ?>" data-confirm="Xoá “<?= e($row['name_vi']) ?>”?"><?= icon('trash', 'h-4 w-4') ?></button>
    <?php endif; ?>
  </div>
</form>
