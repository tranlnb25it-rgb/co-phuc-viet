<?php
/**
 * Create / edit product. Main fields save via Ajax (POST or PUT JSON); images and variants
 * have their own Ajax sections once the product exists.
 * @var array $product @var array $categories @var array $dynasties @var array $fabrics
 */
use App\Core\View;
use App\Services\AdminProductService as Products;

$p = $product;
$isNew = empty($p['id']);
$v = fn(string $k) => e($p[$k] ?? '');

$label = 'text-sm font-medium';
$err = fn(string $k) => '<span class="mt-1 block min-h-4 text-xs text-crimson" data-error-for="' . $k . '"></span>';
$inputCls = 'input mt-1.5 aria-[invalid=true]:border-crimson aria-[invalid=true]:ring-2 aria-[invalid=true]:ring-crimson/20';
$card = 'border border-line bg-white/70 p-5 sm:p-6';
?>
<header class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <a href="<?= e(url('/admin/san-pham')) ?>" class="text-sm text-ink-soft hover:text-crimson">← Sản phẩm</a>
    <h1 class="mt-1 text-4xl"><?= $isNew ? 'Thêm sản phẩm' : e($p['name_vi']) ?></h1>
  </div>
  <?php if (!$isNew): ?>
    <div class="flex gap-2">
      <a href="#anh" class="btn-outline px-4! py-2!">Ảnh</a>
      <a href="#ton-kho" class="btn-outline px-4! py-2!">Tồn kho</a>
      <a href="<?= e(url('/san-pham/' . $p['slug'])) ?>" target="_blank" rel="noopener" class="btn-outline px-4! py-2!" data-view-link>Xem trên web ↗</a>
      <button type="button" class="btn-outline px-4! py-2!" data-duplicate="<?= e(url('/admin/api/products/' . $p['id'] . '/duplicate')) ?>"
              title="Tạo bản sao (đang ẩn, tồn kho 0) để làm sản phẩm tương tự"><?= icon('copy', 'h-4 w-4') ?> Nhân bản</button>
    </div>
  <?php endif; ?>
</header>

<?php if (!empty($_GET['duplicated'])): ?>
  <p class="mt-4 flex items-center gap-2 border border-sky-300 bg-sky-50 px-4 py-3 text-sm text-sky-800"><?= icon('copy', 'h-4 w-4') ?> Đây là bản sao — đang ẩn và tồn kho bằng 0. Hãy đổi tên, đường dẫn, rồi bật “Đang bán”.</p>
<?php endif; ?>
<?php if (!empty($_GET['created'])): ?>
  <p class="mt-4 flex items-center gap-2 border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><?= icon('check', 'h-4 w-4') ?> Đã tạo sản phẩm. Hãy thêm ảnh và tạo biến thể tồn kho bên dưới.</p>
<?php endif; ?>

