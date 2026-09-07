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
use App\Controllers\Admin\CertificateController as AdminCertificateController;
use App\Controllers\Admin\UpdateController;
use App\Controllers\Account\AuthController as AccountAuthController;
use App\Controllers\Account\PasswordResetController;
use App\Controllers\Account\DashboardController as AccountDashboardController;
use App\Controllers\Account\LandingController as AccountLandingController;
use App\Controllers\Account\ProfileController;
use App\Controllers\Account\PeopleController;
use App\Controllers\Account\RegisterController;
use App\Controllers\Account\TrainerRequestController;
use App\Controllers\Account\CategoryController as AccountCategoryController;
use App\Controllers\Account\BranchController as AccountBranchController;
use App\Controllers\Account\CourseController;
use App\Controllers\Account\CertificateController as AccountCertificateController;
use App\Controllers\Api\FormLookupController;

$router = new Router();

// --- First-run setup ---
$router->get('/setup', [SetupController::class, 'index']);
$router->post('/setup', [SetupController::class, 'store']);

// --- Public LMS ---
$router->get('/', [LmsController::class, 'home']);
$router->get('/api/form/organisations', [FormLookupController::class, 'organisations']);
$router->get('/api/form/attachment-providers', [FormLookupController::class, 'attachmentProviders']);
$router->get('/api/form/branches', [FormLookupController::class, 'branches']);

// --- Super admin: auth ---
$router->get('/admin/login', [AdminAuthController::class, 'showLogin']);
$router->post('/admin/login', [AdminAuthController::class, 'login']);
$router->get('/admin/forgot-password', [AdminAuthController::class, 'showForgot']);
$router->post('/admin/forgot-password', [AdminAuthController::class, 'sendReset']);
$router->get('/admin/forgot-password/verify', [AdminAuthController::class, 'showResetVerify']);
$router->post('/admin/forgot-password/verify', [AdminAuthController::class, 'verifyReset']);
$router->get('/admin/forgot-password/new', [AdminAuthController::class, 'showNewPassword']);
$router->post('/admin/forgot-password/new', [AdminAuthController::class, 'storeNewPassword']);
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
$router->get('/admin/registered-users', [UserController::class, 'index']);
$router->get('/admin/users', [UserController::class, 'index']);
$router->get('/admin/users/create', [UserController::class, 'create']);
$router->post('/admin/users', [UserController::class, 'store']);
$router->get('/admin/users/{id}/edit', [UserController::class, 'edit']);
$router->get('/admin/users/{id}', [UserController::class, 'show']);
$router->post('/admin/users/{id}', [UserController::class, 'update']);
$router->post('/admin/users/{id}/status', [UserController::class, 'status']);
$router->post('/admin/users/{id}/delete', [UserController::class, 'destroy']);

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

$router->get('/admin/certificate', [AdminCertificateController::class, 'index']);
$router->post('/admin/certificate', [AdminCertificateController::class, 'update']);

// --- Super admin: updates ---
$router->get('/admin/updates', [UpdateController::class, 'index']);
$router->post('/admin/updates/run', [UpdateController::class, 'run']);

// --- Public registration ---
$router->get('/account/register', [RegisterController::class, 'showStudent']);
$router->post('/account/register', [RegisterController::class, 'storeStudent']);
$router->get('/account/register/trainer', [RegisterController::class, 'showTrainer']);
$router->post('/account/register/trainer', [RegisterController::class, 'storeTrainer']);
$router->get('/account/register/attachment-trainer', [RegisterController::class, 'showAttachmentTrainer']);
$router->post('/account/register/attachment-trainer', [RegisterController::class, 'storeAttachmentTrainer']);
$router->get('/register/organisation-admin/{token}', [RegisterController::class, 'showOrganisationAdmin']);
$router->post('/register/organisation-admin/{token}', [RegisterController::class, 'storeOrganisationAdmin']);

// --- User account (role-based dashboards) ---
$router->get('/account', [AccountLandingController::class, 'index']);
$router->get('/account/login', [AccountAuthController::class, 'choose']);
$router->post('/account/login', [AccountAuthController::class, 'choose']);
$router->get('/account/login/{role}', [AccountAuthController::class, 'showLogin']);
$router->post('/account/login/{role}', [AccountAuthController::class, 'login']);
$router->get('/account/verify', [AccountAuthController::class, 'showVerify']);
$router->post('/account/verify', [AccountAuthController::class, 'verify']);
$router->get('/account/forgot-password', [PasswordResetController::class, 'showRequest']);
$router->post('/account/forgot-password', [PasswordResetController::class, 'send']);
$router->get('/account/forgot-password/verify', [PasswordResetController::class, 'showVerify']);
$router->post('/account/forgot-password/verify', [PasswordResetController::class, 'verify']);
$router->get('/account/forgot-password/new', [PasswordResetController::class, 'showNew']);
$router->post('/account/forgot-password/new', [PasswordResetController::class, 'storeNew']);
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
$router->post('/account/trainer-requests/{id}/approve', [TrainerRequestController::class, 'approve']);
$router->post('/account/trainer-requests/{id}/reject', [TrainerRequestController::class, 'reject']);

$router->get('/account/categories', [AccountCategoryController::class, 'index']);
$router->get('/account/categories/create', [AccountCategoryController::class, 'create']);
$router->post('/account/categories', [AccountCategoryController::class, 'store']);
$router->get('/account/categories/{id}/edit', [AccountCategoryController::class, 'edit']);
$router->post('/account/categories/{id}', [AccountCategoryController::class, 'update']);
$router->post('/account/categories/{id}/delete', [AccountCategoryController::class, 'destroy']);

$router->get('/account/branches', [AccountBranchController::class, 'index']);
$router->get('/account/branches/create', [AccountBranchController::class, 'create']);
$router->post('/account/branches', [AccountBranchController::class, 'store']);
$router->get('/account/branches/{id}/edit', [AccountBranchController::class, 'edit']);
$router->post('/account/branches/{id}', [AccountBranchController::class, 'update']);
$router->post('/account/branches/{id}/delete', [AccountBranchController::class, 'destroy']);

$router->get('/account/courses', [CourseController::class, 'index']);
$router->get('/account/courses/create', [CourseController::class, 'create']);
$router->post('/account/courses', [CourseController::class, 'store']);
$router->get('/account/courses/{id}', [CourseController::class, 'show']);
$router->get('/account/courses/{id}/edit', [CourseController::class, 'edit']);
$router->post('/account/courses/{id}', [CourseController::class, 'update']);
$router->post('/account/courses/{id}/delete', [CourseController::class, 'destroy']);
$router->post('/account/courses/{id}/modules', [CourseController::class, 'storeModule']);
$router->post('/account/courses/{id}/modules/{moduleId}', [CourseController::class, 'updateModule']);
$router->post('/account/courses/{id}/modules/{moduleId}/delete', [CourseController::class, 'destroyModule']);
$router->post('/account/courses/{id}/modules/{moduleId}/quiz', [CourseController::class, 'submitQuiz']);
$router->get('/account/certificate', [AccountCertificateController::class, 'show']);

$router->dispatch();
