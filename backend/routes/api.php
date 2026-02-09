<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\PriceCategoryController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\PackagingRuleController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\CarouselSlideController;
use App\Http\Controllers\Api\DatabaseController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Public Settings
Route::get('/settings', [SettingController::class, 'index']);

// Public Carousel Slides (untuk customer)
Route::get('/carousel-slides/active', [CarouselSlideController::class, 'active']);

// Public Menu Routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/packages/popular', [PackageController::class, 'popular']);
Route::get('/packages', [PackageController::class, 'index']);
Route::get('/packages/{package}', [PackageController::class, 'show']);
Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
Route::post('/transactions', [TransactionController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Products
    Route::apiResource('products', ProductController::class)->except(['index', 'show']);
    Route::patch('/products/{product}/stock', [ProductController::class, 'updateStock']);

    // Customers
    Route::apiResource('customers', CustomerController::class);
    Route::get('/customers-search', [CustomerController::class, 'search']);
    Route::get('/customers/{customer}/stats', [CustomerController::class, 'stats']);
    Route::post('/customers/change-password', [CustomerController::class, 'changePassword']);
    Route::apiResource('customer-types', \App\Http\Controllers\Api\CustomerTypeController::class);

    // Customer Addresses
    Route::apiResource('customer-addresses', \App\Http\Controllers\Api\CustomerAddressController::class);
    Route::post('/customer-addresses/{address}/set-default', [\App\Http\Controllers\Api\CustomerAddressController::class, 'setDefault']);

    // Packages & Combinations
    Route::apiResource('packages', PackageController::class)->except(['index', 'show']);
    Route::post('/packages/{package}/combinations', [PackageController::class, 'storeCombination']);
    Route::put('/combinations/{combination}', [PackageController::class, 'updateCombination']);
    Route::delete('/combinations/{combination}', [PackageController::class, 'destroyCombination']);
    Route::put('/packages/{package}/discounts', [PackageController::class, 'updateDiscounts']);



    // Transactions
    Route::get('/transactions/pending-by-customer', [TransactionController::class, 'getPendingByCustomer']);
    Route::apiResource('transactions', TransactionController::class)->only(['index', 'update', 'destroy']);
    Route::apiResource('payment-methods', PaymentMethodController::class)->except(['index']);
    Route::post('/transactions/{transaction}/complete', [TransactionController::class, 'complete']);
    Route::post('/transactions/{transaction}/cancel', [TransactionController::class, 'cancel']);
    Route::post('/transactions/{transaction}/pay-remaining', [TransactionController::class, 'payRemaining']);
    Route::get('/transactions-summary/today', [TransactionController::class, 'todaySummary']);
    Route::get('/transactions-summary/upcoming', [TransactionController::class, 'upcomingOrders']);

    // Inventory
    Route::apiResource('inventory', InventoryController::class);
    Route::post('/inventory/{inventory}/add-stock', [InventoryController::class, 'addStock']);
    Route::get('/inventory/{inventory}/transactions', [InventoryController::class, 'transactions']);
    Route::get('/inventory-warning/low-stock', [InventoryController::class, 'lowStockWarning']);

    // Recipes
    Route::get('/products/{product}/recipes', [RecipeController::class, 'index']);
    Route::post('/products/{product}/recipes', [RecipeController::class, 'store']);
    Route::post('/products/{product}/recipes/bulk', [RecipeController::class, 'bulkStore']);
    Route::put('/recipes/{recipe}', [RecipeController::class, 'update']);
    Route::delete('/recipes/{recipe}', [RecipeController::class, 'destroy']);

    // Packaging Rules
    Route::apiResource('packaging-rules', PackagingRuleController::class);

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/daily-sales', [ReportController::class, 'dailySales']);
        Route::get('/sales-by-product', [ReportController::class, 'salesByProduct']);
        Route::get('/sales-by-package', [ReportController::class, 'salesByPackage']);
        Route::get('/sales-by-category', [ReportController::class, 'salesByCategory']);
        Route::get('/product-stock', [ReportController::class, 'productStock']);
        Route::get('/inventory-stock', [ReportController::class, 'inventoryStock']);
        Route::get('/inventory-history', [ReportController::class, 'inventoryHistory']);
        Route::get('/product-history', [ReportController::class, 'productHistory']);
        Route::get('/monthly-summary', [ReportController::class, 'monthlySummary']);
    });

    // Users (Owner only)
    Route::apiResource('users', UserController::class);

    // Settings (Owner only) - GET is public now
    Route::put('/settings', [SettingController::class, 'update']);
    Route::post('/settings/logo', [SettingController::class, 'uploadLogo']);
    Route::post('/settings/favicon', [SettingController::class, 'uploadFavicon']);
    Route::delete('/settings/logo', [SettingController::class, 'deleteLogo']);

    // Tables (Meja Resto)
    Route::apiResource('tables', \App\Http\Controllers\Api\TableController::class);
    Route::get('/tables/{table}/qr', [\App\Http\Controllers\Api\TableController::class, 'generateQr']);

    // Carousel Slides (Admin)
    Route::apiResource('carousel-slides', CarouselSlideController::class);
    Route::post('/carousel-slides/reorder', [CarouselSlideController::class, 'reorder']);
    Route::post('/carousel-slides/{carouselSlide}/toggle-active', [CarouselSlideController::class, 'toggleActive']);

    // Database Management (Owner only)
    Route::get('/database/info', [DatabaseController::class, 'info']);
    Route::get('/database/export', [DatabaseController::class, 'export']);
    Route::post('/database/import', [DatabaseController::class, 'import']);
});

// Public route untuk customer scan QR
Route::get('/tables/code/{code}', [\App\Http\Controllers\Api\TableController::class, 'findByCode']);

// Guest orders by phone
Route::get('/transactions/guest/by-phone', [TransactionController::class, 'getGuestOrders']);
