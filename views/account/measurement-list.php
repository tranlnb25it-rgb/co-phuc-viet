<?php
/** @var array $profiles customer_measurements rows */
use App\Core\Lang;
use App\Services\AccountService;
use App\Support\Tailoring;

$labels = Lang::raw('pdp.custom.fields');
$num = fn($v) => rtrim(rtrim(number_format((float) $v, 1, ',', ''), '0'), ',');
?>
<?php if (!$profiles): ?>
  <div class="flex flex-col items-center border border-dashed border-line py-14 text-center">
    <span class="text-gold/70"><?= icon('ruler', 'h-10 w-10') ?></span>
    <p class="mt-3 text-ink-soft"><?= e(t('account.measurements.empty')) ?></p>
  </div>
<?php else: ?>
  <div class="grid gap-4 md:grid-cols-2">
    <?php foreach ($profiles as $p):
      $vals = AccountService::measurementValues($p);
      $size = Tailoring::suggestSize($vals);
      $form = ['name' => $p['name'], 'note' => $p['note'] ?? ''];
      foreach (array_keys(Tailoring::FIELDS) as $k) $form[$k] = $num($p[$k]) === '' ? '' : str_replace(',', '.', $num($p[$k])); ?>
      <article class="flex flex-col border border-line bg-white/60 p-5 text-sm">
        <div class="flex items-start justify-between gap-2">
          <p class="font-serif text-xl font-semibold"><?= e($p['name']) ?></p>
          <?php if ($size): ?><span class="rounded-full border border-gold/60 bg-gold/10 px-2 py-0.5 text-[11px] font-semibold text-gold-dark"><?= e(t('account.measurements.suggest', ['size' => $size])) ?></span><?php endif; ?>
        </div>
        <dl class="mt-3 grid grid-cols-5 gap-1 text-center">
          <?php foreach (Tailoring::FIELDS as $k => [, , $unit]): ?>
            <div class="bg-silk/70 px-1 py-2">
              <dt class="text-[10px] uppercase tracking-wide text-ink-soft"><?= e($labels[$k]) ?></dt>
              <dd class="font-semibold"><?= e($num($p[$k])) ?><span class="text-[10px] font-normal text-ink-soft"><?= e($unit) ?></span></dd>
            </div>
          <?php endforeach; ?>
        </dl>
        <?php if ($p['note']): ?><p class="mt-2 text-xs italic text-ink-soft">“<?= e($p['note']) ?>”</p><?php endif; ?>
        <div class="mt-auto flex items-center gap-4 pt-4 text-xs font-medium">
          <span class="text-ink-soft"><?= e(t('account.measurements.updatedAt', ['date' => date('d/m/Y', strtotime($p['updated_at']))])) ?></span>
          <button type="button" class="ml-auto inline-flex items-center gap-1 text-crimson hover:underline" data-form-dialog="measure-dialog"
                  data-url="<?= e(url('/api/account/measurements/' . $p['id'])) ?>" data-method="PUT"
                  data-heading="<?= e(t('account.measurements.edit')) ?>" data-values="<?= e(json_encode($form, JSON_UNESCAPED_UNICODE)) ?>">
            <?= icon('pencil', 'h-3.5 w-3.5') ?> <?= e(t('account.measurements.edit')) ?></button>
          <button type="button" class="inline-flex items-center gap-1 text-ink-soft hover:text-crimson"
                  data-ajax-action="DELETE <?= e(url('/api/account/measurements/' . $p['id'])) ?>" data-target="[data-measure-list]"
                  data-confirm="<?= e(t('account.measurements.deleteConfirm')) ?>"><?= icon('trash', 'h-3.5 w-3.5') ?> <?= e(t('account.measurements.delete')) ?></button>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
