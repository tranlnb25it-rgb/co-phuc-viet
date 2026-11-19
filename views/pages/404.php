<?php use App\Core\View; ?>
<div class="container-page py-24 text-center">
  <p class="font-serif text-7xl text-gold">404</p>
  <h1 class="mt-2 text-3xl text-crimson"><?= e(t('errors.notFound')) ?></h1>
  <?= View::partial('partials/motif-divider', ['class' => 'my-6']) ?>
  <p class="text-ink-soft"><?= e(t('errors.notFoundText')) ?></p>
  <a href="<?= e(url('/')) ?>" class="btn-primary mt-8"><?= e(t('common.backHome')) ?></a>
</div>
