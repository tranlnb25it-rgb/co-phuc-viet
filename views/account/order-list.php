<?php
/** Tabs + order cards + pagination. Swapped over Ajax. @var array $list */
use App\Core\View;
use App\Services\AccountService;
?>
<div class="flex gap-1 overflow-x-auto border-b border-line" role="tablist">
  <?php foreach (array_keys(AccountService::ORDER_TABS) as $tab): $on = $tab === $list['tab']; $n = $list['counts'][$tab] ?? 0; ?>
    <a href="<?= e(url('/tai-khoan/don-hang', $tab === 'all' ? [] : ['tab' => $tab])) ?>" role="tab" aria-selected="<?= $on ? 'true' : 'false' ?>"
       data-order-tab="<?= e($tab) ?>"
       class="-mb-px flex shrink-0 items-center gap-1.5 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm transition <?= $on ? 'border-crimson font-semibold text-crimson' : 'border-transparent text-ink-soft hover:text-ink' ?>">
      <?= e(t('account.orders.tabs.' . $tab)) ?>
      <?php if ($n): ?><span class="rounded-full bg-silk px-1.5 text-[11px] text-ink"><?= $n ?></span><?php endif; ?>
    </a>
  <?php endforeach; ?>
</div>

<?php if ($list['items']): ?>
  <div class="mt-5 space-y-3">
    <?php foreach ($list['items'] as $o) echo View::partial('account/order-card', ['o' => $o]); ?>
  </div>
  <?php if ($list['pages'] > 1): ?>
    <nav class="mt-6 flex items-center justify-center gap-1" aria-label="<?= e(t('account.orders.title')) ?>">
      <?php for ($p = 1; $p <= $list['pages']; $p++): ?>
        <a href="<?= e(url('/tai-khoan/don-hang', array_filter(['tab' => $list['tab'] === 'all' ? null : $list['tab'], 'page' => $p > 1 ? $p : null]))) ?>"
           data-order-page="<?= $p ?>"
           class="flex h-9 min-w-9 items-center justify-center border px-2 text-sm <?= $p === $list['page'] ? 'border-crimson bg-crimson text-ivory' : 'border-line hover:border-gold' ?>"
           <?= $p === $list['page'] ? 'aria-current="page"' : '' ?>><?= $p ?></a>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
<?php else: ?>
  <div class="mt-5 flex flex-col items-center border border-dashed border-line py-14 text-center">
    <span class="text-gold/70"><?= icon('package', 'h-10 w-10') ?></span>
    <p class="mt-3 text-ink-soft"><?= e(t('account.orders.empty')) ?></p>
    <a href="<?= e(url('/bo-suu-tap')) ?>" class="btn-outline mt-5"><?= e(t('account.overview.startShopping')) ?></a>
  </div>
<?php endif; ?>
