<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\AppController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\LoyaltyController;
use App\Http\Controllers\Api\V1\MerchantController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ScanController;
use Illuminate\Support\Facades\Route;

/*
| Mobile API — every route here is served under /api/v1 (see bootstrap/app.php).
| Reference for app developers: docs/API.md
*/

// Sign-in: the customer messages our WhatsApp first, then receives an OTP
Route::prefix('auth/whatsapp')->group(function () {
    Route::post('start', [AuthController::class, 'start'])->middleware('throttle:otp-start');
    Route::post('check', [AuthController::class, 'check'])->middleware('throttle:otp-poll');
    Route::post('verify', [AuthController::class, 'verify'])->middleware('throttle:otp-verify');
});

// Public catalog
Route::prefix('store')->group(function () {
    Route::get('banners', [CatalogController::class, 'banners']);
    Route::get('categories', [CatalogController::class, 'categories']);
    Route::get('products', [CatalogController::class, 'products']);
    Route::get('products/{id}', [CatalogController::class, 'product'])->whereNumber('id');
});
Route::get('settings', [CatalogController::class, 'settings']);

// App bootstrap and static content
Route::get('app/config', [AppController::class, 'config']);
Route::get('content/faq', [AppController::class, 'faq']);
Route::get('content/pages/{slug}', [AppController::class, 'page']);
Route::get('ranks', [LoyaltyController::class, 'ranks']);

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);

    Route::get('user/profile', [AuthController::class, 'profile']);
    Route::put('user/profile', [AuthController::class, 'updateProfile']);
    Route::delete('user/account', [AccountController::class, 'destroy'])->middleware('throttle:5,1');
    Route::post('user/phone/start', [AccountController::class, 'startPhoneChange'])->middleware('throttle:otp-start');
    Route::post('user/phone/verify', [AccountController::class, 'verifyPhoneChange'])->middleware('throttle:otp-verify');

    // QR scanning
    Route::prefix('qr')->middleware('throttle:60,1')->group(function () {
        Route::post('preview', [ScanController::class, 'preview']);
        Route::post('scan', [ScanController::class, 'scan']);
        Route::post('sync-batch', [ScanController::class, 'syncBatch'])->middleware('throttle:10,1');
    });
    Route::get('merchants/my-recent', [ScanController::class, 'recentMerchants']);

    // Loyalty wallet and lucky wheel
    Route::get('wallet', [LoyaltyController::class, 'wallet']);
    Route::get('wallet/transactions', [LoyaltyController::class, 'transactions']);
    Route::get('customer/rewards', [LoyaltyController::class, 'rewards']);
    Route::get('wheel/config', [LoyaltyController::class, 'wheelConfig']);
    Route::post('wheel/spin', [LoyaltyController::class, 'spin'])->middleware('throttle:20,1');

    // Cart and orders
    Route::prefix('store')->group(function () {
        Route::get('cart', [CartController::class, 'show']);
        Route::post('cart/add', [CartController::class, 'add']);
        Route::post('cart/update', [CartController::class, 'update']);
        Route::delete('cart/remove/{itemId}', [CartController::class, 'remove'])->whereNumber('itemId');
        Route::post('cart/coupon', [CartController::class, 'applyCoupon'])->middleware('throttle:20,1');
        Route::delete('cart/coupon', [CartController::class, 'removeCoupon']);
        Route::post('checkout', [OrderController::class, 'checkout'])->middleware('throttle:10,1');
    });
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{orderNumber}', [OrderController::class, 'show']);
    Route::post('orders/{orderNumber}/cancel', [OrderController::class, 'cancel'])->middleware('throttle:10,1');

    // Address book
    Route::get('addresses', [AddressController::class, 'index']);
    Route::post('addresses', [AddressController::class, 'store']);
    Route::put('addresses/{id}', [AddressController::class, 'update'])->whereNumber('id');
    Route::delete('addresses/{id}', [AddressController::class, 'destroy'])->whereNumber('id');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->whereNumber('id');
    Route::post('notifications/device-token', [AuthController::class, 'deviceToken']);

    // Merchant profile
    Route::post('merchant/apply', [MerchantController::class, 'apply'])->middleware('throttle:5,1');
    Route::post('merchant/logo', [MerchantController::class, 'logo'])->middleware('throttle:10,1');
    Route::get('merchant', [MerchantController::class, 'show']);

    // Support assistant
    Route::post('chat', [AppController::class, 'chat'])->middleware('throttle:10,1');
});
