<?php
/**
 * Ujuzi Training LMS — single front controller (see .htaccess: every
 * request that isn't a real file/directory is routed through this file).
 * Application code lives outside the web root, in /app/collections_apps.
 */

require dirname(__DIR__) . '/app/collections_apps/app/bootstrap.php';

use App\Core\Router;
use App\Controllers\SetupController;
use App\Controllers\LmsController;
use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\RoleController;
use App\Controllers\Admin\OrganisationController;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\FormController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\UpdateController;
use App\Controllers\Account\AuthController as AccountAuthController;
use App\Controllers\Account\DashboardController as AccountDashboardController;
use App\Controllers\Account\ProfileController;
use App\Controllers\Account\PeopleController;
use App\Controllers\Account\RegisterController;

$router = new Router();

// --- First-run setup ---
$router->get('/setup', [SetupController::class, 'index']);
$router->post('/setup', [SetupController::class, 'store']);

// --- Public LMS ---
$router->get('/', [LmsController::class, 'home']);

// --- Super admin: auth ---
$router->get('/admin/login', [AdminAuthController::class, 'showLogin']);
$router->post('/admin/login', [AdminAuthController::class, 'login']);
$router->get('/admin/logout', [AdminAuthController::class, 'logout']);

// --- Super admin: dashboard ---
$router->get('/admin', [DashboardController::class, 'index']);

// --- Super admin: roles ---
$router->get('/admin/roles', [RoleController::class, 'index']);
$router->get('/admin/roles/{id}/edit', [RoleController::class, 'edit']);
$router->post('/admin/roles/{id}', [RoleController::class, 'update']);

// --- Super admin: organisations ---
$router->get('/admin/organisations', [OrganisationController::class, 'index']);
$router->get('/admin/organisations/create', [OrganisationController::class, 'create']);
$router->post('/admin/organisations', [OrganisationController::class, 'store']);
$router->get('/admin/organisations/{id}/edit', [OrganisationController::class, 'edit']);
$router->post('/admin/organisations/{id}/invite', [OrganisationController::class, 'generateInvite']);
$router->post('/admin/organisations/{id}/invite/email', [OrganisationController::class, 'emailInvite']);
$router->post('/admin/organisations/{id}', [OrganisationController::class, 'update']);
$router->get('/admin/share-registration', [OrganisationController::class, 'share']);

// --- Super admin: users ---
$router->get('/admin/users', [UserController::class, 'index']);
$router->get('/admin/users/create', [UserController::class, 'create']);
$router->post('/admin/users', [UserController::class, 'store']);
$router->get('/admin/users/{id}/edit', [UserController::class, 'edit']);
$router->post('/admin/users/{id}', [UserController::class, 'update']);

// --- Super admin: forms ---
$router->get('/admin/forms', [FormController::class, 'index']);
$router->get('/admin/forms/create', [FormController::class, 'create']);
$router->post('/admin/forms', [FormController::class, 'store']);
$router->get('/admin/forms/{id}/edit', [FormController::class, 'edit']);
$router->post('/admin/forms/{id}', [FormController::class, 'update']);
$router->post('/admin/forms/{id}/delete', [FormController::class, 'destroy']);

// --- Super admin: settings ---
$router->get('/admin/settings', [SettingsController::class, 'index']);
$router->post('/admin/settings', [SettingsController::class, 'update']);

// --- Super admin: updates ---
$router->get('/admin/updates', [UpdateController::class, 'index']);
$router->post('/admin/updates/run', [UpdateController::class, 'run']);

// --- Public registration ---
$router->get('/account/register', [RegisterController::class, 'showStudent']);
$router->post('/account/register', [RegisterController::class, 'storeStudent']);
$router->get('/register/organisation-admin/{token}', [RegisterController::class, 'showOrganisationAdmin']);
$router->post('/register/organisation-admin/{token}', [RegisterController::class, 'storeOrganisationAdmin']);

// --- User account (role-based dashboards) ---
$router->get('/account/login', [AccountAuthController::class, 'showLogin']);
$router->post('/account/login', [AccountAuthController::class, 'login']);
$router->get('/account/verify', [AccountAuthController::class, 'showVerify']);
$router->post('/account/verify', [AccountAuthController::class, 'verify']);
$router->get('/account/logout', [AccountAuthController::class, 'logout']);
$router->get('/account/dashboard', [AccountDashboardController::class, 'index']);
$router->get('/account/profile', [ProfileController::class, 'index']);
$router->post('/account/profile', [ProfileController::class, 'update']);
$router->get('/account/people', [PeopleController::class, 'index']);
$router->get('/account/people/create', [PeopleController::class, 'create']);
$router->post('/account/people', [PeopleController::class, 'store']);
$router->get('/account/people/{id}', [PeopleController::class, 'show']);
$router->get('/account/people/{id}/edit', [PeopleController::class, 'edit']);
$router->post('/account/people/{id}', [PeopleController::class, 'update']);

$router->dispatch();
