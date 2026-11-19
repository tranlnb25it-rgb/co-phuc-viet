<?php
/** @var array $filters @var array $result @var array $counts */
use App\Core\View;
use App\Services\Reviews;

$tabs = ['' => 'Tất cả'] + array_map(fn($s) => $s[0], Reviews::STATUSES);
?>
<header class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <p class="eyebrow">Bán hàng</p>
    <h1 class="mt-1 text-4xl">Đánh giá sản phẩm</h1>
    <p class="mt-1 text-sm text-ink-soft">Chỉ khách đã nhận hàng mới đánh giá được. Duyệt để hiển thị trên cửa hàng, trả lời công khai ngay dưới đánh giá.
      <a href="<?= e(url('/admin/cai-dat')) ?>" class="text-crimson underline">Bật tự động duyệt</a></p>
  </div>
</header>

<form class="mt-6 flex flex-wrap items-end gap-3" method="get" action="<?= e(url('/admin/danh-gia')) ?>" data-ajax-filter="#review-list">
  <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Trạng thái">
    <?php foreach ($tabs as $k => $label): ?>
      <label class="cursor-pointer">
        <input type="radio" name="status" value="<?= $k ?>" class="peer sr-only" <?= $filters['status'] === $k ? 'checked' : '' ?>>
        <span class="inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-3 py-1.5 text-sm transition hover:border-gold peer-checked:border-crimson peer-checked:bg-crimson peer-checked:text-ivory peer-focus-visible:outline-2 peer-focus-visible:outline-gold">
          <?= e($label) ?> <span class="rounded-full bg-ink/10 px-1.5 text-[11px]" data-count="<?= $k === '' ? 'all' : $k ?>"><?= (int) $counts[$k === '' ? 'all' : $k] ?></span>
        </span>
      </label>
    <?php endforeach; ?>
  </div>
  <label class="text-sm"><span class="sr-only">Số sao</span>
    <select name="rating" class="input w-auto py-2!">
      <option value="">Mọi số sao</option>
      <?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>" <?= $filters['rating'] === (string) $i ? 'selected' : '' ?>><?= $i ?> sao</option><?php endfor; ?>
    </select>
  </label>
  <label class="relative block min-w-56 flex-1 sm:max-w-xs">
    <span class="sr-only">Tìm</span>
    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-ink-soft"><?= icon('search', 'h-4 w-4') ?></span>
    <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Nội dung, khách, sản phẩm…" class="input pl-9" autocomplete="off">
  </label>
  <noscript><button class="btn-primary">Lọc</button></noscript>
</form>

<!-- bulk actions (shown when reviews are ticked) -->
<div class="sticky top-2 z-30 mt-4 hidden items-center gap-3 border border-ink bg-ink px-4 py-2.5 text-sm text-ivory shadow-lg" data-review-bulk>
  <span>Đã chọn <strong data-review-bulk-count>0</strong></span>
  <button type="button" class="rounded-sm bg-emerald-700 px-3 py-1 hover:bg-emerald-600" data-review-bulk-status="approved">Duyệt</button>
  <button type="button" class="rounded-sm bg-ivory/15 px-3 py-1 hover:bg-ivory/25" data-review-bulk-status="rejected">Ẩn</button>
  <button type="button" class="ml-auto text-ivory/70 hover:text-ivory" data-review-bulk-clear>Bỏ chọn</button>
</div>

<div id="review-list" class="mt-4 transition-opacity" data-endpoint="<?= e(url('/admin/api/reviews')) ?>">
  <?= View::partial('admin/reviews/rows', ['result' => $result, 'filters' => $filters]) ?>
</div>
