<?php /** Bell dropdown content. @var array $summary AdminOrderService::pendingSummary() */ ?>
<div class="flex items-center justify-between border-b border-line px-4 py-3">
  <p class="font-semibold">Đơn chờ xác nhận</p>
  <span class="rounded-full bg-crimson px-2 py-0.5 text-xs font-semibold text-ivory"><?= (int) $summary['count'] ?></span>
</div>
<?php if (!$summary['items']): ?>
  <p class="px-4 py-8 text-center text-sm text-ink-soft">Không có đơn nào đang chờ. 🎉</p>
<?php else: ?>
  <ul class="max-h-80 divide-y divide-line overflow-y-auto">
    <?php foreach ($summary['items'] as $o): ?>
      <li>
        <a href="<?= e(url('/admin/don-hang/' . $o['order_code'])) ?>" class="flex items-center justify-between gap-3 px-4 py-3 text-sm hover:bg-silk">
          <span class="min-w-0">
            <span class="block truncate font-medium"><?= e($o['customer_name']) ?></span>
            <span class="block font-mono text-xs text-ink-soft"><?= e($o['order_code']) ?> · <?= e(date('H:i d/m', strtotime($o['created_at']))) ?></span>
          </span>
          <span class="shrink-0 text-sm font-semibold tabular-nums"><?= money($o['total']) ?></span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
<a href="<?= e(url('/admin/don-hang', ['status' => 'pending'])) ?>" class="block border-t border-line px-4 py-2.5 text-center text-sm text-crimson hover:bg-silk">Xem tất cả đơn chờ →</a>
