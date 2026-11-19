<?php /** @var array $list AccountService::orders() */ ?>
<h2 class="text-3xl"><?= e(t('account.orders.title')) ?></h2>
<div class="mt-5" data-order-list="<?= e(url('/api/account/orders')) ?>">
  <?= App\Core\View::partial('account/order-list', ['list' => $list]) ?>
</div>
