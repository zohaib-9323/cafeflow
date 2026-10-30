<?php

use App\Http\Controllers\Account;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Site;
use App\Http\Controllers\Payments;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------- public site
Route::get('/', Site\HomeController::class)->name('home');
Route::get('/menu', [Site\MenuController::class, 'index'])->name('menu.index');
Route::get('/menu/{product:slug}', [Site\MenuController::class, 'show'])->name('menu.show');
Route::get('/faq', Site\FaqController::class)->name('faq');
Route::get('/contact', [Site\ContactController::class, 'show'])->name('contact');
Route::post('/contact', [Site\ContactController::class, 'send'])->middleware('throttle:5,10')->name('contact.send');
Route::get('/pages/{slug}', Site\PageController::class)->name('pages.show');
foreach (['about', 'privacy-policy', 'terms-and-conditions'] as $slug) {
    Route::get("/{$slug}", Site\PageController::class)->defaults('slug', $slug)->name("pages.{$slug}");
}

Route::prefix('cart')->name('cart.')->middleware('throttle:cart')->group(function () {
    Route::get('/', [Site\CartController::class, 'index'])->name('index');
    Route::get('/summary', [Site\CartController::class, 'summary'])->name('summary');
    Route::post('/items', [Site\CartController::class, 'store'])->name('store');
    Route::patch('/items/{key}', [Site\CartController::class, 'update'])->name('update');
    Route::delete('/items/{key}', [Site\CartController::class, 'destroy'])->name('destroy');
    Route::post('/promo', [Site\CartController::class, 'promo'])->name('promo');
    Route::post('/fulfillment', [Site\CartController::class, 'fulfillment'])->name('fulfillment');
});

