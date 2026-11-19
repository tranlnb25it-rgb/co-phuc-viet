<?php
/** One review in the moderation list. Re-rendered after each action. @var array $r */
use App\Services\Reviews;

[$stLabel, $stCls] = Reviews::STATUSES[$r['status']];
$api = url('/admin/api/reviews/' . $r['id']);
$fit = ['small' => 'Hơi chật', 'true' => 'Vừa vặn', 'large' => 'Hơi rộng'];
?>
<article class="border bg-white/70 p-4 <?= $r['status'] === 'pending' ? 'border-gold/70 ring-1 ring-gold/30' : 'border-line' ?>" data-review-card="<?= (int) $r['id'] ?>">
  <div class="flex gap-3">
    <input type="checkbox" class="mt-1 h-4 w-4 shrink-0 accent-crimson" value="<?= (int) $r['id'] ?>" data-review-check aria-label="Chọn đánh giá #<?= (int) $r['id'] ?>">
    <div class="min-w-0 flex-1">
      <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
          <a href="<?= e(url('/san-pham/' . $r['slug'])) ?>#review-<?= (int) $r['id'] ?>" target="_blank" rel="noopener" class="text-sm font-semibold hover:text-crimson"><?= e($r['product_name']) ?> ↗</a>
          <p class="mt-1 flex flex-wrap items-center gap-2"><?= stars((float) $r['rating']) ?>
            <?php if ($r['title']): ?><strong class="text-sm"><?= e($r['title']) ?></strong><?php endif; ?></p>
        </div>
        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium <?= $stCls ?>"><?= e($stLabel) ?></span>
      </div>

      <p class="mt-2 whitespace-pre-line text-sm leading-relaxed"><?= e($r['body']) ?></p>
      <?php if ($r['images']): ?>
        <div class="mt-2 flex flex-wrap gap-2">
          <?php foreach ($r['images'] as $u): ?>
            <a href="<?= e(url($u)) ?>" target="_blank" rel="noopener" class="block h-16 w-16 overflow-hidden border border-line hover:border-gold"><img src="<?= e(url($u)) ?>" alt="Ảnh đánh giá" class="h-full w-full object-cover"></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <p class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-ink-soft">
        <span><?= icon('user', 'inline h-3.5 w-3.5') ?> <?= e($r['author_name']) ?><?= $r['email'] ? ' · ' . e($r['email']) : '' ?></span>
        <?php if ($r['order_code']): ?><a href="<?= e(url('/admin/don-hang/' . $r['order_code'])) ?>" class="font-mono text-crimson hover:underline"><?= e($r['order_code']) ?></a><?php endif; ?>
        <?php if ($r['variant_label']): ?><span><?= e($r['variant_label']) ?></span><?php endif; ?>
        <?php if ($r['fit']): ?><span>Size: <?= e($fit[$r['fit']]) ?></span><?php endif; ?>
        <span><?= e(date('H:i d/m/Y', strtotime($r['created_at']))) ?></span>
        <?php if ($r['helpful_count']): ?><span>👍 <?= (int) $r['helpful_count'] ?></span><?php endif; ?>
      </p>

      <?php if ($r['reply']): ?>
        <div class="mt-3 border-l-2 border-gold bg-silk/60 px-3 py-2 text-sm">
          <p class="text-xs font-semibold text-crimson">Phản hồi của shop<?= $r['replier'] ? ' · ' . e($r['replier']) : '' ?><?= $r['replied_at'] ? ' · ' . e(date('d/m/Y', strtotime($r['replied_at']))) : '' ?></p>
          <p class="mt-1 whitespace-pre-line"><?= e($r['reply']) ?></p>
        </div>
      <?php endif; ?>

      <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-medium">
        <?php if ($r['status'] !== 'approved'): ?>
          <button type="button" class="rounded-sm bg-emerald-700 px-3 py-1.5 text-ivory hover:bg-emerald-600" data-review-status="approved" data-url="<?= e($api) ?>"><?= icon('check', 'inline h-3.5 w-3.5') ?> Duyệt</button>
        <?php endif; ?>
        <?php if ($r['status'] !== 'rejected'): ?>
          <button type="button" class="rounded-sm border border-line px-3 py-1.5 hover:border-gold" data-review-status="rejected" data-url="<?= e($api) ?>">Ẩn</button>
        <?php endif; ?>
        <details class="group">
          <summary class="cursor-pointer list-none rounded-sm border border-line px-3 py-1.5 hover:border-gold [&::-webkit-details-marker]:hidden"><?= $r['reply'] ? 'Sửa phản hồi' : 'Trả lời' ?></summary>
          <form class="mt-2 w-[min(560px,80vw)]" data-review-reply="<?= e($api . '/reply') ?>">
            <textarea name="reply" rows="3" maxlength="1000" class="input resize-y" placeholder="Cảm ơn bạn đã tin chọn Cổ Phục Việt…"><?= e($r['reply'] ?? '') ?></textarea>
            <span class="block text-xs text-crimson" data-error-for="reply"></span>
            <div class="mt-1 flex items-center gap-2">
              <button class="btn-primary px-4! py-1.5!">Đăng phản hồi</button>
              <span class="text-[11px] font-normal text-ink-soft">Hiển thị công khai dưới đánh giá. Để trống rồi lưu để xoá.</span>
            </div>
          </form>
        </details>
        <button type="button" class="ml-auto inline-flex items-center gap-1 text-ink-soft hover:text-crimson" data-review-delete="<?= e($api) ?>"><?= icon('trash', 'h-3.5 w-3.5') ?> Xoá</button>
      </div>
    </div>
  </div>
</article>
