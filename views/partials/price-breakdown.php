<?php
/** Price breakdown under the main price. Also returned by the Ajax quote endpoint. @var array $quote */
$b = $quote['breakdown'];
$rows = array_filter([
    'base'      => $b['base'],
    'fabric'    => $b['fabric'],
    'tailoring' => $b['tailoring'],
]);
if (count($rows) < 2) return;   // nothing to explain when it's just the base price
?>
<dl class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-ink-soft">
  <?php foreach ($rows as $key => $value): ?>
    <div class="flex gap-1">
      <dt><?= e(t('pdp.breakdown.' . $key)) ?><?= $key === 'base' && !empty($quote['on_sale']) ? ' (' . e(t('product.sale')) . ')' : '' ?>:</dt>
      <dd class="font-medium text-ink"><?= $key === 'base' ? '' : '+' ?><?= money($value) ?></dd>
    </div>
  <?php endforeach; ?>
</dl>
