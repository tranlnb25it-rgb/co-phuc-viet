<?php
/** @var string $tab @var array $lists TaxonomyService::all() */
use App\Core\View;
use App\Services\TaxonomyService as Taxonomy;

$tabCls = fn(bool $on) => 'border-b-2 px-1 pb-3 text-sm font-medium transition ' . ($on ? 'border-crimson text-crimson' : 'border-transparent text-ink-soft hover:text-ink');
?>
<header>
  <p class="eyebrow">Hàng hoá</p>
  <h1 class="mt-1 text-4xl">Danh mục & thuộc tính</h1>
  <p class="mt-1 text-sm text-ink-soft">Sửa trực tiếp trên từng dòng rồi bấm Lưu. Mục đang được sản phẩm sử dụng không thể xoá.</p>
</header>

<nav class="mt-6 flex gap-6 border-b border-line" aria-label="Loại">
  <?php foreach (Taxonomy::TYPES as $type => $label): ?>
    <a href="<?= e(url('/admin/danh-muc', ['tab' => $type])) ?>" class="<?= $tabCls($tab === $type) ?>" <?= $tab === $type ? 'aria-current="page"' : '' ?>>
      <?= e($label) ?> <span class="text-xs text-ink-soft">(<?= count($lists[$type]) ?>)</span>
    </a>
  <?php endforeach; ?>
</nav>

<div class="mt-6" data-taxonomy="<?= e($tab) ?>">
  <?= View::partial("admin/taxonomy/$tab", ['lists' => $lists]) ?>
</div>
