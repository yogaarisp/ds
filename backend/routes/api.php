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
use Illuminate\Support\Facades\Route;

// Auth routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Public Settings
Route::get('/settings', [SettingController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Products
    Route::apiResource('products', ProductController::class);
    Route::patch('/products/{product}/stock', [ProductController::class, 'updateStock']);

    // Customers
    Route::apiResource('customers', CustomerController::class);
    Route::get('/customers-search', [CustomerController::class, 'search']);
    Route::get('/customers/{customer}/stats', [CustomerController::class, 'stats']);
    Route::apiResource('customer-types', \App\Http\Controllers\Api\CustomerTypeController::class);

    // Packages & Combinations
    Route::apiResource('packages', PackageController::class);
    Route::post('/packages/{package}/combinations', [PackageController::class, 'storeCombination']);
    Route::put('/combinations/{combination}', [PackageController::class, 'updateCombination']);
    Route::delete('/combinations/{combination}', [PackageController::class, 'destroyCombination']);
    Route::put('/packages/{package}/discounts', [PackageController::class, 'updateDiscounts']);



    // Transactions
    Route::get('/transactions/pending-by-customer', [TransactionController::class, 'getPendingByCustomer']);
    Route::apiResource('transactions', TransactionController::class)->only(['index', 'store', 'show', 'update']);
    Route::apiResource('payment-methods', PaymentMethodController::class);
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
});
