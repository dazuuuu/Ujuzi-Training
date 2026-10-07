<?php
/**
 * Ujuzi Training LMS — single front controller (see .htaccess: every
 * request that isn't a real file/directory is routed through this file).
 * Application code lives outside the web root, in /app/logic_ujuzi.
 */

require dirname(__DIR__) . '/app/logic_ujuzi/app/bootstrap.php';

use App\Core\Router;
use App\Controllers\SetupController;
use App\Controllers\LmsController;
use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\AdminController as AdminAccountsController;
use App\Controllers\Admin\HomepageController;
use App\Controllers\Admin\PagesController;
use App\Controllers\Admin\AttachmentController as AdminAttachmentController;
use App\Controllers\Admin\RoleController;
use App\Controllers\Admin\OrganisationController;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\FormController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\NavigationController;
use App\Controllers\Admin\CertificateController as AdminCertificateController;
use App\Controllers\Admin\UpdateController;
use App\Controllers\Account\AuthController as AccountAuthController;
use App\Controllers\Account\PasswordResetController;
use App\Controllers\Account\DashboardController as AccountDashboardController;
use App\Controllers\Account\LandingController as AccountLandingController;
use App\Controllers\Account\ProfileController;
use App\Controllers\Account\ChangePasswordController;
use App\Controllers\Account\PeopleController;
use App\Controllers\Account\RegisterController;
use App\Controllers\Account\TrainerRequestController;
use App\Controllers\Account\CategoryController as AccountCategoryController;
use App\Controllers\Account\BranchController as AccountBranchController;
use App\Controllers\Account\CourseController;
use App\Controllers\Account\CertificateController as AccountCertificateController;
use App\Controllers\Account\AttachmentAudienceController;
use App\Controllers\Account\AttachmentController;
use App\Controllers\Account\AttachmentReviewController;
use App\Controllers\Account\BranchAdminController;
use App\Controllers\Account\CourseBranchAdminController;
use App\Controllers\Account\OrganisationProfileController;
use App\Controllers\Account\WalletController;
use App\Controllers\Account\DirectoryController;
use App\Controllers\Account\CertificateVerifyController;
use App\Controllers\Account\AttachmentPartnerController;
use App\Controllers\Admin\FinanceController;
use App\Controllers\Account\CourseOrganisationController;
use App\Controllers\Account\RecommendationLetterController;
use App\Controllers\Admin\DocumentController;
use App\Controllers\Admin\DataCleanupController;
use App\Controllers\Admin\StudentLookupController as AdminStudentLookupController;
use App\Controllers\Admin\ReportController;
use App\Controllers\Api\FormLookupController;

$router = new Router();

// --- First-run setup ---
$router->get('/setup', [SetupController::class, 'index']);
$router->post('/setup', [SetupController::class, 'store']);

// --- Public LMS ---
$router->get('/', [LmsController::class, 'home']);
$router->get('/courses', [LmsController::class, 'courses']);
$router->get('/about', [LmsController::class, 'about']);
$router->get('/p/{slug}', [LmsController::class, 'customPage']);
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
$router->post('/admin/organisations/{id}/delete', [OrganisationController::class, 'destroy']);
$router->get('/admin/share-registration', [OrganisationController::class, 'share']);

