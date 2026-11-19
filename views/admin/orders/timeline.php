<?php
/** Order timeline (newest first) + internal note form. Re-rendered after every change. @var array $order */
use App\Services\OrderEvents;
?>
<?php if (App\Support\Auth::can('orders.manage')): ?>
<form class="flex gap-2" data-note-form="<?= e(url('/admin/api/orders/' . $order['order_code'] . '/notes')) ?>">
  <label class="sr-only" for="note-input">Ghi chú nội bộ</label>
  <textarea id="note-input" name="note" rows="2" maxlength="1000" required
            class="input resize-y aria-[invalid=true]:border-crimson" placeholder="Ghi chú nội bộ (khách không thấy)… VD: đã gọi xác nhận số đo"></textarea>
  <button class="btn-primary self-start px-4! py-2.5!">Thêm</button>
</form>
<span class="mt-1 block text-xs text-crimson" data-error-for="note"></span>
<?php endif; ?>

<ol class="relative mt-4 space-y-5 border-l border-line pl-6">
  <?php foreach ($order['events'] as $ev): [$label, $ic, $dot] = OrderEvents::TYPES[$ev['type']] ?? ['', 'alert', 'bg-line']; ?>
    <li class="relative">
      <span class="absolute -left-[37px] top-0 flex h-6 w-6 items-center justify-center rounded-full ring-4 ring-white <?= $dot ?>"><?= icon($ic, 'h-3 w-3') ?></span>
      <p class="text-sm <?= $ev['type'] === 'note' ? 'whitespace-pre-line rounded-sm bg-gold/10 px-3 py-2' : '' ?>"><?= e($ev['message']) ?></p>
      <p class="mt-0.5 text-xs text-ink-soft">
        <?= e($label) ?> · <?= e($ev['user_name'] ?? ($ev['type'] === 'created' || str_starts_with($ev['message'], 'Khách') ? 'Khách hàng' : 'Hệ thống')) ?>
        · <time datetime="<?= e(date('c', strtotime($ev['created_at']))) ?>"><?= e(date('H:i d/m/Y', strtotime($ev['created_at']))) ?></time>
      </p>
    </li>
  <?php endforeach; ?>
  <?php if (!$order['events']): ?><li class="text-sm text-ink-soft">Chưa có hoạt động.</li><?php endif; ?>
</ol>
