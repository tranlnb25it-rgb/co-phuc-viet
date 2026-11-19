<?php /** @var string $message @var string|null $debug */ use App\Core\View; ?>
<div class="container-page py-24 text-center">
  <h1 class="text-3xl text-crimson"><?= e($message) ?></h1>
  <?= View::partial('partials/motif-divider', ['class' => 'my-6']) ?>
  <?php if (!empty($debug)): ?>
    <pre class="mx-auto max-w-3xl overflow-x-auto whitespace-pre-wrap border border-line bg-silk p-4 text-left text-xs text-ink-soft"><?= e($debug) ?></pre>
  <?php endif; ?>
  <a href="<?= e(url('/')) ?>" class="btn-primary mt-8"><?= e(t('common.backHome')) ?></a>
</div>
