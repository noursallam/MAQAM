<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\LocaleController;
use App\Http\Controllers\Admin\LoyaltyController;
use App\Http\Controllers\Admin\MerchantController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PrizeCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\QrCodeController;
use App\Http\Controllers\Admin\RankController;
use App\Http\Controllers\Admin\RiskController;
use App\Http\Controllers\Admin\ScanController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    });

    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
    Route::post('locale', [LocaleController::class, 'switch'])->name('locale');

    Route::middleware(['auth', EnsureUserIsAdmin::class])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('search', [DashboardController::class, 'search'])->name('search');

        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::post('customers/{customer}/adjust-points', [CustomerController::class, 'adjustPoints'])->name('customers.adjust-points');
        Route::post('customers/{customer}/freeze', [CustomerController::class, 'freeze'])->name('customers.freeze');
        Route::post('customers/{customer}/unfreeze', [CustomerController::class, 'unfreeze'])->name('customers.unfreeze');

        Route::get('merchants/inbox', [MerchantController::class, 'inbox'])->name('merchants.inbox');
        Route::resource('merchants', MerchantController::class)->except(['show']);
        Route::post('merchants/{merchant}/approve', [MerchantController::class, 'approve'])->name('merchants.approve');
        Route::post('merchants/{merchant}/reject', [MerchantController::class, 'reject'])->name('merchants.reject');

        Route::resource('ranks', RankController::class)->except(['show']);
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('prize-categories', PrizeCategoryController::class)->except(['show']);
        Route::resource('products', ProductController::class)->except(['show']);

        Route::get('qr-codes', [QrCodeController::class, 'index'])->name('qr-codes.index');
        Route::get('qr-codes/generate', [QrCodeController::class, 'create'])->name('qr-codes.create');
        Route::post('qr-codes/generate', [QrCodeController::class, 'store'])->name('qr-codes.store');
        Route::get('qr-codes/download/{batchId}', [QrCodeController::class, 'download'])->name('qr-codes.download');
        Route::get('qr-codes/download-json/{batchId}', [QrCodeController::class, 'downloadJson'])->name('qr-codes.download-json');
        Route::post('qr-codes/restore', [QrCodeController::class, 'restore'])->name('qr-codes.restore');
        Route::delete('qr-codes/{qr_code}', [QrCodeController::class, 'destroy'])->name('qr-codes.destroy');

        Route::get('scans', [ScanController::class, 'index'])->name('scans.index');

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');

        Route::resource('coupons', CouponController::class)->except(['show']);
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/create', [NotificationController::class, 'create'])->name('notifications.create');
        Route::post('notifications', [NotificationController::class, 'store'])->name('notifications.store');

        Route::get('loyalty/transactions', [LoyaltyController::class, 'transactions'])->name('loyalty.transactions');
        Route::get('loyalty/spins', [LoyaltyController::class, 'spins'])->name('loyalty.spins');
        Route::post('loyalty/wheel-toggle', [LoyaltyController::class, 'toggleWheel'])->name('loyalty.wheel-toggle');
        Route::post('loyalty/wheel-simulate', [LoyaltyController::class, 'simulateSpin'])->name('loyalty.wheel-simulate');
        Route::post('loyalty/wheel-prizes', [LoyaltyController::class, 'storePrize'])->name('loyalty.wheel-prizes.store');
        Route::put('loyalty/wheel-prizes/{prize}', [LoyaltyController::class, 'updatePrize'])->name('loyalty.wheel-prizes.update');
        Route::delete('loyalty/wheel-prizes/{prize}', [LoyaltyController::class, 'destroyPrize'])->name('loyalty.wheel-prizes.destroy');
        Route::post('loyalty/wheel-prizes/{prize}/toggle', [LoyaltyController::class, 'togglePrize'])->name('loyalty.wheel-prizes.toggle');

        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('inventory/{product}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');

        Route::get('risk', [RiskController::class, 'index'])->name('risk.index');
        Route::post('risk/{user}/freeze', [RiskController::class, 'freeze'])->name('risk.freeze');
        Route::post('risk/{user}/unfreeze', [RiskController::class, 'unfreeze'])->name('risk.unfreeze');

        Route::get('admins', [AdminUserController::class, 'index'])->name('admins.index');

        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
