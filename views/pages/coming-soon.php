<?php use App\Core\View; ?>
<div class="container-page py-24 text-center">
  <h1 class="text-4xl text-crimson"><?= e(t('common.comingSoon')) ?></h1>
  <?= View::partial('partials/motif-divider', ['class' => 'my-6']) ?>
  <p class="text-ink-soft"><?= e(t('common.comingSoonText')) ?></p>
  <a href="<?= e(url('/')) ?>" class="btn-outline mt-8"><?= e(t('common.backHome')) ?></a>
</div>
