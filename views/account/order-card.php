<?php
/** One order in "My orders" / overview. @var array $o row from AccountService::orders() */
use App\Services\AdminOrderService;

$href = url('/tai-khoan/don-hang/' . $o['order_code']);
$lines = $o['lines'] ?? [];
$shown = array_slice($lines, 0, 3);
?>
<article class="border border-line bg-white/60 transition hover:border-gold">
  <header class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3 text-sm">
    <span>
      <a href="<?= e($href) ?>" data-account-link class="font-mono font-semibold text-crimson hover:underline"><?= e($o['order_code']) ?></a>
      <span class="text-ink-soft">· <?= e(date('d/m/Y', strtotime($o['created_at']))) ?></span>
    </span>
    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold <?= AdminOrderService::STATUSES[$o['status']][1] ?>"><?= e(t('account.status.' . $o['status'])) ?></span>
  </header>
  <div class="flex flex-wrap items-center gap-4 px-4 py-3">
    <div class="flex -space-x-3">
      <?php foreach ($shown as $l): ?>
        <span class="h-14 w-11 overflow-hidden border-2 border-ivory bg-silk shadow-sm [&_span]:hidden [&_svg]:h-5 [&_svg]:w-5"><?= product_image($l['image'], $l['product_name'], $l['tint']) ?></span>
      <?php endforeach; ?>
    </div>
    <div class="min-w-0 flex-1 text-sm">
      <p class="truncate"><?= e(implode(', ', array_map(fn($l) => $l['product_name'] . ' ×' . $l['quantity'], $shown))) ?></p>
      <p class="text-xs text-ink-soft"><?= e(t('account.orders.itemCount', ['count' => (int) $o['item_count']])) ?>
        <?= count($lines) > 3 ? ' · ' . e(t('account.orders.more', ['count' => count($lines) - 3])) : '' ?></p>
    </div>
    <div class="text-right">
      <p class="font-semibold text-crimson"><?= money($o['total']) ?></p>
      <a href="<?= e($href) ?>" data-account-link class="text-xs text-ink-soft hover:text-crimson"><?= e(t('account.orders.view')) ?> →</a>
    </div>
  </div>
</article>
