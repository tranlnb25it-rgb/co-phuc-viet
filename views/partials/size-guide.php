<?php
/** Size chart dialog. @var array $sizeChart @var array $fields */
$labels = App\Core\Lang::raw('pdp.custom.fields');
?>
<dialog id="size-guide" class="m-auto w-[min(640px,calc(100vw-2rem))] border border-line bg-ivory p-0 text-ink shadow-2xl backdrop:bg-ink/50" aria-labelledby="size-guide-title">
  <div class="flex items-center justify-between border-b border-line px-6 py-4">
    <h2 id="size-guide-title" class="text-2xl"><?= e(t('pdp.sizeChart.title')) ?></h2>
    <button type="button" data-dialog-close class="rounded-full p-1.5 transition hover:bg-silk" aria-label="<?= e(t('nav.close')) ?>"><?= icon('x') ?></button>
  </div>
  <div class="px-6 py-5">
    <p class="text-sm text-ink-soft"><?= e(t('pdp.sizeChart.intro')) ?></p>
    <div class="mt-4 overflow-x-auto">
      <table class="w-full min-w-[480px] text-sm">
        <thead>
          <tr class="border-b-2 border-gold/50 text-left">
            <th class="py-2 pr-3 font-semibold"><?= e(t('pdp.sizeChart.size')) ?></th>
            <?php foreach ($fields as $key => [, , $unit]): ?>
              <th class="px-2 py-2 font-semibold"><?= e($labels[$key]) ?> <span class="font-normal text-ink-soft">(<?= e($unit) ?>)</span></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sizeChart as $size => $ranges): ?>
            <tr class="border-b border-line odd:bg-silk/50">
              <th class="py-2.5 pr-3 font-serif text-lg text-crimson"><?= e($size) ?></th>
              <?php foreach ($fields as $key => $_): ?>
                <td class="px-2 py-2.5"><?= (int) $ranges[$key][0] ?>–<?= (int) $ranges[$key][1] ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</dialog>
