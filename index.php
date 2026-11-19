<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\{Config, Csrf, HttpException, Request, Response, Router, View};
use App\Controllers\{AccountController, CatalogController, CheckoutController, CustomerAuthController, HomeController,
                     LangController, OrderLookupController, ProductPageController, WishlistController};
use App\Controllers\Api\{CartController, MetaController, ProductController, ReviewController};
use App\Controllers\Admin;
use App\Support\{Auth, Customer};

$router = new Router();

// ---------- Pages (server-rendered PHP) ----------
$router->get('/',                          [HomeController::class, 'index']);
$router->get('/bo-suu-tap',                [CatalogController::class, 'index']);
$router->get('/san-pham/{slug}',           [ProductPageController::class, 'show']);
$router->get('/thanh-toan',                [CheckoutController::class, 'show']);
$router->post('/thanh-toan',               [CheckoutController::class, 'place']);     // no-JS fallback
$router->get('/dat-hang-thanh-cong/{code}',[CheckoutController::class, 'success']);
$router->get('/lang/{code}',               [LangController::class, 'switch']);

// ---------- Customer accounts (phase 9) ----------
$member = [[Customer::class, 'required']];         // signed-in customer

$router->get('/dang-nhap',                 [CustomerAuthController::class, 'show']);
$router->post('/dang-nhap',                [CustomerAuthController::class, 'login']);
$router->get('/dang-ky',                   [CustomerAuthController::class, 'show']);
$router->post('/dang-ky',                  [CustomerAuthController::class, 'register']);
$router->post('/dang-xuat',                [CustomerAuthController::class, 'logout']);
$router->get('/yeu-thich',                 [WishlistController::class, 'index']);
$router->get('/tra-cuu-don-hang',          [OrderLookupController::class, 'show']);
$router->post('/tra-cuu-don-hang',         [OrderLookupController::class, 'lookup']);
$router->get('/tai-khoan',                 [AccountController::class, 'overview'], $member);
$router->get('/tai-khoan/don-hang',        [AccountController::class, 'orders'], $member);
$router->get('/tai-khoan/don-hang/{code}', [AccountController::class, 'order'], $member);
$router->get('/tai-khoan/dia-chi',         [AccountController::class, 'addresses'], $member);
$router->get('/tai-khoan/so-do',           [AccountController::class, 'measurements'], $member);
$router->get('/tai-khoan/thong-tin',       [AccountController::class, 'profile'], $member);
$router->get('/tai-khoan/danh-gia',        [AccountController::class, 'reviews'], $member);

// ---------- Ajax / JSON API ----------
$router->get('/api/health',                 [MetaController::class, 'health']);
$router->get('/api/meta',                   [MetaController::class, 'all']);
$router->get('/api/products',               [ProductController::class, 'index']);  // format=html | catalog
$router->get('/api/products/{slug}',        [ProductController::class, 'show']);
$router->post('/api/products/{slug}/quote', [ProductController::class, 'quote']);
$router->get('/api/products/{slug}/reviews', [ReviewController::class, 'index']);
$router->post('/api/products/{slug}/reviews',[ReviewController::class, 'store'], $member);
$router->post('/api/reviews/{id}/helpful',   [ReviewController::class, 'helpful']);
$router->get('/api/cart',                   [CartController::class, 'show']);
$router->post('/api/cart/coupon',           [CartController::class, 'applyCoupon']);   // before {key}
$router->delete('/api/cart/coupon',         [CartController::class, 'removeCoupon']);
$router->post('/api/cart',                  [CartController::class, 'add']);
$router->patch('/api/cart/{key}',           [CartController::class, 'update']);
$router->delete('/api/cart/{key}',          [CartController::class, 'remove']);
$router->post('/api/orders',                [CheckoutController::class, 'place']);
$router->post('/api/orders/lookup',         [OrderLookupController::class, 'lookup']);
$router->post('/api/wishlist/{id}',         [WishlistController::class, 'toggle']);

// Ajax — my account
$router->get('/api/account/orders',                    [AccountController::class, 'orderRows'], $member);
$router->post('/api/account/orders/{code}/cancel',     [AccountController::class, 'cancelOrder'], $member);
$router->put('/api/account/profile',                   [AccountController::class, 'updateProfile'], $member);
$router->post('/api/account/password',                 [AccountController::class, 'changePassword'], $member);
$router->post('/api/account/addresses',                [AccountController::class, 'saveAddress'], $member);
$router->put('/api/account/addresses/{id}',            [AccountController::class, 'saveAddress'], $member);
$router->patch('/api/account/addresses/{id}/default',  [AccountController::class, 'defaultAddress'], $member);
$router->delete('/api/account/addresses/{id}',         [AccountController::class, 'deleteAddress'], $member);
$router->post('/api/account/measurements',             [AccountController::class, 'saveMeasurement'], $member);
$router->put('/api/account/measurements/{id}',         [AccountController::class, 'saveMeasurement'], $member);
$router->delete('/api/account/measurements/{id}',      [AccountController::class, 'deleteMeasurement'], $member);

