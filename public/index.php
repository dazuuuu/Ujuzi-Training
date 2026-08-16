<?php
/**
 * Pentagon Collections — single front controller (see .htaccess: every
 * request that isn't a real file/directory is routed through this file).
 * Application code lives outside the web root, in /app/collections_apps.
 */

require dirname(__DIR__) . '/app/collections_apps/app/bootstrap.php';

use App\Core\Router;
use App\Controllers\SetupController;
use App\Controllers\StorefrontController;
use App\Controllers\Api\OrderController as ApiOrderController;
use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\ProductController;
use App\Controllers\Admin\CategoryController;
use App\Controllers\Admin\OfferController;
use App\Controllers\Admin\OrderController as AdminOrderController;
use App\Controllers\Admin\SeoController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\UpdateController;
use App\Controllers\Account\AuthController as AccountAuthController;
use App\Controllers\Account\OrderController as AccountOrderController;

$router = new Router();

// --- First-run setup ---
$router->get('/setup', [SetupController::class, 'index']);
$router->post('/setup', [SetupController::class, 'store']);

// --- Storefront ---
$router->get('/', [StorefrontController::class, 'home']);
$router->get('/product/{code}', [StorefrontController::class, 'product']);
$router->get('/category/{key}', [StorefrontController::class, 'category']);
$router->get('/track-order', [StorefrontController::class, 'trackOrder']);
$router->post('/track-order', [StorefrontController::class, 'trackOrder']);

// --- API ---
$router->post('/api/place-order', [ApiOrderController::class, 'store']);

// --- Admin: auth ---
$router->get('/admin/login', [AdminAuthController::class, 'showLogin']);
$router->post('/admin/login', [AdminAuthController::class, 'login']);
$router->get('/admin/logout', [AdminAuthController::class, 'logout']);

// --- Admin: dashboard ---
$router->get('/admin', [DashboardController::class, 'index']);

// --- Admin: products ---
$router->get('/admin/products', [ProductController::class, 'index']);
$router->get('/admin/products/create', [ProductController::class, 'create']);
$router->post('/admin/products', [ProductController::class, 'store']);
$router->get('/admin/products/{id}/edit', [ProductController::class, 'edit']);
$router->post('/admin/products/{id}', [ProductController::class, 'update']);
$router->post('/admin/products/{id}/delete', [ProductController::class, 'destroy']);

// --- Admin: categories ---
$router->get('/admin/categories', [CategoryController::class, 'index']);
$router->get('/admin/categories/create', [CategoryController::class, 'create']);
$router->post('/admin/categories', [CategoryController::class, 'store']);
$router->get('/admin/categories/{id}/edit', [CategoryController::class, 'edit']);
$router->post('/admin/categories/{id}', [CategoryController::class, 'update']);
$router->post('/admin/categories/{id}/delete-image', [CategoryController::class, 'deleteImage']);

// --- Admin: offers ---
$router->get('/admin/offers', [OfferController::class, 'index']);
$router->post('/admin/offers/{id}/update', [OfferController::class, 'update']);
$router->post('/admin/offers/{id}/remove', [OfferController::class, 'remove']);
$router->post('/admin/offers/{id}/add', [OfferController::class, 'add']);

// --- Admin: orders ---
$router->get('/admin/orders', [AdminOrderController::class, 'index']);
$router->get('/admin/orders/{ref}', [AdminOrderController::class, 'show']);
$router->post('/admin/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);

// --- Admin: SEO ---
$router->get('/admin/seo', [SeoController::class, 'index']);
$router->get('/admin/seo/{pageKey}/edit', [SeoController::class, 'edit']);
$router->post('/admin/seo/{pageKey}', [SeoController::class, 'update']);

// --- Admin: settings ---
$router->get('/admin/settings', [SettingsController::class, 'index']);
$router->post('/admin/settings', [SettingsController::class, 'update']);

// --- Admin: updates ---
$router->get('/admin/updates', [UpdateController::class, 'index']);
$router->post('/admin/updates/run', [UpdateController::class, 'run']);

// --- Account ---
$router->get('/account/login', [AccountAuthController::class, 'showLogin']);
$router->post('/account/login', [AccountAuthController::class, 'login']);
$router->get('/account/verify', [AccountAuthController::class, 'showVerify']);
$router->post('/account/verify', [AccountAuthController::class, 'verify']);
$router->get('/account/logout', [AccountAuthController::class, 'logout']);
$router->get('/account/orders', [AccountOrderController::class, 'index']);

$router->dispatch();
