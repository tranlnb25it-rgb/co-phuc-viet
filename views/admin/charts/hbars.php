<?php
/**
 * Horizontal bar list (one series, one hue). Value printed at each bar's tip in text ink;
 * 4px rounded data-end, square at the left baseline; thin 12px bars.
 * @var list<array{label:string, value:int|float, display:string, sub?:string, href?:string}> $rows
 * @var string $empty
 */
$max = max(array_column($rows, 'value') ?: [0]);
?>
<?php if (!$rows || $max == 0): ?>
  <p class="py-8 text-center text-sm text-ink-soft"><?= e($empty) ?></p>
<?php else: ?>
  <ul class="space-y-3.5">
    <?php foreach ($rows as $r): $w = $max ? $r['value'] / $max * 100 : 0; ?>
      <li class="grid grid-cols-[minmax(0,9rem)_1fr] items-center gap-3 text-sm sm:grid-cols-[minmax(0,11rem)_1fr]">
        <span class="min-w-0">
          <?php if (!empty($r['href'])): ?>
            <a href="<?= e($r['href']) ?>" class="block truncate hover:text-crimson" title="<?= e($r['label']) ?>"><?= e($r['label']) ?></a>
          <?php else: ?>
            <span class="block truncate" title="<?= e($r['label']) ?>"><?= e($r['label']) ?></span>
          <?php endif; ?>
          <?php if (!empty($r['sub'])): ?><span class="block truncate text-[11px] text-ink-soft"><?= e($r['sub']) ?></span><?php endif; ?>
        </span>
        <span class="flex min-w-0 items-center gap-2">
          <?php if ($r['value'] > 0): ?>
            <span class="h-3 shrink-0 rounded-r-[4px] bg-crimson" style="width: max(4px, calc(<?= round($w, 3) ?>% - 4.5rem))"></span>
          <?php endif; ?>
          <span class="shrink-0 text-xs font-medium tabular-nums text-ink"><?= e($r['display']) ?></span>
        </span>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
