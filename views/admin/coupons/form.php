<?php
/** @var array $coupon */
use App\Services\Coupons;

$c = $coupon;
$isNew = empty($c['id']);
$v = fn(string $k) => e((string) ($c[$k] ?? ''));
$dt = fn(string $k) => !empty($c[$k]) ? e(date('Y-m-d\TH:i', strtotime($c[$k]))) : '';
$in = 'input mt-1.5 aria-[invalid=true]:border-crimson aria-[invalid=true]:ring-2 aria-[invalid=true]:ring-crimson/20';
$err = fn(string $k) => '<span class="mt-1 block min-h-4 text-xs text-crimson" data-error-for="' . $k . '"></span>';
$card = 'border border-line bg-white/70 p-5 sm:p-6';
?>
<header class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <a href="<?= e(url('/admin/khuyen-mai')) ?>" class="text-sm text-ink-soft hover:text-crimson">← Mã giảm giá</a>
    <h1 class="mt-1 flex flex-wrap items-center gap-3 text-4xl"><?= $isNew ? 'Tạo mã giảm giá' : '<span class="font-mono text-3xl">' . e($c['code']) . '</span>' ?>
      <?php if (!$isNew): [$st, $stCls] = Coupons::status($c); ?><span class="rounded-full px-3 py-1 font-sans text-sm font-medium <?= $stCls ?>"><?= e($st) ?></span><?php endif; ?>
    </h1>
    <?php if (!$isNew): ?><p class="mt-1 text-sm text-ink-soft">Đã dùng <?= (int) $c['used_count'] ?> lần<?= $c['usage_limit'] !== null ? ' / ' . (int) $c['usage_limit'] : '' ?></p><?php endif; ?>
  </div>
  <?php if (!$isNew): ?>
    <button type="button" class="btn-outline px-4! py-2!" data-coupon-delete="<?= e(url('/admin/api/coupons/' . $c['id'])) ?>"
            data-back="<?= e(url('/admin/khuyen-mai')) ?>"><?= icon('trash', 'h-4 w-4') ?> Xoá</button>
  <?php endif; ?>
</header>

<form class="mt-6 grid gap-6 xl:grid-cols-[1fr_340px]" novalidate data-coupon-admin
      data-url="<?= e(url($isNew ? '/admin/api/coupons' : '/admin/api/coupons/' . $c['id'])) ?>" data-method="<?= $isNew ? 'POST' : 'PUT' ?>">
  <div class="space-y-6">
    <section class="<?= $card ?>">
      <h2 class="text-2xl">Ưu đãi</h2>
      <div class="mt-4 grid gap-x-5 sm:grid-cols-2">
        <label class="block"><span class="text-sm font-medium">Mã *</span>
          <span class="mt-1.5 flex gap-2">
            <input name="code" value="<?= $v('code') ?>" maxlength="40" class="<?= $in ?> mt-0! font-mono uppercase tracking-wider" autocomplete="off">
            <button type="button" class="btn-outline shrink-0 px-3! py-2!" title="Tạo mã ngẫu nhiên" data-generate-code="<?= e(url('/admin/api/coupons/generate')) ?>"><?= icon('rotate-ccw', 'h-4 w-4') ?></button>
          </span><?= $err('code') ?></label>
        <label class="block"><span class="text-sm font-medium">Mô tả hiển thị cho khách</span>
          <input name="description" value="<?= $v('description') ?>" maxlength="255" class="<?= $in ?>" placeholder="VD: Ưu đãi khai trương"><?= $err('description') ?></label>
      </div>

      <fieldset class="mt-2"><legend class="text-sm font-medium">Loại *</legend>
        <div class="mt-2 grid gap-2 sm:grid-cols-3">
          <?php foreach (Coupons::TYPES as $k => $label): ?>
            <label class="cursor-pointer">
              <input type="radio" name="type" value="<?= $k ?>" class="peer sr-only" <?= ($c['type'] ?? 'percent') === $k ? 'checked' : '' ?> data-coupon-type>
              <span class="block border border-line bg-white px-3 py-2.5 text-center text-sm transition hover:border-gold peer-checked:border-crimson peer-checked:bg-crimson peer-checked:text-ivory peer-focus-visible:outline-2 peer-focus-visible:outline-gold"><?= e($label) ?></span>
            </label>
          <?php endforeach; ?>
        </div><?= $err('type') ?>
      </fieldset>

      <div class="grid gap-x-5 sm:grid-cols-2" data-show-for="percent fixed">
        <label class="block"><span class="text-sm font-medium" data-value-label>Giá trị *</span>
          <input name="value" value="<?= $v('value') ?>" inputmode="numeric" class="<?= $in ?>"><?= $err('value') ?></label>
        <label class="block" data-show-for="percent"><span class="text-sm font-medium">Giảm tối đa (₫)</span>
          <input name="max_discount" value="<?= $v('max_discount') ?>" inputmode="numeric" class="<?= $in ?>" placeholder="Không giới hạn"><?= $err('max_discount') ?></label>
      </div>
      <label class="block sm:max-w-xs"><span class="text-sm font-medium">Đơn tối thiểu (₫)</span>
        <input name="min_order" value="<?= $v('min_order') ?>" inputmode="numeric" class="<?= $in ?>" placeholder="0"><?= $err('min_order') ?>
        <span class="-mt-3 block text-xs text-ink-soft">Tính trên tổng tiền hàng (chưa gồm phí ship)</span></label>
    </section>
  </div>

  <div class="space-y-6 xl:sticky xl:top-6 xl:self-start">
    <section class="<?= $card ?>">
      <h2 class="text-2xl">Thời gian & giới hạn</h2>
      <label class="mt-4 block"><span class="text-sm font-medium">Bắt đầu</span>
        <input type="datetime-local" name="starts_at" value="<?= $dt('starts_at') ?>" class="<?= $in ?>"><?= $err('starts_at') ?></label>
      <label class="block"><span class="text-sm font-medium">Kết thúc</span>
        <input type="datetime-local" name="ends_at" value="<?= $dt('ends_at') ?>" class="<?= $in ?>"><?= $err('ends_at') ?></label>
      <div class="grid grid-cols-2 gap-3">
        <label class="block"><span class="text-sm font-medium">Tổng lượt dùng</span>
          <input name="usage_limit" value="<?= $v('usage_limit') ?>" inputmode="numeric" class="<?= $in ?>" placeholder="∞"><?= $err('usage_limit') ?></label>
        <label class="block"><span class="text-sm font-medium">Mỗi khách (SĐT)</span>
          <input name="per_phone_limit" value="<?= $v('per_phone_limit') ?>" inputmode="numeric" class="<?= $in ?>" placeholder="∞"><?= $err('per_phone_limit') ?></label>
      </div>
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="h-4 w-4 accent-crimson" <?= !empty($c['is_active']) ? 'checked' : '' ?>> Đang bật</label>
      <button class="btn-primary mt-5 w-full" data-save data-label="<?= $isNew ? 'Tạo mã' : 'Lưu thay đổi' ?>"><?= icon('check', 'h-4 w-4') ?> <span><?= $isNew ? 'Tạo mã' : 'Lưu thay đổi' ?></span></button>
      <p class="mt-2 hidden text-center text-xs text-crimson" data-form-error></p>
    </section>
  </div>
</form>
