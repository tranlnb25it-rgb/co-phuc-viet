<?php
/** @var array $result @var array $filters */
use App\Core\View;
use App\Services\Activity;

$link = function (array $a): ?string {
    return match ($a['entity']) {
        'order'   => url('/admin/don-hang/' . $a['entity_id']),
        'product' => $a['entity_id'] ? url('/admin/san-pham/' . $a['entity_id']) : null,
        'variant' => url('/admin/kho', ['tab' => 'history', 'variant' => $a['entity_id']]),
        default   => null,
    };
};
?>
<p class="mb-3 text-sm text-ink-soft"><?= (int) $result['total'] ?> hoạt động</p>
<?php if (!$result['items']): ?>
  <div class="border border-dashed border-line px-6 py-16 text-center text-ink-soft">Chưa có hoạt động phù hợp.</div>
<?php else: ?>
<ol class="divide-y divide-line border border-line bg-white/70">
  <?php foreach ($result['items'] as $a): $group = strtok($a['action'], '.'); $href = $link($a); ?>
    <li class="grid gap-1 px-4 py-3 text-sm sm:grid-cols-[130px_150px_1fr] sm:gap-4">
      <time class="text-ink-soft tabular-nums" datetime="<?= e(date('c', strtotime($a['created_at']))) ?>"><?= e(date('H:i d/m/Y', strtotime($a['created_at']))) ?></time>
      <span class="font-medium"><?= e($a['user_name']) ?></span>
      <span class="min-w-0">
        <span class="mr-1.5 rounded-full bg-silk px-2 py-0.5 text-[11px] text-ink-soft"><?= e(Activity::GROUPS[$group] ?? $group) ?></span>
        <?php if ($href): ?><a href="<?= e($href) ?>" class="hover:text-crimson"><?= e($a['message']) ?></a><?php else: ?><?= e($a['message']) ?><?php endif; ?>
        <?php if ($a['ip']): ?><span class="ml-1 text-[11px] text-ink-soft">· IP <?= e($a['ip']) ?></span><?php endif; ?>
      </span>
    </li>
  <?php endforeach; ?>
</ol>
<?= View::partial('admin/partials/pagination', ['result' => $result, 'filters' => $filters, 'path' => '/admin/nhat-ky']) ?>
<?php endif; ?>