// ---------- Admin (session login + role permissions, see Auth::PERMISSIONS) ----------
$staff = [[Auth::class, 'requireAdmin']];          // any staff member
$can = fn(string $perm) => [Auth::need($perm)];    // staff member with a permission

$router->get('/admin/dang-nhap',  [Admin\AuthController::class, 'show']);
$router->post('/admin/dang-nhap', [Admin\AuthController::class, 'login']);
$router->post('/admin/dang-xuat', [Admin\AuthController::class, 'logout'], $staff);

// pages
$router->get('/admin',                     [Admin\DashboardController::class, 'index'], $can('dashboard'));
$router->get('/admin/don-hang',            [Admin\OrderController::class, 'index'], $can('orders.view'));
$router->get('/admin/don-hang/xuat-excel', [Admin\OrderController::class, 'export'], $can('orders.manage'));   // before {code}
$router->get('/admin/don-hang/in',         [Admin\OrderController::class, 'print'], $can('orders.view'));      // before {code}
$router->get('/admin/don-hang/{code}',     [Admin\OrderController::class, 'show'], $can('orders.view'));
$router->get('/admin/khach-hang',          [Admin\CustomerController::class, 'index'], $can('customers'));
$router->get('/admin/khach-hang/{phone}',  [Admin\CustomerController::class, 'show'], $can('customers'));
$router->get('/admin/khuyen-mai',          [Admin\CouponController::class, 'index'], $can('promotions'));
$router->get('/admin/khuyen-mai/them',     [Admin\CouponController::class, 'create'], $can('promotions'));   // before {id}
$router->get('/admin/khuyen-mai/{id}',     [Admin\CouponController::class, 'edit'], $can('promotions'));
$router->get('/admin/danh-gia',            [Admin\ReviewController::class, 'index'], $can('reviews'));
$router->get('/admin/san-pham',            [Admin\ProductController::class, 'index'], $can('products'));
$router->get('/admin/san-pham/them',       [Admin\ProductController::class, 'create'], $can('products'));
$router->get('/admin/san-pham/{id}',       [Admin\ProductController::class, 'edit'], $can('products'));
$router->get('/admin/kho',                 [Admin\InventoryController::class, 'index'], $can('inventory'));
$router->get('/admin/danh-muc',            [Admin\TaxonomyController::class, 'index'], $can('catalog'));
$router->get('/admin/cai-dat',             [Admin\SettingsController::class, 'index'], $can('settings'));
$router->get('/admin/nhan-vien',           [Admin\StaffController::class, 'index'], $can('staff'));
$router->get('/admin/nhat-ky',             [Admin\StaffController::class, 'activity'], $can('activity'));
$router->get('/admin/tai-khoan',           [Admin\StaffController::class, 'account'], $staff);

// Ajax — dashboard & notifications
$router->get('/admin/api/dashboard',       [Admin\DashboardController::class, 'body'], $can('dashboard'));
$router->get('/admin/api/notifications',   [Admin\OrderController::class, 'notifications'], $can('orders.view'));

// Ajax — orders & customers
$router->get('/admin/api/orders',                   [Admin\OrderController::class, 'rows'], $can('orders.view'));
$router->post('/admin/api/orders/bulk',             [Admin\OrderController::class, 'bulk'], $can('orders.manage'));
$router->patch('/admin/api/orders/{code}',          [Admin\OrderController::class, 'update'], $can('orders.manage'));
$router->post('/admin/api/orders/{code}/notes',     [Admin\OrderController::class, 'addNote'], $can('orders.manage'));
$router->patch('/admin/api/orders/{code}/customer', [Admin\OrderController::class, 'updateCustomer'], $can('orders.manage'));
$router->get('/admin/api/customers',                [Admin\CustomerController::class, 'rows'], $can('customers'));