// ---------------------------------------------------------------- auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [Auth\LoginController::class, 'show'])->name('login');
    Route::post('/login', [Auth\LoginController::class, 'store'])->middleware('throttle:auth');
    Route::get('/register', [Auth\RegisterController::class, 'show'])->name('register');
    Route::post('/register', [Auth\RegisterController::class, 'store'])->middleware('throttle:auth');
    Route::get('/forgot-password', [Auth\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [Auth\PasswordResetController::class, 'email'])->middleware('throttle:auth')->name('password.email');
    Route::get('/reset-password/{token}', [Auth\PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [Auth\PasswordResetController::class, 'update'])->middleware('throttle:auth')->name('password.update');
});
Route::post('/logout', [Auth\LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// ---------------------------------------------------------------- customers
Route::middleware(['auth', 'customer'])->group(function () {
    Route::get('/checkout', [Site\CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [Site\CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');
    Route::post('/menu/{product:slug}/reviews', [Site\ReviewController::class, 'store'])->middleware('throttle:10,1')->name('reviews.store');

    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/', [Account\ProfileController::class, 'edit'])->name('profile');
        Route::put('/', [Account\ProfileController::class, 'update'])->name('profile.update');
        Route::put('/password', [Account\ProfileController::class, 'password'])->name('password');
        Route::post('/addresses', [Account\AddressController::class, 'store'])->name('addresses.store');
        Route::delete('/addresses/{address}', [Account\AddressController::class, 'destroy'])->name('addresses.destroy');
        Route::get('/orders', [Account\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [Account\OrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{order}/status', [Account\OrderController::class, 'status'])->name('orders.status');
        Route::post('/orders/{order}/cancel', [Account\OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{order}/pay', [Account\OrderController::class, 'pay'])->middleware('throttle:checkout')->name('orders.pay');
    });

    Route::get('/payments/{payment}/return', [Payments\ReturnController::class, '__invoke'])->name('payments.return');
    // Development test gateway (signed links, see App\Services\Payments\MockGateway).
    Route::get('/payments/mock/{payment}', [Payments\MockCheckoutController::class, 'show'])->middleware('signed')->name('payments.mock.show');
    Route::post('/payments/mock/{payment}', [Payments\MockCheckoutController::class, 'complete'])->middleware('signed')->name('payments.mock.complete');
});

Route::post('/webhooks/payments/{provider}', Payments\WebhookController::class)
    ->whereIn('provider', ['mock', 'stripe'])->middleware('throttle:webhooks')->name('webhooks.payments');

Route::view('/docs/api', 'docs.api')->name('docs.api');
Route::get('/docs/openapi.yaml', fn () => response()->file(base_path('docs/openapi.yaml'), ['Content-Type' => 'application/yaml']))->name('docs.openapi');

// ---------------------------------------------------------------- admin
Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:access-admin'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('/notifications', [Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [Admin\NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications/{id}', [Admin\NotificationController::class, 'open'])->name('notifications.open');

    Route::middleware('can:view-orders')->group(function () {
        Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{order}/receipt', [Admin\OrderController::class, 'receipt'])->name('orders.receipt');
        Route::patch('/orders/{order}/status', [Admin\OrderController::class, 'updateStatus'])->name('orders.status');
        Route::post('/orders/{order}/claim', [Admin\OrderController::class, 'claim'])->name('orders.claim');
        Route::post('/orders/{order}/assign', [Admin\OrderController::class, 'assign'])->name('orders.assign');
    });

    Route::middleware('can:view-catalog')->group(function () {
        Route::get('/products', [Admin\ProductController::class, 'index'])->name('products.index');
    });
    Route::middleware('can:manage-catalog')->group(function () {
        Route::resource('products', Admin\ProductController::class)->except(['index', 'show']);
        Route::post('/products/{product}/restore', [Admin\ProductController::class, 'restore'])->withTrashed()->name('products.restore');
        Route::post('/products/{product}/images', [Admin\ProductImageController::class, 'store'])->name('products.images.store');
        Route::delete('/products/{product}/images/{image}', [Admin\ProductImageController::class, 'destroy'])->scopeBindings()->name('products.images.destroy');
        Route::resource('categories', Admin\CategoryController::class)->except(['show']);
    });

    Route::middleware('can:view-inventory')->group(function () {
        Route::get('/inventory', [Admin\InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/{item}', [Admin\InventoryController::class, 'show'])->whereNumber('item')->name('inventory.show');
        Route::post('/inventory/{item}/adjust', [Admin\InventoryController::class, 'adjust'])->name('inventory.adjust');
    });
    Route::middleware('can:manage-inventory')->group(function () {
        Route::get('/inventory/create', [Admin\InventoryController::class, 'create'])->name('inventory.create');
        Route::post('/inventory', [Admin\InventoryController::class, 'store'])->name('inventory.store');
        Route::get('/inventory/{item}/edit', [Admin\InventoryController::class, 'edit'])->name('inventory.edit');
        Route::put('/inventory/{item}', [Admin\InventoryController::class, 'update'])->name('inventory.update');
        Route::resource('suppliers', Admin\SupplierController::class)->except(['show']);
    });

    Route::middleware('can:manage-customers')->group(function () {
        Route::get('/customers', [Admin\CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{user}', [Admin\CustomerController::class, 'show'])->name('customers.show');
        Route::patch('/customers/{user}/status', [Admin\CustomerController::class, 'toggle'])->name('customers.toggle');
    });

    Route::middleware('can:manage-promotions')->group(function () {
        Route::resource('promotions', Admin\PromotionController::class)->except(['show']);
    });

    Route::middleware('can:manage-reviews')->group(function () {
        Route::get('/reviews', [Admin\ReviewController::class, 'index'])->name('reviews.index');
        Route::patch('/reviews/{review}', [Admin\ReviewController::class, 'update'])->name('reviews.update');
        Route::delete('/reviews/{review}', [Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');
    });

    Route::prefix('cms')->name('cms.')->middleware('can:manage-content')->group(function () {
        Route::get('/homepage', [Admin\Cms\HomepageController::class, 'edit'])->name('homepage.edit');
        Route::put('/homepage', [Admin\Cms\HomepageController::class, 'update'])->name('homepage.update');
        Route::resource('pages', Admin\Cms\PageController::class)->except(['show']);
        Route::resource('banners', Admin\Cms\BannerController::class)->except(['show']);
        Route::resource('faqs', Admin\Cms\FaqController::class)->except(['show']);
        Route::post('/faqs/reorder', [Admin\Cms\FaqController::class, 'reorder'])->name('faqs.reorder');
        Route::resource('testimonials', Admin\Cms\TestimonialController::class)->except(['show']);
        Route::get('/media', [Admin\Cms\MediaController::class, 'index'])->name('media.index');
        Route::post('/media', [Admin\Cms\MediaController::class, 'store'])->name('media.store');
        Route::delete('/media/{media}', [Admin\Cms\MediaController::class, 'destroy'])->name('media.destroy');
    });

    Route::get('/analytics', Admin\AnalyticsController::class)->middleware('can:view-analytics')->name('analytics');

    Route::middleware('can:manage-team')->group(function () {
        Route::resource('team', Admin\TeamController::class)->parameters(['team' => 'user'])->except(['show']);
    });
    Route::middleware('can:manage-settings')->group(function () {
        Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
    });
});
