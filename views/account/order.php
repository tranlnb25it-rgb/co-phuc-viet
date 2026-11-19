<?php /** @var array $order AccountService::order() */ ?>
<div data-order-detail>
  <a href="<?= e(url('/tai-khoan/don-hang')) ?>" data-account-link class="inline-flex items-center gap-1 text-sm text-ink-soft hover:text-crimson">
    <?= icon('chevron-right', 'h-4 w-4 rotate-180') ?> <?= e(t('account.orders.back')) ?>
  </a>
  <div class="mt-4"><?= App\Core\View::partial('partials/order-tracking', ['order' => $order]) ?></div>

  <?php if ($order['status'] === 'pending'): ?>
    <div class="mt-8 flex flex-wrap items-center gap-4 border-t border-line pt-6">
      <button type="button" class="btn border border-crimson/40 text-crimson hover:bg-crimson hover:text-ivory"
              data-ajax-action="POST <?= e(url('/api/account/orders/' . $order['order_code'] . '/cancel')) ?>"
              data-confirm="<?= e(t('account.orders.cancelConfirm', ['code' => $order['order_code']])) ?>"
              data-target="[data-order-detail]" data-swap="outer" data-busy-label="<?= e(t('account.orders.cancelling')) ?>">
        <?= icon('x', 'h-4 w-4') ?> <span><?= e(t('account.orders.cancel')) ?></span>
      </button>
      <p class="max-w-md text-xs text-ink-soft"><?= e(t('account.orders.cancelNote')) ?></p>
    </div>
  <?php endif; ?>
</div>