<form id="product-admin-form" class="mt-6 grid gap-6 xl:grid-cols-[1fr_320px]" novalidate
      data-url="<?= e(url($isNew ? '/admin/api/products' : '/admin/api/products/' . $p['id'])) ?>"
      data-method="<?= $isNew ? 'POST' : 'PUT' ?>">

  <div class="space-y-6">
    <section class="<?= $card ?>">
      <h2 class="text-2xl">Thông tin cơ bản</h2>
      <div class="mt-4 grid gap-x-5 sm:grid-cols-2">
        <label class="block"><span class="<?= $label ?>">Tên (Tiếng Việt) *</span>
          <input name="name_vi" value="<?= $v('name_vi') ?>" required maxlength="160" class="<?= $inputCls ?>" data-slug-source><?= $err('name_vi') ?></label>
        <label class="block"><span class="<?= $label ?>">Tên (English)</span>
          <input name="name_en" value="<?= $v('name_en') ?>" maxlength="160" class="<?= $inputCls ?>" placeholder="Để trống = dùng tên tiếng Việt"><?= $err('name_en') ?></label>
        <label class="block sm:col-span-2"><span class="<?= $label ?>">Đường dẫn (slug)</span>
          <span class="mt-1.5 flex items-center">
            <span class="rounded-l-sm border border-r-0 border-line bg-silk px-3 py-2.5 text-xs text-ink-soft">/san-pham/</span>
            <input name="slug" value="<?= $v('slug') ?>" maxlength="160" class="<?= $inputCls ?> mt-0! rounded-l-none! font-mono" data-slug-target <?= $isNew ? '' : 'data-slug-locked' ?>>
          </span><?= $err('slug') ?></label>
        <label class="block"><span class="<?= $label ?>">Danh mục *</span>
          <select name="category_id" class="<?= $inputCls ?>">
            <option value="">— Chọn —</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>" <?= (int) ($p['category_id'] ?? 0) === $c['id'] ? 'selected' : '' ?>><?= e($c['label']) ?></option>
            <?php endforeach; ?>
          </select><?= $err('category_id') ?></label>
        <label class="block"><span class="<?= $label ?>">Triều đại</span>
          <select name="dynasty_id" class="<?= $inputCls ?>">
            <option value="">Không gắn triều đại (phụ kiện)</option>
            <?php foreach ($dynasties as $d): ?>
              <option value="<?= $d['id'] ?>" <?= (int) ($p['dynasty_id'] ?? 0) === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['name_vi']) ?></option>
            <?php endforeach; ?>
          </select><?= $err('dynasty_id') ?></label>
        <fieldset class="sm:col-span-2"><legend class="<?= $label ?>">Giới tính</legend>
          <div class="mt-2 flex gap-2">
            <?php foreach (Products::GENDERS as $k => $g): ?>
              <label class="cursor-pointer"><input type="radio" name="gender" value="<?= $k ?>" class="peer sr-only" <?= ($p['gender'] ?? 'unisex') === $k ? 'checked' : '' ?>>
                <span class="block border border-line px-4 py-2 text-sm transition peer-checked:border-crimson peer-checked:bg-crimson peer-checked:text-ivory peer-focus-visible:outline-2 peer-focus-visible:outline-gold"><?= $g ?></span></label>
            <?php endforeach; ?>
          </div><?= $err('gender') ?></fieldset>
      </div>
    </section>

    <section class="<?= $card ?>">
      <h2 class="text-2xl">Mô tả</h2>
      <div class="mt-4 grid gap-x-5 sm:grid-cols-2">
        <label class="block"><span class="<?= $label ?>">Mô tả ngắn (VI)</span>
          <textarea name="short_description_vi" rows="3" maxlength="500" class="<?= $inputCls ?> resize-y"><?= $v('short_description_vi') ?></textarea><?= $err('short_description_vi') ?></label>
        <label class="block"><span class="<?= $label ?>">Short description (EN)</span>
          <textarea name="short_description_en" rows="3" maxlength="500" class="<?= $inputCls ?> resize-y"><?= $v('short_description_en') ?></textarea><?= $err('short_description_en') ?></label>
        <label class="block"><span class="<?= $label ?>">Bối cảnh lịch sử (VI)</span>
          <textarea name="historical_context_vi" rows="7" maxlength="5000" class="<?= $inputCls ?> resize-y"><?= $v('historical_context_vi') ?></textarea><?= $err('historical_context_vi') ?></label>
        <label class="block"><span class="<?= $label ?>">Historical context (EN)</span>
          <textarea name="historical_context_en" rows="7" maxlength="5000" class="<?= $inputCls ?> resize-y"><?= $v('historical_context_en') ?></textarea><?= $err('historical_context_en') ?></label>
      </div>
    </section>
  </div>

  <div class="space-y-6 xl:sticky xl:top-6 xl:self-start">
    <section class="<?= $card ?>">
      <h2 class="text-2xl">Giá</h2>
      <label class="mt-4 block"><span class="<?= $label ?>">Giá gốc (₫) *</span>
        <input name="base_price" value="<?= $v('base_price') ?>" inputmode="numeric" class="<?= $inputCls ?>" placeholder="2800000"><?= $err('base_price') ?></label>
      <p class="-mt-2 mb-3 text-xs text-ink-soft">Phụ phí chất liệu:
        <?= e(implode(', ', array_map(fn($f) => $f['name_vi'] . ' +' . money($f['price_modifier']), $fabrics))) ?></p>
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="allow_custom_tailoring" value="1" class="h-4 w-4 accent-crimson" <?= !empty($p['allow_custom_tailoring']) ? 'checked' : '' ?>> Cho phép may đo</label>
      <label class="mt-3 block"><span class="<?= $label ?>">Phí may đo (₫)</span>
        <input name="custom_tailoring_fee" value="<?= $v('custom_tailoring_fee') ?>" inputmode="numeric" class="<?= $inputCls ?>"><?= $err('custom_tailoring_fee') ?></label>
    </section>

    <?php
    $saleInfo = isset($p['base_price']) ? App\Support\Pricing::info($p + ['sale_price' => null]) : null;
    $dtVal = fn(string $k) => !empty($p[$k]) ? e(date('Y-m-d\TH:i', strtotime($p[$k]))) : '';
    [$saleLabel, $saleCls] = match (true) {
        $saleInfo === null || empty($p['sale_price'])          => ['Không giảm giá', 'bg-line text-ink-soft'],
        $saleInfo['on_sale']                                   => ['Đang giảm ' . $saleInfo['percent'] . '%', 'bg-emerald-100 text-emerald-800'],
        $saleInfo['scheduled']                                 => ['Đã lên lịch', 'bg-sky-100 text-sky-800'],
        default                                                => ['Đã hết hạn', 'bg-ink/10 text-ink'],
    };
    ?>
    <section class="<?= $card ?>">
      <div class="flex items-center justify-between gap-2">
        <h2 class="text-2xl">Khuyến mãi</h2>
        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium <?= $saleCls ?>"><?= e($saleLabel) ?></span>
      </div>
      <label class="mt-4 block"><span class="<?= $label ?>">Giá khuyến mãi (₫)</span>
        <input name="sale_price" value="<?= $v('sale_price') ?>" inputmode="numeric" class="<?= $inputCls ?>" placeholder="Để trống = không giảm"><?= $err('sale_price') ?></label>
      <div class="grid grid-cols-2 gap-3">
        <label class="block"><span class="text-xs font-medium">Bắt đầu</span>
          <input type="datetime-local" name="sale_starts_at" value="<?= $dtVal('sale_starts_at') ?>" class="<?= $inputCls ?> px-2! text-xs"><?= $err('sale_starts_at') ?></label>
        <label class="block"><span class="text-xs font-medium">Kết thúc</span>
          <input type="datetime-local" name="sale_ends_at" value="<?= $dtVal('sale_ends_at') ?>" class="<?= $inputCls ?> px-2! text-xs"><?= $err('sale_ends_at') ?></label>
      </div>
      <p class="text-xs text-ink-soft">Để trống ngày = chạy ngay / không hết hạn. Giá sale thay cho giá gốc; phụ phí chất liệu và may đo vẫn cộng thêm.</p>
    </section>

    <section class="<?= $card ?>">
      <h2 class="text-2xl">Hiển thị</h2>
      <label class="mt-4 flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="h-4 w-4 accent-crimson" <?= !empty($p['is_active']) ? 'checked' : '' ?>> Đang bán (hiện trên cửa hàng)</label>
      <label class="mt-2 flex items-center gap-2 text-sm"><input type="checkbox" name="is_featured" value="1" class="h-4 w-4 accent-crimson" <?= !empty($p['is_featured']) ? 'checked' : '' ?>> Nổi bật trên trang chủ</label>
      <button type="submit" class="btn-primary mt-6 w-full" data-save data-label="<?= $isNew ? 'Tạo sản phẩm' : 'Lưu thay đổi' ?>">
        <?= icon('check', 'h-4 w-4') ?> <span><?= $isNew ? 'Tạo sản phẩm' : 'Lưu thay đổi' ?></span>
      </button>
      <p class="mt-2 hidden text-center text-xs text-crimson" data-form-error></p>
    </section>
  </div>
