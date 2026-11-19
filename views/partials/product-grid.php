<?php
/** @var array $products @var string|null $empty */
use App\Core\View;
?>
<?php if (!$products): ?>
  <p class="col-span-full py-16 text-center text-ink-soft"><?= e($empty ?? t('home.featured.empty')) ?></p>
<?php else: foreach ($products as $p): ?>
  <?= View::partial('partials/product-card', ['p' => $p]) ?>
<?php endforeach; endif; ?>