// --- Super admin: users ---
$router->get('/admin/registered-users', [UserController::class, 'index']);
$router->get('/admin/users', [UserController::class, 'index']);
$router->get('/admin/users/create', [UserController::class, 'create']);
$router->post('/admin/users', [UserController::class, 'store']);
$router->post('/admin/users/{id}/unlock', [UserController::class, 'unlock']);
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
$router->get('/admin/admins', [AdminAccountsController::class, 'index']);
$router->post('/admin/admins', [AdminAccountsController::class, 'store']);
$router->post('/admin/admins/{id}', [AdminAccountsController::class, 'update']);
$router->post('/admin/admins/{id}/delete', [AdminAccountsController::class, 'destroy']);
$router->get('/admin/course-approvals', [\App\Controllers\Admin\CourseApprovalController::class, 'index']);
$router->get('/admin/course-approvals/{id}/preview', [\App\Controllers\Admin\CourseApprovalController::class, 'preview']);
$router->post('/admin/course-approvals/{id}', [\App\Controllers\Admin\CourseApprovalController::class, 'decide']);
$router->post('/admin/branch-deletions/{id}', [\App\Controllers\Admin\CourseApprovalController::class, 'branchDeletion']);
$router->get('/admin/pages', [PagesController::class, 'index']);
$router->post('/admin/pages/save', [PagesController::class, 'save']);
$router->post('/admin/pages/preview', [PagesController::class, 'preview']);
$router->post('/admin/pages/upload', [PagesController::class, 'upload']);
$router->post('/admin/pages/new', [PagesController::class, 'create']);
$router->get('/admin/theme', [\App\Controllers\Admin\ThemeController::class, 'index']);
$router->post('/admin/theme', [\App\Controllers\Admin\ThemeController::class, 'save']);
$router->post('/admin/theme/import', [\App\Controllers\Admin\ThemeController::class, 'import']);
$router->post('/admin/theme/apply', [\App\Controllers\Admin\ThemeController::class, 'apply']);
$router->post('/admin/theme/discard', [\App\Controllers\Admin\ThemeController::class, 'discard']);
$router->post('/admin/theme/reset', [\App\Controllers\Admin\ThemeController::class, 'reset']);
$router->post('/admin/pages/custom/{slug}', [PagesController::class, 'updateMeta']);
$router->post('/admin/pages/custom/{slug}/delete', [PagesController::class, 'delete']);
$router->get('/admin/homepage', [HomepageController::class, 'index']);
$router->post('/admin/homepage/content', [HomepageController::class, 'saveContent']);
$router->post('/admin/homepage/courses', [HomepageController::class, 'saveCourses']);
$router->post('/admin/homepage/logos', [HomepageController::class, 'addLogo']);
$router->post('/admin/homepage/logos/{index}/delete', [HomepageController::class, 'removeLogo']);
$router->post('/admin/homepage/logos/{index}/{direction}', [HomepageController::class, 'moveLogo']);
$router->get('/admin/student-lookup', [AdminStudentLookupController::class, 'index']);
$router->get('/admin/reports', [ReportController::class, 'index']);
$router->get('/admin/reports/{section}/{format}', [ReportController::class, 'download']);
$router->get('/admin/attachments', [AdminAttachmentController::class, 'index']);
$router->get('/admin/attachments/export', [AdminAttachmentController::class, 'export']);
$router->get('/admin/finance', [FinanceController::class, 'index']);
$router->get('/admin/finance/organisations/{id}', [FinanceController::class, 'organisation']);
$router->post('/admin/finance/rate', [FinanceController::class, 'updateRate']);
$router->post('/admin/finance/min-payment', [FinanceController::class, 'updateMinPayment']);
$router->get('/admin/navigation', [NavigationController::class, 'index']);
$router->post('/admin/navigation/{portal}', [NavigationController::class, 'update']);

$router->get('/admin/certificate', [AdminCertificateController::class, 'index']);
$router->post('/admin/certificate', [AdminCertificateController::class, 'update']);
$router->get('/admin/documents', [DocumentController::class, 'index']);
$router->post('/admin/documents/{type}/layout', [DocumentController::class, 'saveLayout']);
$router->post('/admin/documents/{type}', [DocumentController::class, 'update']);
$router->get('/admin/data-cleanup', [DataCleanupController::class, 'index']);
$router->post('/admin/data-cleanup', [DataCleanupController::class, 'destroy']);
$router->post('/admin/data-cleanup/reset-everything', [DataCleanupController::class, 'resetEverything']);

// --- Super admin: updates ---
$router->get('/admin/updates', [UpdateController::class, 'index']);
$router->post('/admin/updates/run', [UpdateController::class, 'run']);

