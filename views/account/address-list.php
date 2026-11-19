<?php /** @var array $addresses */ ?>
<?php if (!$addresses): ?>
  <div class="flex flex-col items-center border border-dashed border-line py-14 text-center">
    <span class="text-gold/70"><?= icon('map-pin', 'h-10 w-10') ?></span>
    <p class="mt-3 text-ink-soft"><?= e(t('account.addresses.empty')) ?></p>
  </div>
<?php else: ?>
  <div class="grid gap-4 md:grid-cols-2">
    <?php foreach ($addresses as $a):
      $values = ['recipient_name' => $a['recipient_name'], 'phone' => $a['phone'], 'address' => $a['address'],
                 'city' => $a['city'], 'label' => $a['label'] ?? '', 'is_default' => (bool) $a['is_default']]; ?>
      <article class="relative flex flex-col border bg-white/60 p-5 text-sm <?= $a['is_default'] ? 'border-crimson ring-1 ring-crimson/30' : 'border-line' ?>">
        <div class="flex items-start justify-between gap-2">
          <p class="font-semibold"><?= e($a['recipient_name']) ?>
            <?php if ($a['label']): ?><span class="ml-1 rounded-sm bg-silk px-1.5 py-0.5 text-[11px] font-normal text-ink-soft"><?= e($a['label']) ?></span><?php endif; ?></p>
          <?php if ($a['is_default']): ?>
            <span class="inline-flex items-center gap-1 rounded-full bg-crimson px-2 py-0.5 text-[11px] font-semibold text-ivory"><?= icon('check', 'h-3 w-3') ?> <?= e(t('account.addresses.default')) ?></span>
          <?php endif; ?>
        </div>
        <p class="mt-1 text-ink-soft"><?= e($a['phone']) ?></p>
        <p class="text-ink-soft"><?= e($a['address']) ?>, <?= e($a['city']) ?></p>
        <div class="mt-auto flex flex-wrap gap-x-4 gap-y-1 pt-4 text-xs font-medium">
          <button type="button" class="inline-flex items-center gap-1 text-crimson hover:underline" data-form-dialog="address-dialog"
                  data-url="<?= e(url('/api/account/addresses/' . $a['id'])) ?>" data-method="PUT"
                  data-heading="<?= e(t('account.addresses.edit')) ?>" data-values="<?= e(json_encode($values, JSON_UNESCAPED_UNICODE)) ?>">
            <?= icon('pencil', 'h-3.5 w-3.5') ?> <?= e(t('account.addresses.edit')) ?></button>
          <?php if (!$a['is_default']): ?>
            <button type="button" class="text-ink-soft hover:text-crimson" data-ajax-action="PATCH <?= e(url('/api/account/addresses/' . $a['id'] . '/default')) ?>"
                    data-target="[data-address-list]"><?= e(t('account.addresses.makeDefault')) ?></button>
          <?php endif; ?>
          <button type="button" class="ml-auto inline-flex items-center gap-1 text-ink-soft hover:text-crimson"
                  data-ajax-action="DELETE <?= e(url('/api/account/addresses/' . $a['id'])) ?>" data-target="[data-address-list]"
                  data-confirm="<?= e(t('account.addresses.deleteConfirm')) ?>"><?= icon('trash', 'h-3.5 w-3.5') ?> <?= e(t('account.addresses.delete')) ?></button>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