// Ajax — products, images, variants, stock
$router->get('/admin/api/products',                     [Admin\ProductController::class, 'rows'], $can('products'));
$router->post('/admin/api/products',                    [Admin\ProductController::class, 'store'], $can('products'));
$router->post('/admin/api/products/bulk',               [Admin\ProductController::class, 'bulk'], $can('products'));    // before {id}
$router->put('/admin/api/products/{id}',                [Admin\ProductController::class, 'update'], $can('products'));
$router->delete('/admin/api/products/{id}',             [Admin\ProductController::class, 'destroy'], $can('products'));
$router->patch('/admin/api/products/{id}/toggle',       [Admin\ProductController::class, 'toggle'], $can('products'));
$router->post('/admin/api/products/{id}/duplicate',     [Admin\ProductController::class, 'duplicate'], $can('products'));
$router->post('/admin/api/products/{id}/variants',      [Admin\ProductController::class, 'generateVariants'], $can('products'));
$router->post('/admin/api/products/{id}/images',        [Admin\ProductController::class, 'uploadImages'], $can('products'));
$router->patch('/admin/api/products/{id}/images/order', [Admin\ProductController::class, 'reorderImages'], $can('products'));
$router->patch('/admin/api/images/{id}',                [Admin\ProductController::class, 'setImageType'], $can('products'));
$router->delete('/admin/api/images/{id}',               [Admin\ProductController::class, 'deleteImage'], $can('products'));
$router->patch('/admin/api/variants/{id}',              [Admin\ProductController::class, 'updateVariant'], $can('inventory'));
$router->delete('/admin/api/variants/{id}',             [Admin\ProductController::class, 'deleteVariant'], $can('products'));
$router->get('/admin/api/inventory',                    [Admin\InventoryController::class, 'rows'], $can('inventory'));
$router->post('/admin/api/inventory/{id}',              [Admin\InventoryController::class, 'adjust'], $can('inventory'));

// Ajax — coupons
$router->get('/admin/api/coupons',          [Admin\CouponController::class, 'rows'], $can('promotions'));
$router->get('/admin/api/coupons/generate', [Admin\CouponController::class, 'generate'], $can('promotions'));
$router->post('/admin/api/coupons',         [Admin\CouponController::class, 'save'], $can('promotions'));
$router->put('/admin/api/coupons/{id}',     [Admin\CouponController::class, 'save'], $can('promotions'));
$router->delete('/admin/api/coupons/{id}',  [Admin\CouponController::class, 'delete'], $can('promotions'));

// Ajax — reviews
$router->get('/admin/api/reviews',             [Admin\ReviewController::class, 'rows'], $can('reviews'));
$router->post('/admin/api/reviews/bulk',       [Admin\ReviewController::class, 'bulk'], $can('reviews'));   // before {id}
$router->patch('/admin/api/reviews/{id}',      [Admin\ReviewController::class, 'status'], $can('reviews'));
$router->put('/admin/api/reviews/{id}/reply',  [Admin\ReviewController::class, 'reply'], $can('reviews'));
$router->delete('/admin/api/reviews/{id}',     [Admin\ReviewController::class, 'delete'], $can('reviews'));

// Ajax — catalog, settings, staff, activity, own account
$router->post('/admin/api/taxonomy/{type}',        [Admin\TaxonomyController::class, 'save'], $can('catalog'));
$router->put('/admin/api/taxonomy/{type}/{id}',    [Admin\TaxonomyController::class, 'save'], $can('catalog'));
$router->delete('/admin/api/taxonomy/{type}/{id}', [Admin\TaxonomyController::class, 'delete'], $can('catalog'));
$router->put('/admin/api/settings/{group}',        [Admin\SettingsController::class, 'save'], $can('settings'));
$router->post('/admin/api/staff',                  [Admin\StaffController::class, 'store'], $can('staff'));
$router->put('/admin/api/staff/{id}',              [Admin\StaffController::class, 'update'], $can('staff'));
$router->post('/admin/api/staff/{id}/password',    [Admin\StaffController::class, 'resetPassword'], $can('staff'));
$router->get('/admin/api/activity',                [Admin\StaffController::class, 'activityRows'], $can('activity'));
$router->post('/admin/api/account/password',       [Admin\StaffController::class, 'changePassword'], $staff);

$request = Request::capture();
try {
    // Every state-changing request must carry the session's CSRF token
    if (!in_array($request->method, ['GET', 'HEAD', 'OPTIONS'], true)) Csrf::verify();

    $router->dispatch($request);
} catch (Throwable $e) {
    $status  = $e instanceof HttpException ? $e->status : 500;
    $message = $e instanceof HttpException ? $e->getMessage() : t('errors.server');
    if ($request->isAjax()) {
        $payload = ['error' => $message];
        if ($e instanceof HttpException && $e->details) $payload['details'] = $e->details;
        if ($status === 500 && Config::get('debug')) $payload['debug'] = $e->getMessage();
        Response::json($payload, $status);
    }
    // Errors inside the admin area (e.g. 403 for a role without access) keep the admin chrome
    if (str_starts_with($request->path, '/admin') && Auth::user()) {
        View::page('admin/error', ['title' => $message, 'status' => $status, 'message' => $message,
            'debug' => $status === 500 && Config::get('debug') ? $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() : null],
            $status, 'admin/layout');
    }
    View::page($status === 404 ? 'pages/404' : 'pages/error', [
        'title' => $message,
        'message' => $message,
        'debug' => $status === 500 && Config::get('debug') ? $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() : null,
    ], $status);
}