</form>

<?php if (!$isNew): ?>
  <section id="anh" class="<?= $card ?> mt-6 scroll-mt-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h2 class="text-2xl">Ảnh sản phẩm</h2>
        <p class="text-xs text-ink-soft">JPG, PNG hoặc WebP, tối đa 5MB mỗi ảnh. Ảnh đầu tiên loại “Mặt trước” là ảnh đại diện.</p>
      </div>
      <form class="flex flex-wrap items-center gap-2" data-image-upload="<?= e(url('/admin/api/products/' . $p['id'] . '/images')) ?>">
        <select name="view_type" class="input w-auto! py-2!" aria-label="Loại ảnh">
          <?php foreach (Products::VIEW_TYPES as $k => $label_): ?><option value="<?= $k ?>"><?= $label_ ?></option><?php endforeach; ?>
        </select>
        <label class="btn-outline cursor-pointer px-4! py-2!">
          <?= icon('plus', 'h-4 w-4') ?> <span data-upload-label>Chọn ảnh</span>
          <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple class="sr-only">
        </label>
      </form>
    </div>
    <div class="mt-5" data-images><?= View::partial('admin/products/images', ['product' => $p]) ?></div>
  </section>

  <section id="ton-kho" class="<?= $card ?> mt-6 scroll-mt-6">
    <div data-variants><?= View::partial('admin/products/variants', ['product' => $p, 'fabrics' => $fabrics]) ?></div>
  </section>
<?php endif; ?>
