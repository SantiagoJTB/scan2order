<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SecurityOverviewController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OrderItemController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public test endpoint
Route::get('/hello', function () {
    return response()->json(['message' => 'Hello from Laravel']);
});

// Auth routes (public)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth-login');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:auth-forgot-password');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:auth-reset-password');
Route::post('/webhooks/stripe', [PaymentController::class, 'handleStripeWebhook']);

// Protected auth routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/mfa/setup', [AuthController::class, 'mfaSetup'])->middleware('throttle:auth-mfa-setup');
    Route::post('/mfa/enable', [AuthController::class, 'mfaEnable'])->middleware('throttle:auth-mfa-verify');
    Route::post('/mfa/disable', [AuthController::class, 'mfaDisable'])->middleware('throttle:auth-mfa-verify');
});

// User management (protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/limits', [UserController::class, 'getCreationLimits']);
    Route::patch('/users/limits/system', [UserController::class, 'updateSystemLimits']);
    Route::patch('/users/{user}/staff-limit', [UserController::class, 'updateAdminStaffLimit']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::patch('/users/{user}', [UserController::class, 'update']);
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus']);
    Route::patch('/users/{user}/password', [UserController::class, 'updatePassword']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});

// Public API endpoints (for viewing menu, etc)
Route::get('/restaurants', [RestaurantController::class, 'index']);
Route::get('/restaurants/{restaurant}', [RestaurantController::class, 'show'])->whereNumber('restaurant');
Route::get('/restaurants/{restaurantId}/catalogs', [ProductController::class, 'getCatalogsByRestaurant']);

// Protected resource routes (admin only)
Route::middleware('auth:sanctum')->group(function () {
    // Restaurant management
    Route::get('/restaurants/stats', [ProductController::class, 'getRestaurantsStats']);
    Route::post('/restaurants', [RestaurantController::class, 'store']);
    Route::put('/restaurants/{restaurant}', [RestaurantController::class, 'update']);
    Route::put('/restaurants/{restaurant}/admins', [RestaurantController::class, 'syncAdmins']);
    Route::put('/restaurants/{restaurant}/staffs', [RestaurantController::class, 'syncStaffs']);
    Route::delete('/restaurants/{restaurant}', [RestaurantController::class, 'destroy']);

    // Catalog management
    Route::post('/restaurants/{restaurantId}/catalogs', [ProductController::class, 'storeCatalog']);
    Route::put('/restaurants/{restaurantId}/catalogs/{catalogId}', [ProductController::class, 'updateCatalog']);
    Route::delete('/restaurants/{restaurantId}/catalogs/{catalogId}', [ProductController::class, 'deleteCatalog']);

    // Section management
    Route::post('/restaurants/{restaurantId}/catalogs/{catalogId}/sections', [ProductController::class, 'storeSection']);
    Route::put('/restaurants/{restaurantId}/catalogs/{catalogId}/sections/{sectionId}', [ProductController::class, 'updateSection']);
    Route::delete('/restaurants/{restaurantId}/catalogs/{catalogId}/sections/{sectionId}', [ProductController::class, 'deleteSection']);

    // Product management
    Route::post('/restaurants/{restaurantId}/catalogs/{catalogId}/sections/{sectionId}/products', [ProductController::class, 'storeProduct']);
    Route::put('/restaurants/{restaurantId}/catalogs/{catalogId}/sections/{sectionId}/products/{productId}', [ProductController::class, 'updateProduct']);
    Route::delete('/restaurants/{restaurantId}/catalogs/{catalogId}/sections/{sectionId}/products/{productId}', [ProductController::class, 'deleteProduct']);

    // Category management
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    // Table management
    Route::apiResources([
        'tables' => TableController::class,
    ]);

    // Order management
    Route::apiResources([
        'orders' => OrderController::class,
        'order-items' => OrderItemController::class,
    ]);

    Route::post('/orders/{orderId}/payments/stripe', [PaymentController::class, 'createStripePaymentIntent']);
    Route::post('/orders/{orderId}/payments/cash', [PaymentController::class, 'createCashPayment']);
    Route::post('/orders/{orderId}/payments/test', [PaymentController::class, 'createTestPayment']);
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);

    Route::get('/admin/security/overview', [SecurityOverviewController::class, 'index']);
    Route::post('/admin/security/emergency-action', [SecurityOverviewController::class, 'emergencyAction'])->middleware('throttle:15,1');
    Route::get('/admin/security/health', [SecurityOverviewController::class, 'health']);
    Route::get('/admin/security/guardian/status', [SecurityOverviewController::class, 'guardianStatus']);
    Route::post('/admin/security/guardian/action', [SecurityOverviewController::class, 'guardianAction'])->middleware('throttle:15,1');
});
