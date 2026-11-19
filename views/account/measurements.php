<?php
/** @var array $profiles */
use App\Core\{Lang, View};
use App\Support\Tailoring;

$labels = Lang::raw('pdp.custom.fields');
?>
<div class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h2 class="text-3xl"><?= e(t('account.measurements.title')) ?></h2>
    <p class="mt-1 max-w-xl text-sm text-ink-soft"><?= e(t('account.measurements.intro')) ?></p>
  </div>
  <button type="button" class="btn-primary" data-form-dialog="measure-dialog"
          data-url="<?= e(url('/api/account/measurements')) ?>" data-method="POST" data-heading="<?= e(t('account.measurements.add')) ?>">
    <?= icon('plus', 'h-4 w-4') ?> <?= e(t('account.measurements.add')) ?>
  </button>
</div>

<div class="mt-6" data-measure-list><?= View::partial('account/measurement-list', ['profiles' => $profiles]) ?></div>

<dialog id="measure-dialog" class="m-auto w-[min(600px,calc(100vw-2rem))] border border-line bg-ivory p-0 text-ink shadow-2xl backdrop:bg-ink/50" aria-labelledby="measure-dialog-title">
  <form data-ajax-form data-target="[data-measure-list]" novalidate>
    <div class="flex items-center justify-between border-b border-line px-6 py-4">
      <h3 id="measure-dialog-title" class="text-2xl" data-dialog-heading><?= e(t('account.measurements.add')) ?></h3>
      <button type="button" data-dialog-close class="rounded-full p-1.5 hover:bg-silk" aria-label="<?= e(t('nav.close')) ?>"><?= icon('x') ?></button>
    </div>
    <div class="px-6 pt-5">
      <label class="block text-sm"><span class="font-medium"><?= e(t('account.measurements.name')) ?></span>
        <input name="name" maxlength="60" required class="input mt-1 aria-[invalid=true]:border-crimson" placeholder="<?= e(t('account.measurements.namePlaceholder')) ?>">
        <span class="mt-1 block min-h-4 text-xs text-crimson" data-error-for="name"></span></label>
      <div class="grid grid-cols-2 gap-x-4 sm:grid-cols-3">
        <?php foreach (Tailoring::FIELDS as $key => [$min, $max, $unit]): ?>
          <label class="block text-sm">
            <span class="font-medium"><?= e($labels[$key]) ?></span>
            <span class="relative mt-1 block">
              <input type="number" name="<?= $key ?>" min="<?= $min ?>" max="<?= $max ?>" step="0.5" inputmode="decimal" placeholder="<?= $min ?>–<?= $max ?>"
                     class="input pr-10 aria-[invalid=true]:border-crimson aria-[invalid=true]:ring-2 aria-[invalid=true]:ring-crimson/20">
              <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-ink-soft"><?= e($unit) ?></span>
            </span>
            <span class="mt-1 block min-h-4 text-xs text-crimson" data-error-for="<?= $key ?>"></span>
          </label>
        <?php endforeach; ?>
      </div>
      <label class="block text-sm"><span class="font-medium"><?= e(t('pdp.custom.note')) ?></span>
        <textarea name="note" rows="2" maxlength="300" class="input mt-1 resize-y" placeholder="<?= e(t('pdp.custom.notePlaceholder')) ?>"></textarea></label>
      <p class="mt-3 flex gap-2 text-xs text-ink-soft"><?= icon('ruler', 'h-4 w-4 shrink-0 text-gold-dark') ?> <?= e(t('account.measurements.howTo')) ?></p>
    </div>
    <p class="mx-6 mt-3 hidden text-sm text-crimson" data-form-error role="alert"></p>
    <div class="mt-5 flex justify-end gap-2 border-t border-line px-6 py-4">
      <button type="button" data-dialog-close class="btn border border-line hover:border-gold"><?= e(t('account.measurements.cancel')) ?></button>
      <button class="btn-primary"><?= e(t('account.measurements.save')) ?></button>
    </div>
  </form>
</dialog>
