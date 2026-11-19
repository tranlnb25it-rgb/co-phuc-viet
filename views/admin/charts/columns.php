<?php
/**
 * Single-series column chart, rendered as HTML so text stays crisp at any width.
 * Marks: ≤24px columns, 4px rounded data-end, square at the baseline, 2px gap between columns,
 * 1px solid recessive gridlines, value printed only on the peak; hover/focus tooltip on each column
 * (handled by admin.js via data-tip-*); a table view below for the full data.
 *
 * @var list<array{label:string,title:string,value:int,orders:int}> $points
 * @var string $caption  accessible description
 */
$max = max(array_column($points, 'value') ?: [0]);
$ticks = nice_ticks((float) $max, 4);
$top = (float) end($ticks) ?: 1;
$n = count($points);
$every = max(1, (int) ceil($n / 8));          // x-labels: at most ~8, so they never collide
$peak = $max > 0 ? array_search($max, array_column($points, 'value'), true) : -1;
?>
<figure class="relative" data-chart>
  <figcaption class="sr-only"><?= e($caption) ?></figcaption>
  <div class="relative h-60">
    <!-- gridlines + y ticks -->
    <?php foreach ($ticks as $t): ?>
      <div class="absolute left-14 right-0 border-t border-line" style="bottom: <?= round($t / $top * 100, 3) ?>%">
        <span class="absolute -left-14 w-12 -translate-y-1/2 text-right text-[11px] tabular-nums text-ink-soft"><?= e(money_compact($t)) ?></span>
      </div>
    <?php endforeach; ?>

    <!-- columns: the full-height button is the hit target -->
    <div class="absolute inset-y-0 left-14 right-0 flex items-end gap-0.5" role="list">
      <?php foreach ($points as $i => $p): $h = $top ? $p['value'] / $top * 100 : 0; ?>
        <button type="button" role="listitem"
                class="group relative flex h-full min-w-0 flex-1 items-end justify-center outline-none"
                data-tip-title="<?= e($p['title']) ?>" data-tip-value="<?= e(money($p['value'])) ?>"
                data-tip-sub="<?= (int) $p['orders'] ?> đơn"
                aria-label="<?= e($p['title'] . ': ' . money($p['value']) . ', ' . $p['orders'] . ' đơn') ?>">
          <span class="absolute inset-x-0 inset-y-0 rounded-sm transition group-hover:bg-ink/[0.04] group-focus-visible:bg-ink/[0.06]"></span>
          <?php if ($i === $peak): ?>
            <span class="pointer-events-none absolute z-10 -translate-y-full whitespace-nowrap pb-1 text-[11px] font-semibold text-ink"
                  style="bottom: <?= round($h, 3) ?>%"><?= e(money_compact($p['value'])) ?></span>
          <?php endif; ?>
          <span class="relative w-full max-w-6 rounded-t-[4px] bg-crimson transition group-hover:bg-crimson-light"
                style="height: <?= round($h, 3) ?>%"></span>
        </button>
      <?php endforeach; ?>
    </div>

    <?php if ($max == 0): ?>
      <p class="absolute inset-0 left-14 flex items-center justify-center text-sm text-ink-soft">Chưa có doanh thu trong khoảng thời gian này</p>
    <?php endif; ?>
  </div>

  <!-- x labels -->
  <div class="ml-14 mt-2 flex gap-0.5 text-[11px] tabular-nums text-ink-soft" aria-hidden="true">
    <?php foreach ($points as $i => $p): ?>
      <span class="min-w-0 flex-1 text-center"><?= ($i % $every === 0 || $i === $n - 1) ? e($p['label']) : '' ?></span>
    <?php endforeach; ?>
  </div>

  <!-- shared tooltip (positioned by admin.js) -->
  <div class="pointer-events-none absolute z-20 hidden min-w-36 rounded-sm border border-line bg-white px-3 py-2 text-xs shadow-lg" data-chart-tip>
    <p class="text-sm font-semibold text-ink" data-tip="value"></p>
    <p class="flex items-center gap-1.5 text-ink-soft"><span class="h-0.5 w-3 bg-crimson"></span><span data-tip="title"></span></p>
    <p class="text-ink-soft" data-tip="sub"></p>
  </div>

  <details class="mt-4 text-sm">
    <summary class="cursor-pointer text-xs text-ink-soft hover:text-crimson">Xem dạng bảng</summary>
    <div class="mt-2 max-h-64 overflow-y-auto">
      <table class="w-full text-xs">
        <thead class="sticky top-0 bg-white text-left text-ink-soft"><tr><th class="py-1.5 font-medium">Thời gian</th><th class="py-1.5 text-right font-medium">Đơn</th><th class="py-1.5 text-right font-medium">Doanh thu</th></tr></thead>
        <tbody class="divide-y divide-line tabular-nums">
          <?php foreach ($points as $p): ?>
            <tr><td class="py-1.5"><?= e($p['title']) ?></td><td class="py-1.5 text-right"><?= (int) $p['orders'] ?></td><td class="py-1.5 text-right"><?= money($p['value']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </details>
</figure>