// --- Public registration ---
$router->get('/account/register/choose', [RegisterController::class, 'chooseRole']);
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
$router->post('/account/profile/public', [ProfileController::class, 'updatePublic']);
$router->post('/account/profile/photo', [ProfileController::class, 'updatePhoto']);
$router->get('/account/change-password', [ChangePasswordController::class, 'show']);
$router->post('/account/change-password', [ChangePasswordController::class, 'update']);
$router->post('/account/change-password/code', [ChangePasswordController::class, 'sendCode']);
$router->get('/account/organisations/{id}', [DirectoryController::class, 'organisation']);
$router->get('/account/tutors/{id}', [DirectoryController::class, 'tutor']);
$router->get('/account/verify-certificate', [CertificateVerifyController::class, 'index']);
$router->get('/account/student-lookup', [CertificateVerifyController::class, 'index']);
$router->get('/account/attachment-partners', [AttachmentPartnerController::class, 'index']);
$router->post('/account/attachment-partners/{providerId}/{decision}', [AttachmentPartnerController::class, 'decide']);
$router->get('/account/people', [PeopleController::class, 'index']);
$router->get('/account/people/create', [PeopleController::class, 'create']);
$router->get('/account/people/import', [PeopleController::class, 'showImport']);
$router->post('/account/people/import', [PeopleController::class, 'import']);
$router->post('/account/people', [PeopleController::class, 'store']);
$router->get('/account/people/{id}', [PeopleController::class, 'show']);
$router->get('/account/people/{id}/edit', [PeopleController::class, 'edit']);
$router->post('/account/people/{id}', [PeopleController::class, 'update']);
$router->post('/account/people/{id}/reset-password', [PeopleController::class, 'resetPassword']);
$router->post('/account/people/{id}/status/{status}', [PeopleController::class, 'status']);
$router->get('/account/trainer-requests', [TrainerRequestController::class, 'index']);
$router->get('/account/trainer-requests/{id}', [TrainerRequestController::class, 'reviewStudent']);
$router->post('/account/trainer-requests/{id}/approve', [TrainerRequestController::class, 'approve']);
$router->post('/account/trainer-requests/{id}/categories', [TrainerRequestController::class, 'updateCategories']);
$router->post('/account/trainer-requests/{id}/reject', [TrainerRequestController::class, 'reject']);
$router->post('/account/trainer-requests/{id}/approve-student', [TrainerRequestController::class, 'approveStudent']);
$router->get('/account/attachment-providers', [AttachmentController::class, 'index']);
$router->get('/account/attachment-providers/{id}', [AttachmentController::class, 'show']);
$router->get('/account/attachment-audience', [AttachmentAudienceController::class, 'edit']);
$router->post('/account/attachment-audience', [AttachmentAudienceController::class, 'update']);
$router->post('/account/attachments/select', [AttachmentController::class, 'select']);
$router->post('/account/attachments/bulk-accept', [AttachmentController::class, 'providerBulkAccept']);
$router->get('/account/attachments-export', [AttachmentController::class, 'providerExport']);
$router->post('/account/attachments/{id}/cancel', [AttachmentController::class, 'cancel']);
$router->get('/account/attachments/{id}', [AttachmentController::class, 'thread']);
$router->post('/account/attachments/{id}/reply', [AttachmentController::class, 'reply']);
$router->post('/account/attachment-requests/{id}/assessment', [\App\Controllers\Account\AttachmentReviewController::class, 'assessment']);
$router->get('/account/attachment-requests/{id}', [AttachmentReviewController::class, 'show']);
$router->post('/account/attachment-requests/{id}/respond', [AttachmentReviewController::class, 'respond']);
$router->post('/account/attachment-requests/{id}/resend-letter', [AttachmentReviewController::class, 'resendLetter']);
$router->post('/account/attachments/{id}/accept', [AttachmentController::class, 'providerAccept']);
$router->post('/account/attachments/{id}/complete', [AttachmentController::class, 'providerComplete']);
$router->get('/account/recommendation-letter/{applicationId}', [RecommendationLetterController::class, 'show']);
$router->get('/account/certificates', [\App\Controllers\Account\OrganisationCertificateController::class, 'index']);
$router->get('/account/reports', [\App\Controllers\Account\ReportController::class, 'index']);
$router->get('/account/search', [\App\Controllers\Account\SearchController::class, 'index']);
$router->get('/account/assessments', [\App\Controllers\Account\AssessmentsController::class, 'index']);
$router->get('/account/locked', [\App\Controllers\Account\AccountLockController::class, 'show']);
$router->post('/account/locked', [\App\Controllers\Account\AccountLockController::class, 'update']);
$router->get('/account/verify-email/{token}', [\App\Controllers\Account\EmailVerificationController::class, 'verify']);
$router->get('/account/branch-students', [\App\Controllers\Account\BranchStudentsController::class, 'index']);
$router->post('/account/branch-students', [\App\Controllers\Account\BranchStudentsController::class, 'store']);
$router->post('/account/branch-students/import', [\App\Controllers\Account\BranchStudentsController::class, 'import']);
$router->get('/account/branch-students/template', [\App\Controllers\Account\BranchStudentsController::class, 'template']);
$router->get('/account/branch-students/export/{list}/{format}', [\App\Controllers\Account\BranchStudentsController::class, 'export']);
$router->get('/account/reports/export', [\App\Controllers\Account\ReportController::class, 'export']);
$router->post('/account/certificates/template', [\App\Controllers\Account\OrganisationCertificateController::class, 'upload']);
$router->post('/account/certificates/layout', [\App\Controllers\Account\OrganisationCertificateController::class, 'layout']);
$router->post('/account/certificates/mode', [\App\Controllers\Account\OrganisationCertificateController::class, 'mode']);
$router->get('/account/certificates/{userId}/{courseId}', [\App\Controllers\Account\OrganisationCertificateController::class, 'show']);
$router->get('/account/course-organisations', [CourseOrganisationController::class, 'index']);
$router->post('/account/course-organisations/request', [CourseOrganisationController::class, 'request']);
$router->get('/account/organisation', [OrganisationProfileController::class, 'edit']);
$router->post('/account/organisation', [OrganisationProfileController::class, 'update']);
$router->get('/account/branch-admin', [BranchAdminController::class, 'index']);
$router->post('/account/branch-admin/bulk-accept', [BranchAdminController::class, 'bulkAccept']);
$router->get('/account/branch-admin/export', [BranchAdminController::class, 'export']);
$router->post('/account/branch-admin/{id}/accept', [BranchAdminController::class, 'accept']);
$router->post('/account/branch-admin/{id}/complete', [BranchAdminController::class, 'complete']);
$router->get('/account/course-branch-admin', [CourseBranchAdminController::class, 'index']);
$router->post('/account/course-branch-admin/{id}/approve', [CourseBranchAdminController::class, 'approve']);
$router->post('/account/course-branch-admin/{id}/categories', [CourseBranchAdminController::class, 'updateCategories']);
$router->post('/account/course-branch-admin/{id}/reject', [CourseBranchAdminController::class, 'reject']);

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

