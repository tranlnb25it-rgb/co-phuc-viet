<?php
/** Staff accounts, each row an inline edit form (page render + Ajax). @var array $staff */
use App\Support\Auth;

$me = Auth::user()['id'];
$in = 'w-full rounded-sm border border-line bg-white px-2.5 py-1.5 text-sm focus:border-gold focus:outline-none focus:ring-2 focus:ring-gold/30 aria-[invalid=true]:border-crimson';
$grid = 'md:grid-cols-[1.1fr_1.2fr_0.8fr_1fr_90px_auto]';
?>
<div class="border border-line bg-white/70">
  <div class="hidden gap-2 border-b border-line bg-silk/60 px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-ink-soft md:grid <?= $grid ?>">
    <span>Họ tên</span><span>Email</span><span>Điện thoại</span><span>Vai trò</span><span>Hoạt động</span><span></span>
  </div>
  <div class="divide-y divide-line">
    <?php foreach ($staff as $u): $self = (int) $u['id'] === $me; ?>
      <form class="grid items-start gap-2 px-4 py-3 <?= $grid ?> <?= $u['is_active'] ? '' : 'bg-line/30 opacity-70' ?>"
            data-staff-form="<?= e(url('/admin/api/staff/' . $u['id'])) ?>" data-method="PUT">
        <label class="min-w-0"><span class="sr-only">Họ tên</span><input name="full_name" value="<?= e($u['full_name']) ?>" class="<?= $in ?>">
          <span class="block text-[11px] text-crimson" data-error-for="full_name"></span>
          <span class="block text-[11px] text-ink-soft"><?= $u['last_login_at'] ? 'Đăng nhập ' . e(date('H:i d/m', strtotime($u['last_login_at']))) : 'Chưa đăng nhập' ?> · <?= (int) $u['actions_30d'] ?> thao tác/30 ngày</span></label>
        <label class="min-w-0"><span class="sr-only">Email</span><input name="email" type="email" value="<?= e($u['email']) ?>" class="<?= $in ?>"><span class="block text-[11px] text-crimson" data-error-for="email"></span></label>
        <label class="min-w-0"><span class="sr-only">Điện thoại</span><input name="phone" value="<?= e($u['phone'] ?? '') ?>" class="<?= $in ?>"><span class="block text-[11px] text-crimson" data-error-for="phone"></span></label>
        <label class="min-w-0"><span class="sr-only">Vai trò</span>
          <select name="role" class="<?= $in ?>" <?= $self ? 'disabled title="Không thể tự đổi vai trò"' : '' ?>>
            <?php foreach (Auth::ROLES as $k => $l): ?><option value="<?= $k ?>" <?= $u['role'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
          </select>
          <?php if ($self): ?><input type="hidden" name="role" value="<?= e($u['role']) ?>"><?php endif; ?>
          <span class="block text-[11px] text-crimson" data-error-for="role"></span></label>
        <label class="flex items-center gap-2 pt-1.5 text-sm">
          <input type="checkbox" name="is_active" value="1" class="h-4 w-4 accent-crimson" <?= $u['is_active'] ? 'checked' : '' ?> <?= $self ? 'disabled' : '' ?>>
          <?php if ($self): ?><input type="hidden" name="is_active" value="1"><?php endif; ?>
          <?= $u['is_active'] ? 'Đang dùng' : 'Đã khoá' ?>
        </label>
        <div class="flex items-center justify-end gap-1">
          <?php if ($self): ?><span class="mr-1 rounded-full bg-gold/20 px-2 py-0.5 text-[11px] text-gold-dark">Bạn</span><?php endif; ?>
          <button class="rounded-sm bg-crimson px-3 py-1.5 text-xs font-medium text-ivory hover:bg-crimson-dark">Lưu</button>
          <?php if (!$self): ?>
            <button type="button" class="rounded-sm border border-line px-2 py-1.5 text-xs hover:border-gold" title="Đặt mật khẩu mới"
                    data-staff-password="<?= e(url('/admin/api/staff/' . $u['id'] . '/password')) ?>" data-email="<?= e($u['email']) ?>"><?= icon('lock', 'h-3.5 w-3.5') ?></button>
          <?php endif; ?>
        </div>
      </form>
    <?php endforeach; ?>
  </div>
</div>
