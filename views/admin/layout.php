<?php
/** Admin layout: sidebar on desktop, top bar on mobile. @var string $content @var string|null $title */
use App\Support\Auth;

$path = current_path();
// [href, label, icon, permission, section]
$items = [
    ['/admin',            'Tổng quan',   'globe',        'dashboard',   'Bán hàng'],
    ['/admin/don-hang',   'Đơn hàng',    'truck',        'orders.view', 'Bán hàng'],
    ['/admin/khach-hang', 'Khách hàng',  'award',        'customers',   'Bán hàng'],
    ['/admin/khuyen-mai', 'Khuyến mãi',  'banknote',     'promotions',  'Bán hàng'],
    ['/admin/danh-gia',   'Đánh giá',    'star',         'reviews',     'Bán hàng'],
    ['/admin/san-pham',   'Sản phẩm',    'shopping-bag', 'products',    'Hàng hoá'],
    ['/admin/kho',        'Kho hàng',    'landmark',     'inventory',   'Hàng hoá'],
    ['/admin/danh-muc',   'Danh mục',    'book-open',    'catalog',     'Hàng hoá'],
    ['/admin/nhan-vien',  'Nhân viên',   'lock',         'staff',       'Hệ thống'],
    ['/admin/nhat-ky',    'Nhật ký',     'rotate-ccw',   'activity',    'Hệ thống'],
    ['/admin/cai-dat',    'Cài đặt',     'scissors',     'settings',    'Hệ thống'],
];
$nav = [];
foreach ($items as [$href, $label, $ic, $perm, $section]) {
    if (!Auth::can($perm)) continue;
    $active = $href === '/admin' ? $path === '/admin' : str_starts_with($path, $href);
    $nav[$section][] = [$href, $label, $ic, $active];
}
$pendingReviews = Auth::can('reviews') ? App\Services\Reviews::pendingCount() : 0;
$user = Auth::user();
?>
<!doctype html>
<html lang="vi">
<head><?= App\Core\View::partial('admin/partials/head', ['title' => $title ?? null]) ?></head>
<body class="bg-ivory text-ink" data-base="<?= e(url('/')) ?>">
<div class="min-h-screen lg:grid lg:grid-cols-[240px_1fr]">

  <aside class="bg-ink text-ivory/80 lg:sticky lg:top-0 lg:h-screen">
    <div class="h-1 bg-gradient-to-r from-crimson via-gold to-crimson"></div>
    <div class="flex items-center justify-between gap-4 px-5 py-4 lg:block lg:py-6">
      <a href="<?= e(url('/admin')) ?>" class="flex items-center gap-2 text-gold">
        <?= lotus('h-7 w-7') ?>
        <span class="font-serif text-xl font-bold leading-none"><?= e(t('brand')) ?><span class="block font-sans text-[10px] font-medium uppercase tracking-[0.3em] text-ivory/50">Quản trị</span></span>
      </a>
      <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener" class="text-xs text-ivory/60 hover:text-gold lg:mt-4 lg:inline-flex lg:items-center lg:gap-1">Xem cửa hàng ↗</a>
    </div>
    <nav class="flex gap-1 overflow-x-auto px-3 pb-3 lg:max-h-[calc(100vh-15rem)] lg:flex-col lg:overflow-y-auto lg:pb-0" aria-label="Quản trị">
      <?php foreach ($nav as $section => $links): ?>
        <p class="hidden px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-[0.25em] text-ivory/40 first:pt-0 lg:block"><?= e($section) ?></p>
        <?php foreach ($links as [$href, $label, $ic, $active]): ?>
          <a href="<?= e(url($href)) ?>"
             class="flex shrink-0 items-center gap-3 rounded-sm px-3 py-2 text-sm transition <?= $active ? 'bg-crimson text-ivory' : 'hover:bg-ivory/10 hover:text-ivory' ?>"
             <?= $active ? 'aria-current="page"' : '' ?>>
            <?= icon($ic, 'h-4 w-4') ?> <?= e($label) ?>
            <?php if ($href === '/admin/danh-gia' && $pendingReviews): ?><span class="ml-auto rounded-full bg-gold px-1.5 text-[10px] font-semibold text-ink" title="Chờ duyệt"><?= $pendingReviews ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <?php if ($user): ?>
      <div class="hidden border-t border-ivory/10 px-5 py-4 text-xs lg:absolute lg:inset-x-0 lg:bottom-0 lg:block">
        <a href="<?= e(url('/admin/tai-khoan')) ?>" class="group block">
          <p class="font-medium text-ivory group-hover:text-gold"><?= e($user['name']) ?></p>
          <p class="text-ivory/50"><?= e(Auth::ROLES[$user['role']] ?? '') ?></p>
        </a>
        <form method="post" action="<?= e(url('/admin/dang-xuat')) ?>" class="mt-3">
          <?= csrf_field() ?>
          <button class="text-gold hover:underline">Đăng xuất</button>
        </form>
      </div>
    <?php endif; ?>
  </aside>

  <main class="min-w-0 px-4 py-6 sm:px-8 lg:py-8">
    <!-- Top bar: new-order bell (polled by admin.js) -->
    <div class="mb-4 flex items-center justify-end gap-3">
      <?php if ($user): ?>
        <a href="<?= e(url('/admin/tai-khoan')) ?>" class="text-sm text-ink-soft hover:text-crimson lg:hidden"><?= e($user['name']) ?></a>
      <?php endif; ?>
      <?php if (Auth::can('orders.view')): ?>
      <div class="relative" data-bell data-endpoint="<?= e(url('/admin/api/notifications')) ?>"
           data-latest-id="<?= (int) (App\Core\Database::one('SELECT MAX(id) m FROM orders')['m'] ?? 0) ?>">
        <button type="button" class="relative rounded-full border border-line bg-white p-2.5 transition hover:border-gold" aria-haspopup="true" aria-expanded="false"
                aria-label="Thông báo đơn hàng" data-bell-toggle>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
          <?php $pending = (int) App\Core\Database::one("SELECT COUNT(*) n FROM orders WHERE status = 'pending'")['n']; ?>
          <span data-bell-count class="<?= $pending ? '' : 'hidden' ?> absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-crimson px-1 text-[10px] font-semibold text-ivory"><?= $pending ?: '' ?></span>
        </button>
        <div class="absolute right-0 z-40 mt-2 hidden w-80 max-w-[calc(100vw-2rem)] border border-line bg-white shadow-xl" data-bell-panel role="dialog" aria-label="Đơn chờ xác nhận"></div>
      </div>
      <?php endif; ?>
    </div>
    <?= $content ?>
  </main>
</div>

<!-- toast for Ajax feedback -->
<div class="pointer-events-none fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-sm px-4 py-3 text-sm text-ivory shadow-xl" data-admin-toast role="status"></div>
<script src="<?= asset('js/admin.js') ?>" defer></script>
</body>
</html>