$router->get('/account/wallet', [WalletController::class, 'index']);
$router->get('/account/wallet/export/{format}', [WalletController::class, 'export']);
$router->post('/account/wallet/deposit', [WalletController::class, 'deposit']);
$router->get('/account/courses', [CourseController::class, 'index']);
$router->get('/account/courses/create', [CourseController::class, 'create']);
$router->post('/account/courses', [CourseController::class, 'store']);
$router->get('/account/courses/{id}', [CourseController::class, 'show']);
$router->post('/account/courses/{id}/enroll', [CourseController::class, 'enroll']);
$router->get('/account/courses/{id}/checkout', [CourseController::class, 'checkout']);
$router->post('/account/courses/{id}/checkout', [CourseController::class, 'confirmCheckout']);
$router->get('/account/courses/{id}/edit', [CourseController::class, 'edit']);
$router->post('/account/courses/{id}', [CourseController::class, 'update']);
$router->post('/account/courses/{id}/delete', [CourseController::class, 'destroy']);
$router->post('/account/courses/{id}/modules', [CourseController::class, 'storeModule']);
$router->post('/account/courses/{id}/modules/{moduleId}', [CourseController::class, 'updateModule']);
$router->post('/account/courses/{id}/modules/{moduleId}/delete', [CourseController::class, 'destroyModule']);
$router->post('/account/courses/{id}/modules/{moduleId}/quiz', [CourseController::class, 'submitQuiz']);
$router->post('/account/courses/{id}/modules/{moduleId}/unlock', [CourseController::class, 'unlockModule']);
$router->post('/account/courses/{id}/modules/{moduleId}/done', [CourseController::class, 'markModuleDone']);
$router->post('/account/courses/{id}/complete', [CourseController::class, 'completeCourse']);
$router->post('/account/courses/{id}/retake', [CourseController::class, 'retake']);
$router->post('/account/courses/{id}/final-exam', [CourseController::class, 'updateFinalExam']);
$router->post('/account/courses/{id}/final-exam/submit', [CourseController::class, 'submitFinalExam']);
$router->get('/account/certificate', [AccountCertificateController::class, 'show']);

$router->dispatch();
