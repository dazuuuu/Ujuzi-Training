<?php

namespace App\Controllers\Account;

use App\Core\AccountRedirect;
use App\Core\Authz;
use App\Core\Request;
use App\Models\AttachmentApplication;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Services\CourseRetake;
use App\Services\WalletException;
use App\Services\ModuleAccess;
use App\Services\WalletService;
use App\Models\CourseFinalExamProgress;
use App\Models\CourseModule;
use App\Models\CourseModuleProgress;
use App\Models\Form;
use App\Models\FormField;
use App\Models\OrganisationBranch;
use App\Models\OrganisationCategory;
use App\Models\OrganisationMembership;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\FormAnswerService;
use App\Services\UploadException;
use App\Services\UploadService;

class CourseController extends BaseAccountController
{
    public function index(): void
    {
        $this->requireViewer();
        if (Authz::isStudent($this->user)) {
            CourseRetake::expireDue((int) $this->user['id']);
            try {
                $courses = Course::forLearner(Authz::learnerOrganisationIds($this->user), Authz::approvedCategoryIds($this->user));
                $courses = $this->markEnrollment($courses);
            } catch (\Throwable $e) {
                $courses = [];
            }
            // Students don't need their organisation's branch list on this page.
            $branches = [];
        } elseif (Authz::isOrganisationAdmin($this->user)) {
            $courses = Course::forOrganisation((int) $this->user['organisation_id']);
            $branches = [];
            $pendingTrainerRequests = OrganisationMembership::pendingTrainersForOrganisation((int) $this->user['organisation_id']);
        } else {
            $courses = Course::forTrainer((int) $this->user['id']);
            $branches = [];
            $pendingTrainerRequests = [];
        }

        $this->render('account.courses.index', [
            'pageTitle' => 'Courses',
            'activeNav' => 'courses',
            'courses' => $courses,
            'courseGroups' => Authz::isStudent($this->user) ? $this->groupForStudent($courses) : [],
            'branches' => $branches,
            'canCreate' => Authz::canCreateCourses($this->user),
            'isStudent' => Authz::isStudent($this->user),
            'paymentsEnabled' => $this->paymentsEnabled(),
            'pendingTrainerRequests' => $pendingTrainerRequests ?? [],
        ]);
    }

    /**
     * Students see their organisation's courses first, then the open ones
     * anybody can take. Empty groups are dropped.
     */
    private function groupForStudent(array $courses): array
    {
        $organisation = [];
        $open = [];
        foreach ($courses as $course) {
            if (($course['visibility'] ?? 'strict') === 'global') {
                $open[] = $course;
            } else {
                $organisation[] = $course;
            }
        }

        $groups = [];
        if ($organisation) {
            $groups[] = [
                'key' => 'organisation',
                'title' => 'My organisation courses',
                'note' => 'Every course from the organisation that accepted you — new ones appear here as soon as they are published.',
                'courses' => $organisation,
            ];
        }
        if ($open) {
            $groups[] = [
                'key' => 'general',
                'title' => 'General courses',
                'note' => 'Courses open to every student.',
                'courses' => $open,
            ];
        }
        return $groups;
    }

    public function create(): void
    {
        $this->requireCreator();
        $this->requirePublicProfile();
        $this->render('account.courses.form', [
            'pageTitle' => 'Create course',
            'activeNav' => 'courses',
            'course' => null,
            'forms' => $this->courseForms(),
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireCreator();
        $this->requirePublicProfile();
        $this->persist(null);
    }

    public function show(string $id): void
    {
        if (Authz::isStudent($this->user)) {
            CourseRetake::expireDue((int) $this->user['id']);
        }
        $course = $this->accessibleCourse((int) $id);
        $modules = CourseModule::forCourse((int) $course['id']);
        $editingModule = null;
        $editId = (int) Request::query('module', 0);
        $canEdit = Authz::canEditCourse($this->user, $course);
        $isStudent = Authz::isStudent($this->user);
        $isEnrolled = false;
        if ($isStudent) {
            try {
                $isEnrolled = CourseEnrollment::isEnrolled((int) $this->user['id'], (int) $course['id']);
            } catch (\Throwable $e) {
                $isEnrolled = false;
            }
        }
        if ($editId && $canEdit) {
            foreach ($modules as $module) {
                if ((int) $module['id'] === $editId) {
                    $editingModule = $module;
                    break;
                }
            }
        }

        $progress = [];
        $finalProgress = null;
        $modulesComplete = false;
        $attachmentApplication = null;
        if ($isStudent && $isEnrolled) {
            try {
                $progress = CourseModuleProgress::forUserCourse((int) $this->user['id'], (int) $course['id']);
                $modules = CourseModule::withUnlockState($modules, $progress);
                $modulesComplete = Course::modulesCompletedByUser((int) $course['id'], (int) $this->user['id']);
                $finalProgress = CourseFinalExamProgress::findForUserCourse((int) $this->user['id'], (int) $course['id']);
                $attachmentApplication = AttachmentApplication::forStudentCourse((int) $this->user['id'], (int) $course['id']);
            } catch (\Throwable $e) {
                $modules = CourseModule::withUnlockState($modules, []);
            }
        }

        // Students see the course in the learning view: the lesson (or the
        // course's About page) in the middle, the course outline on the right.
        // Before enrolling every module is shown with its price, locked.
        // Tutors and admins keep the full page with the editing tools.
        if ($isStudent && !$canEdit) {
            $userId = (int) $this->user['id'];
            $paidKsh = 0.0;
            try {
                $paidKsh = WalletService::paidForCourse($userId, (int) $course['id']);
            } catch (\Throwable $e) {
                $paidKsh = 0.0;
            }
            if ($isEnrolled) {
                $modules = ModuleAccess::annotate($userId, $course, $modules);
            } else {
                $modules = array_map(static function (array $m): array {
                    return $m + ['is_unlocked' => false, 'is_passed' => false, 'is_done' => false, 'progress' => null, 'needs_payment' => false, 'in_history' => false, 'access' => null];
                }, $modules);
            }
            $tutor = null;
            try {
                $tutor = User::find((int) $course['trainer_user_id']);
            } catch (\Throwable $e) {
                $tutor = null;
            }
            $this->render('account.courses.learn', [
                'pageTitle' => $course['title'],
                'activeNav' => 'courses',
                'course' => $course,
                'modules' => $modules,
                'isEnrolled' => $isEnrolled,
                'tutor' => $tutor,
                'paymentsEnabled' => $this->paymentsEnabled(),
                'plan' => ModuleAccess::plan($userId, $course, count($modules)),
                'prices' => ModuleAccess::prices((float) ($course['enrollment_fee_ksh'] ?? 0), count($modules)),
                'perModule' => $isEnrolled ? ModuleAccess::chargesPerModule($userId, $course) : (float) ($course['enrollment_fee_ksh'] ?? 0) > 0,
                'walletKsh' => WalletService::balanceKsh($userId),
                'finalProgress' => $finalProgress,
                'modulesComplete' => $modulesComplete,
                'lesson' => (string) Request::query('lesson', ''),
                'quizResult' => $this->takeQuizResult(),
                'finalPaperQuestions' => $isEnrolled ? Course::paperQuestions($course, Course::finalPaper($course, $userId, $finalProgress)) : [],
                'runEndsAt' => $isEnrolled ? CourseRetake::endsAt($userId, (int) $course['id']) : null,
                'feeKsh' => (float) ($course['enrollment_fee_ksh'] ?? 0),
                'paidKsh' => $paidKsh,
                'settled' => $isEnrolled && Course::feeSettledByUser((int) $course['id'], $userId),
            ]);
            return;
        }

        $this->render('account.courses.show', [
            'pageTitle' => $course['title'],
            'activeNav' => 'courses',
            'course' => $course,
            'modules' => $modules,
            'canEdit' => $canEdit,
            'isStudent' => $isStudent,
            'isEnrolled' => $isEnrolled,
            'durations' => CourseModule::durations(),
            'moduleForm' => $editingModule ?: $this->blankModule(),
            'editingModule' => $editingModule,
            'finalProgress' => $finalProgress,
            'modulesComplete' => $modulesComplete,
            'paymentsEnabled' => $this->paymentsEnabled(),
            'attachmentApplication' => $attachmentApplication,
        ]);
    }

    public function enroll(string $id): void
    {
        $course = $this->accessibleCourse((int) $id);
        if (!Authz::isStudent($this->user)) {
            flashError('Only students enroll for courses.');
            redirect('/account/courses/' . $course['id']);
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/courses/' . $course['id']);
        }

        $this->requireFullRegistration($course);

        if ((float) ($course['enrollment_fee_ksh'] ?? 0) > 0) {
            redirect('/account/courses/' . $course['id'] . '/checkout');
        }

        $paymentsBypassed = !$this->paymentsEnabled() && (float) ($course['enrollment_fee_ksh'] ?? 0) > 0;
        CourseEnrollment::enroll((int) $this->user['id'], (int) $course['id'], [
            'amount_ksh' => $paymentsBypassed ? (float) ($course['enrollment_fee_ksh'] ?? 0) : 0,
            'payment_provider' => $paymentsBypassed ? 'testing_bypass' : 'free',
            'payment_status' => 'paid',
            'payment_reference' => ($paymentsBypassed ? 'TEST-' : 'FREE-') . strtoupper(bin2hex(random_bytes(4))),
        ]);
        flashSuccess($paymentsBypassed
            ? 'Payments are closed for testing. You are enrolled and can test the full course.'
            : 'You are enrolled. Start with the course introduction.');
        redirect('/account/courses/' . $course['id']);
    }

    public function checkout(string $id): void
    {
        $course = $this->accessibleCourse((int) $id);
        if (!Authz::isStudent($this->user)) {
            flashError('Only students enroll for courses.');
            redirect('/account/courses/' . $course['id']);
        }
        $this->requireFullRegistration($course);

        $userId = (int) $this->user['id'];
        $paid = WalletService::paidForCourse($userId, (int) $course['id']);
        $fee = (float) ($course['enrollment_fee_ksh'] ?? 0);
        if ($fee > 0 && $paid >= $fee) {
            flashSuccess('This course is fully paid.');
            redirect('/account/courses/' . $course['id']);
        }
        $moduleCount = count(CourseModule::forCourse((int) $course['id']));

        $this->render('account.courses.checkout', [
            'pageTitle' => 'Enrol',
            'activeNav' => 'courses',
            'course' => $course,
            'paidKsh' => $paid,
            'balanceKsh' => WalletService::balanceKsh($userId),
            'depositKsh' => WalletService::enrolmentDeposit($course),
            'moduleCount' => $moduleCount,
            'plan' => ModuleAccess::plan($userId, $course, $moduleCount),
            'isEnrolled' => CourseEnrollment::isEnrolled($userId, (int) $course['id']),
        ]);
    }

    /** Pays part (the minimum share first — see WalletService::minPaymentPercent()) or all of the fee from the wallet; enrols on the first payment. */
    public function confirmCheckout(string $id): void
    {
        $course = $this->accessibleCourse((int) $id);
        if (!Authz::isStudent($this->user)) {
            flashError('Only students enroll for courses.');
            redirect('/account/courses/' . $course['id']);
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/courses/' . $course['id'] . '/checkout');
        }
        $this->requireFullRegistration($course);

        $userId = (int) $this->user['id'];
        $amount = (float) Request::post('amount_ksh', 0);
        $wasEnrolled = CourseEnrollment::isEnrolled($userId, (int) $course['id']);

        // Enrolling needs the minimum deposit (its share of the fee) in the wallet.
        // Nothing is charged for that — modules are paid for one by one as they open.
        if (!$wasEnrolled) {
            $deposit = WalletService::enrolmentDeposit($course);
            $available = WalletService::balanceKsh($userId);
            if ($available + 0.001 < $deposit) {
                flashError(sprintf(
                    'To enrol you need at least %s coins (Ksh %s — %d%% of the fee) in your wallet. You have %s coins (Ksh %s). Deposit first.',
                    ModuleAccess::coins($deposit), number_format($deposit, 2), WalletService::minPaymentPercent(),
                    ModuleAccess::coins($available), number_format($available, 2)
                ));
                redirect('/account/courses/' . $course['id'] . '/checkout');
            }
        }

        try {
            if ($amount > 0) {
                WalletService::payCourse($userId, $course, $amount); // optional: pay ahead
            } elseif (!$wasEnrolled) {
                CourseEnrollment::enroll($userId, (int) $course['id'], [
                    'amount_ksh' => 0, 'payment_provider' => 'wallet', 'payment_status' => 'partial',
                    'payment_reference' => 'ENROL-' . strtoupper(bin2hex(random_bytes(4))),
                ]);
            }
        } catch (WalletException $e) {
            flashError($e->getMessage());
            redirect('/account/courses/' . $course['id'] . '/checkout');
        }

        // A paid course without modules is paid in one go when enrolling.
        if (!$wasEnrolled && !CourseModule::forCourse((int) $course['id'])) {
            $left = (float) $course['enrollment_fee_ksh'] - WalletService::paidForCourse($userId, (int) $course['id']);
            if ($left > 0) {
                try {
                    WalletService::payCourse($userId, $course, $left);
                } catch (WalletException $e) {
                    flashError('You are enrolled, but this course has no modules and is paid in full: ' . $e->getMessage());
                    redirect('/account/courses/' . $course['id']);
                }
            }
        }

        $left = (float) $course['enrollment_fee_ksh'] - WalletService::paidForCourse($userId, (int) $course['id']);
        flashSuccess($wasEnrolled
            ? ($left > 0 ? 'Payment received. Balance left: Ksh ' . number_format($left, 2) . '.' : 'Course fully paid.')
            : 'You are enrolled. Open a module to pay for it from your wallet and start learning.');
        redirect('/account/courses/' . $course['id']);
    }

    public function edit(string $id): void
    {
        $course = $this->editableCourse((int) $id);
        $this->render('account.courses.form', [
            'pageTitle' => 'Edit course',
            'activeNav' => 'courses',
            'course' => $course,
            'forms' => $this->courseForms($course),
            'errors' => [],
        ]);
    }

    public function update(string $id): void
    {
        $this->editableCourse((int) $id);
        $this->persist((int) $id);
    }

    public function destroy(string $id): void
    {
        $course = $this->editableCourse((int) $id);
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/courses/' . $course['id']);
        }
        Course::delete((int) $course['id']);
        flashSuccess('Course deleted.');
        redirect('/account/courses');
    }

    public function storeModule(string $id): void
    {
        $course = $this->editableCourse((int) $id);
        $this->persistModule($course, null);
    }

    public function updateModule(string $id, string $moduleId): void
    {
        $course = $this->editableCourse((int) $id);
        $module = $this->ownedModule($course, (int) $moduleId);
        $this->persistModule($course, $module);
    }

    public function destroyModule(string $id, string $moduleId): void
    {
        $course = $this->editableCourse((int) $id);
        $module = $this->ownedModule($course, (int) $moduleId);
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/courses/' . $course['id']);
        }
        CourseModule::delete((int) $module['id']);
        flashSuccess('Module removed.');
        redirect('/account/courses/' . $course['id']);
    }

    public function updateFinalExam(string $id): void
    {
        $course = $this->editableCourse((int) $id);
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/courses/' . $course['id'] . '#final-exam');
        }

        $passPercent = Course::FINAL_PASS_PERCENT;
        $paperSize = (int) Request::post('final_paper_size', 0);
        // The final exam is optional: switched off, the course has none.
        if (Request::post('has_final', '') !== '1') {
            Course::updateFinalExam((int) $course['id'], [], $passPercent);
            flashSuccess('This course has no final exam. Students finish it by ticking "Complete course" after the last module.');
            redirect('/account/courses/' . $course['id'] . '#final-exam');
        }
        $questions = CourseModule::normalizeQuestions($this->postedQuestionsFrom('final_questions'));
        if (!$questions) {
            flashError('Add at least one valid final exam question (choice questions need two answer choices), or untick "This course has a final exam".');
            redirect('/account/courses/' . $course['id'] . '#final-exam');
        }

        Course::updateFinalExam((int) $course['id'], $questions, $passPercent, $paperSize > 0 && $paperSize < count($questions) ? $paperSize : null);
        flashSuccess('Final exam saved. Students take it once they have worked through every module and paid.');
        redirect('/account/courses/' . $course['id'] . '#final-exam');
    }

    public function submitQuiz(string $id, string $moduleId): void
    {
        $course = $this->accessibleCourse((int) $id);
        if (!Authz::isStudent($this->user)) {
            flashError('Only students take the module quiz.');
            redirect('/account/courses/' . $course['id']);
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/courses/' . $course['id']);
        }
        if (!CourseEnrollment::isEnrolled((int) $this->user['id'], (int) $course['id'])) {
            flashError('Enroll for this course before taking module quizzes.');
            redirect('/account/courses/' . $course['id']);
        }

        $module = $this->ownedModule($course, (int) $moduleId);
        $modules = CourseModule::withUnlockState(
            CourseModule::forCourse((int) $course['id']),
            CourseModuleProgress::forUserCourse((int) $this->user['id'], (int) $course['id'])
        );
        $current = null;
        foreach ($modules as $row) {
            if ((int) $row['id'] === (int) $module['id']) {
                $current = $row;
                break;
            }
        }
        if (!$current || empty($current['is_unlocked'])) {
            flashError('Finish the module before this one first.');
            redirect('/account/courses/' . $course['id']);
        }
        $here = '/account/courses/' . $course['id'] . '?lesson=m' . $module['id'] . '#lesson';
        $current = ModuleAccess::annotate((int) $this->user['id'], $course, [$current])[0];
        if (!empty($current['needs_payment'])) {
            flashError('Unlock this module from your wallet before taking its quiz.');
            redirect($here);
        }
        if (!empty($current['is_passed'])) {
            flashError('You already passed this quiz. A passed quiz can\'t be taken again.');
            redirect($here);
        }
        if (empty($current['quiz_questions'])) {
            flashError('This module has no quiz.');
            redirect('/account/courses/' . $course['id'] . '#topic-' . $module['id']);
        }

        $posted = Request::post('answers', []);
        if (!is_array($posted)) {
            $posted = [];
        }
        $result = CourseModule::grade($current, $posted);
        CourseModuleProgress::saveAttempt([
            'user_id' => (int) $this->user['id'],
            'course_id' => (int) $course['id'],
            'module_id' => (int) $module['id'],
            'score' => $result['score'],
            'passed' => $result['passed'],
            'answers' => $posted,
        ]);

        // The result shows inside the quiz section, not as a banner at the top.
        $allDone = $result['passed'] && Course::modulesCompletedByUser((int) $course['id'], (int) $this->user['id']);
        $_SESSION['quiz_result'] = [
            'key' => 'm' . $module['id'],
            'passed' => $result['passed'],
            'message' => $result['passed']
                ? 'You scored ' . $result['score'] . '%. ' . ($allDone ? 'All modules are complete — the final exam is open.' : 'The next module is now open.')
                : 'You scored ' . $result['score'] . '%. You need ' . (int) $current['pass_percent'] . '% to open the next module — read the module again and retry.',
        ];
        redirect('/account/courses/' . $course['id'] . '?lesson=m' . $module['id'] . '#quiz');
    }

    /** Pays for the next module from the student's wallet (money already paid towards the course is used first). */
    public function unlockModule(string $id, string $moduleId): void
    {
        [$course, $module, $modules] = $this->studentModule((int) $id, (int) $moduleId);
        $here = '/account/courses/' . $course['id'] . '?lesson=m' . $module['id'] . '#lesson';
        if (empty($module['is_unlocked'])) {
            flashError('Finish the module before this one first.');
            redirect($here);
        }
        if (empty($module['needs_payment'])) {
            redirect($here);
        }
        try {
            $access = ModuleAccess::unlock((int) $this->user['id'], $course, $module, count($modules));
        } catch (WalletException $e) {
            flashError($e->getMessage());
            redirect($here);
        }
        flashSuccess('Module unlocked' . ((float) $access['from_wallet_ksh'] > 0 ? ' — ' . ModuleAccess::coins((float) $access['from_wallet_ksh']) . ' coins paid from your wallet' : '') . '. It stays open for ' . ModuleAccess::ACCESS_DAYS . ' days.');
        redirect($here);
    }

    /** A module without a quiz is done once the student has read it and ticks it off. */
    public function markModuleDone(string $id, string $moduleId): void
    {
        [$course, $module, $modules] = $this->studentModule((int) $id, (int) $moduleId);
        $here = '/account/courses/' . $course['id'] . '?lesson=m' . $module['id'] . '#lesson';
        if (empty($module['is_unlocked']) || !empty($module['needs_payment'])) {
            flashError('Open this module first.');
            redirect($here);
        }
        if (!empty($module['quiz_questions'])) {
            flashError('Pass this module\'s quiz to complete it.');
            redirect($here);
        }
        CourseModuleProgress::saveAttempt([
            'user_id' => (int) $this->user['id'], 'course_id' => (int) $course['id'], 'module_id' => (int) $module['id'],
            'score' => 100, 'passed' => true, 'answers' => [],
        ]);
        $ids = array_map(static fn(array $m): int => (int) $m['id'], $modules);
        $at = array_search((int) $module['id'], $ids, true);
        flashSuccess('Marked as done.');
        redirect('/account/courses/' . $course['id'] . '?lesson=' . (isset($ids[$at + 1]) ? 'm' . $ids[$at + 1] : 'final') . '#lesson');
    }

    /** For a course without a final exam: the student ticks it complete after the last module. */
    public function completeCourse(string $id): void
    {
        $course = $this->accessibleCourse((int) $id);
        $userId = (int) $this->user['id'];
        if (!Authz::isStudent($this->user) || !CourseEnrollment::isEnrolled($userId, (int) $course['id'])) {
            flashError('Enrol for this course first.');
            redirect('/account/courses/' . $course['id']);
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/courses/' . $course['id']);
        }
        $here = '/account/courses/' . $course['id'] . '?lesson=final#lesson';
        if (!empty($course['final_exam_questions'])) {
            flashError('This course finishes with its final exam.');
            redirect($here);
        }
        if (!Course::modulesCompletedByUser((int) $course['id'], $userId)) {
            flashError('Finish every module first.');
            redirect($here);
        }
        if (!Course::feeSettledByUser((int) $course['id'], $userId)) {
            flashError('Clear your balance for this course to complete it.');
            redirect($here);
        }
        CourseFinalExamProgress::saveAttempt(['user_id' => $userId, 'course_id' => (int) $course['id'], 'score' => 100, 'passed' => true, 'answers' => []]);
        flashSuccess('Congratulations — you completed ' . $course['title'] . '.');
        redirect($this->certificateOr($course));
    }

    /** Starts a course over within its six months: progress resets, nothing is paid again. */
    public function retake(string $id): void
    {
        $course = $this->accessibleCourse((int) $id);
        $userId = (int) $this->user['id'];
        if (!Authz::isStudent($this->user) || !CourseEnrollment::isEnrolled($userId, (int) $course['id'])) {
            flashError('Enrol for this course first.');
            redirect('/account/courses/' . $course['id']);
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/courses/' . $course['id']);
        }
        if (!CourseRetake::restart($userId, (int) $course['id'])) {
            flashError('Your ' . CourseRetake::RETAKE_MONTHS . ' months on this course have ended. Enrol and pay again to retake it.');
            redirect('/account/courses/' . $course['id']);
        }
        flashSuccess('Course reset. Start again from the first module — your certificate stays.');
        redirect('/account/courses/' . $course['id']);
    }

    /** The last quiz / final exam result, shown once inside its own section. */
    private function takeQuizResult(): ?array
    {
        $result = $_SESSION['quiz_result'] ?? null;
        unset($_SESSION['quiz_result']);
        return is_array($result) ? $result : null;
    }

    /** The course's certificate if it gives one and it's ready, else the course page. */
    private function certificateOr(array $course): string
    {
        foreach (Course::certifiableCompletedByLearner($this->user) as $done) {
            if ((int) $done['id'] === (int) $course['id']) {
                return '/account/certificate?course=' . (int) $course['id'];
            }
        }
        return '/account/courses/' . (int) $course['id'] . '?lesson=final#lesson';
    }

    /** @return array{0: array, 1: array, 2: array} the course, the module (with unlock + payment state), and all modules */
    private function studentModule(int $courseId, int $moduleId): array
    {
        $course = $this->accessibleCourse($courseId);
        $userId = (int) $this->user['id'];
        if (!Authz::isStudent($this->user) || !CourseEnrollment::isEnrolled($userId, (int) $course['id'])) {
            flashError('Enrol for this course first.');
            redirect('/account/courses/' . $course['id']);
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/courses/' . $course['id']);
        }
        $modules = ModuleAccess::annotate($userId, $course, CourseModule::withUnlockState(
            CourseModule::forCourse((int) $course['id']),
            CourseModuleProgress::forUserCourse($userId, (int) $course['id'])
        ));
        foreach ($modules as $module) {
            if ((int) $module['id'] === $moduleId) {
                return [$course, $module, $modules];
            }
        }
        flashError('That module was not found.');
        redirect('/account/courses/' . $course['id']);
    }

    public function submitFinalExam(string $id): void
    {
        $course = $this->accessibleCourse((int) $id);
        if (!Authz::isStudent($this->user)) {
            flashError('Only students take the final exam.');
            redirect('/account/courses/' . $course['id']);
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/courses/' . $course['id'] . '#final-exam');
        }
        if (!CourseEnrollment::isEnrolled((int) $this->user['id'], (int) $course['id'])) {
            flashError('Enroll for this course before taking the final exam.');
            redirect('/account/courses/' . $course['id']);
        }
        if (!Course::modulesCompletedByUser((int) $course['id'], (int) $this->user['id'])) {
            flashError('Pass every module before taking the final exam.');
            redirect('/account/courses/' . $course['id'] . '#final-exam');
        }
        if (empty($course['final_exam_questions'])) {
            flashError('The tutor has not published a final exam for this course yet.');
            redirect('/account/courses/' . $course['id'] . '#final-exam');
        }
        if (Course::finalExamPassedByUser((int) $course['id'], (int) $this->user['id'])) {
            flashError('You already passed this exam. A passed exam can\'t be taken again.');
            redirect($this->certificateOr($course));
        }
        if (!Course::feeSettledByUser((int) $course['id'], (int) $this->user['id'])) {
            flashError('Clear your balance for this course before the final exam.');
            redirect('/account/courses/' . $course['id'] . '?lesson=final#lesson');
        }

        $posted = Request::post('answers', []);
        if (!is_array($posted)) {
            $posted = [];
        }
        // Marked against this student's own paper (random questions, shuffled choices).
        $paper = Course::finalPaper($course, (int) $this->user['id'], CourseFinalExamProgress::findForUserCourse((int) $this->user['id'], (int) $course['id']));
        $result = Course::gradeFinalExam($course, $posted, $paper);
        CourseFinalExamProgress::saveAttempt([
            'user_id' => (int) $this->user['id'],
            'course_id' => (int) $course['id'],
            'score' => $result['score'],
            'passed' => $result['passed'],
            'answers' => $posted,
        ]);

        if ($result['passed']) {
            Course::isCompletedByUser((int) $course['id'], (int) $this->user['id']); // records the completion
        }
        $_SESSION['quiz_result'] = [
            'key' => 'final',
            'passed' => $result['passed'],
            'message' => $result['passed']
                ? 'You scored ' . $result['score'] . '%. Congratulations — you completed ' . $course['title'] . '.'
                : 'You scored ' . $result['score'] . '%. You need ' . Course::FINAL_PASS_PERCENT . '% to pass the final exam. Try again.',
        ];
        redirect('/account/courses/' . $course['id'] . '?lesson=final#lesson');
    }

    private function persist(?int $id): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please resubmit the form.');
            redirect($id ? '/account/courses/' . $id . '/edit' : '/account/courses/create');
        }

        $formId = (int) Request::post('form_id', 0);
        if ($id && $formId < 1) {
            $formId = (int) (Course::find($id)['form_id'] ?? 0);
        }
        if ($formId < 1) {
            $available = Form::forRole((int) $this->user['role_id'], true, 'course');
            $formId = (int) ($available[0]['id'] ?? 0);
        }
        $form = Form::find($formId);
        if (!$form || ($form['purpose'] ?? '') !== 'course' || empty($form['is_active']) || !in_array((int) $this->user['role_id'], $form['role_ids'], true)) {
            flashError('That course form is not assigned to your role.');
            redirect('/account/courses/create');
        }

        $fields = array_values(array_filter(FormField::forForm($formId), static fn(array $f): bool => !in_array($f['field_type'] ?? '', ['branches', 'branch_select'], true)));
        $posted = Request::post('answers', []);
        if (!is_array($posted)) {
            $posted = [];
        }
        $existing = $id ? (Course::find($id)['answers'] ?? []) : [];
        $collected = FormAnswerService::collect($fields, $posted, is_array($existing) ? $existing : [], $this->user);
        if ($collected['errors']) {
            flashError(implode(' ', $collected['errors']));
            redirect($id ? '/account/courses/' . $id . '/edit' : '/account/courses/create');
        }

        $mapped = $this->mapCourseAnswers($collected['answers'], $fields);
        if ($mapped['errors']) {
            flashError(implode(' ', $mapped['errors']));
            redirect($id ? '/account/courses/' . $id . '/edit' : '/account/courses/create');
        }

        $payload = $mapped['payload'];
        $payload['trainer_user_id'] = (int) $this->user['id'];
        $payload['form_id'] = $formId;
        $payload['answers'] = $collected['answers'];
        $payload['is_published'] = Request::post('is_published', '1') === '1';
        $payload['visibility'] = Course::normalizeVisibility((string) Request::post('visibility', Course::VISIBILITY_STRICT));
        $payload['enrollment_fee_ksh'] = max(0, (float) Request::post('enrollment_fee_ksh', 0));
        $payload['requires_attachment'] = Request::post('requires_attachment') === '1';
        $payload['certificate_enabled'] = Request::post('certificate_enabled') === '1';
        $payload['requires_full_registration'] = Request::post('requires_full_registration') === '1';
        $payload['introduction_title'] = trim((string) Request::post('introduction_title', '')) ?: null;
        $payload['introduction_description'] = trim((string) Request::post('introduction_description', '')) ?: null;
        $payload['introduction_video_source'] = Request::post('introduction_video_source') === 'upload' ? 'upload' : 'youtube';
        $payload['introduction_video_path'] = null;
        $payload['introduction_video_url'] = null;

        $coverUpload = $_FILES['course_cover'] ?? null;
        if (is_array($coverUpload) && ($coverUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $payload['cover_image'] = UploadService::store($coverUpload, 'courses');
            } catch (UploadException $e) {
                flashError('Cover image: ' . $e->getMessage());
                redirect($id ? '/account/courses/' . $id . '/edit' : '/account/courses/create');
            }
        }
        $hasCover = static fn($path): bool => is_string($path) && preg_match('/\.(jpe?g|png|webp|gif)$/i', $path);
        if (!$hasCover($payload['cover_image']) && !($id && $hasCover(Course::find($id)['cover_image'] ?? null))) {
            flashError('Add a cover image — it is shown on the course card and the homepage.');
            redirect($id ? '/account/courses/' . $id . '/edit' : '/account/courses/create');
        }

        $introErrors = $this->collectIntroductionVideo($payload, $id ? Course::find($id) : null);
        if ($introErrors) {
            flashError(implode(' ', $introErrors));
            redirect($id ? '/account/courses/' . $id . '/edit' : '/account/courses/create');
        }

        if ($id) {
            $existingCourse = Course::find($id);
            if (!$payload['cover_image'] && !empty($existingCourse['cover_image'])) {
                $payload['cover_image'] = $existingCourse['cover_image'];
            }
            if (!$payload['materials'] && !empty($existingCourse['materials'])) {
                $payload['materials'] = $existingCourse['materials'];
            }
            Course::update($id, $payload);
            if (($existingCourse['approval_status'] ?? '') === 'rejected') {
                Course::setApproval((int) $id, 'pending', null); // back in Super Admin's queue
            }
            flashSuccess('Course updated. Use Edit modules to add videos, resources, quizzes, and the final exam.');
            redirect('/account/courses/' . $id);
        }

        $newId = Course::create($payload);
        flashSuccess('Course created. Add modules, then set the final exam. Each module can include a video, resources, and a quiz that must be passed to open the next one.');
        redirect('/account/courses/' . $newId);
    }

    private function persistModule(array $course, ?array $module): void
    {
        $redirect = '/account/courses/' . (int) $course['id'];
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect($redirect);
        }

        $title = trim((string) Request::post('title', ''));
        $description = trim((string) Request::post('description', ''));
        $summary = trim((string) Request::post('summary', ''));
        $notes = trim((string) Request::post('notes', ''));
        $duration = CourseModule::normalizeDuration(Request::post('duration_minutes', 10));
        $source = Request::post('video_source') === 'youtube' ? 'youtube' : 'upload';
        $youtubeUrl = trim((string) Request::post('video_url', ''));
        $errors = [];
        if ($title === '') {
            $errors[] = 'Module title is required.';
        }

        $videoPath = $module['video_path'] ?? null;
        $videoUrl = $module['video_url'] ?? null;
        if ($source === 'youtube') {
            if (!CourseModule::youtubeId($youtubeUrl)) {
                $errors[] = 'Enter a valid YouTube URL. Learners will watch it here and will not see a copyable link.';
            } else {
                $videoUrl = $youtubeUrl;
                $videoPath = null;
            }
        } else {
            $file = Request::file('video');
            if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                try {
                    $videoPath = UploadService::storeVideo($file, 'course-videos');
                    $videoUrl = null;
                } catch (UploadException $e) {
                    $errors[] = $e->getMessage();
                }
            } elseif (!$videoPath) {
                $errors[] = 'Upload a module video.';
            }
        }

        $materials = is_array($module['materials'] ?? null) ? $module['materials'] : [];
        foreach (Request::fileList('materials') as $file) {
            try {
                $materials[] = UploadService::storeDocument($file, 'course-materials');
            } catch (UploadException $e) {
                $errors[] = $e->getMessage();
            }
        }

        // The quiz is optional: only a module set to have one needs questions.
        $hasQuiz = Request::post('has_quiz', '') === '1';
        $quiz = $hasQuiz ? CourseModule::normalizeQuestions($this->postedQuestions()) : [];
        $passPercent = CourseModule::normalizePassPercent(Request::post('pass_percent', 80));
        if ($hasQuiz && !$quiz) {
            $errors[] = 'Add at least one valid quiz question (choice questions need two answer choices), or untick "This module has a quiz".';
        }

        if ($errors) {
            flashError(implode(' ', $errors));
            redirect($module ? $redirect . '?module=' . (int) $module['id'] : $redirect);
        }

        $payload = [
            'course_id' => (int) $course['id'],
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'summary' => $summary !== '' ? $summary : null,
            'notes' => $notes !== '' ? $notes : null,
            'duration_minutes' => $duration,
            'video_source' => $source,
            'video_path' => $videoPath,
            'video_url' => $videoUrl,
            'materials' => $materials,
            'quiz_questions' => $quiz,
            'pass_percent' => $passPercent,
        ];

        if ($module) {
            CourseModule::update((int) $module['id'], $payload);
            flashSuccess('Module updated.');
            redirect($redirect . '#topic-' . (int) $module['id']);
        }

        $newId = CourseModule::create($payload);
        flashSuccess('Module added.');
        redirect($redirect . '#topic-' . $newId);
    }

    private function postedQuestions(): array
    {
        return $this->postedQuestionsFrom('questions');
    }

    private function postedQuestionsFrom(string $key): array
    {
        $raw = Request::post($key, []);
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $accepted = $row['accepted_answers'] ?? '';
            if (is_array($accepted)) {
                $accepted = implode("\n", array_map('strval', $accepted));
            }
            $out[] = [
                'question' => $row['text'] ?? ($row['question'] ?? ''),
                'type' => $row['type'] ?? 'single_choice',
                'options' => $row['options'] ?? [],
                'correct' => $row['correct'] ?? 0,
                'accepted_answers' => $accepted,
            ];
        }
        return $out;
    }

    private function mapCourseAnswers(array $answers, array $fields): array
    {
        $title = '';
        $description = '';
        $categoryIds = [];
        $cover = null;
        $materials = [];
        foreach ($fields as $field) {
            $type = $field['field_type'] ?? 'text';
            $key = $field['field_key'] ?? '';
            $value = $answers[$key] ?? null;
            if ($type === 'category' && !$categoryIds) {
                $categoryIds = is_array($value) ? array_values(array_unique(array_map('intval', $value))) : array_filter([(int) $value]);
            }
            if (in_array($key, ['course_title', 'title'], true) && is_string($value) && trim($value) !== '') {
                $title = trim($value);
            } elseif ($type === 'text' && $title === '' && is_string($value) && trim($value) !== '') {
                $title = trim($value);
            }
            if (in_array($key, ['course_description', 'description'], true) && is_string($value)) {
                $description = trim($value);
            } elseif ($type === 'paragraph' && $description === '' && is_string($value)) {
                $description = trim($value);
            }
            // The cover is the image field, or any upload the form calls a cover
            // (a "Cover image" built as a plain File field still counts).
            $isCoverField = in_array($key, ['course_cover', 'cover', 'cover_image'], true)
                || stripos((string) ($field['label'] ?? ''), 'cover') !== false;
            if (($type === 'image' || ($type === 'file' && $isCoverField)) && is_string($value) && $value !== '') {
                if ($cover === null || $isCoverField) {
                    $cover = $value;
                }
                continue;
            }
            if ($type === 'files' && is_array($value)) {
                $materials = array_merge($materials, $value);
            }
            if ($type === 'file' && is_string($value) && $value !== '') {
                $materials[] = $value;
            }
        }

        $errors = [];
        if ($title === '') {
            $errors[] = 'Give the course a title.';
        }
        // The tutor's own organisation is authoritative for which org the
        // course belongs to — the category just has to be one that
        // organisation actually offers.
        $approved = Authz::approvedOrganisationIds($this->user);
        $organisationId = !empty($this->user['organisation_id']) && in_array((int) $this->user['organisation_id'], $approved, true)
            ? (int) $this->user['organisation_id']
            : (int) ($approved[0] ?? 0);
        $validCategoryIds = [];
        if ($organisationId < 1) {
            $errors[] = 'An organisation must approve you as a tutor before you can create courses.';
        } elseif (!$categoryIds) {
            $errors[] = 'Choose at least one category. Ask your organisation admin to add one from Categories if none are listed.';
        } else {
            foreach ($categoryIds as $categoryId) {
                $category = OrganisationCategory::find($categoryId);
                if ($category && (int) $category['organisation_id'] === $organisationId) {
                    $validCategoryIds[] = $categoryId;
                }
            }
            if (!$validCategoryIds) {
                $errors[] = 'Choose a category that belongs to your organisation.';
            }
        }

        return [
            'errors' => $errors,
            'payload' => [
                'organisation_id' => $organisationId,
                'category_id' => $validCategoryIds[0] ?? 0,
                'category_ids' => $validCategoryIds,
                'title' => $title,
                'description' => $description !== '' ? $description : null,
                'cover_image' => $cover,
                'materials' => array_values($materials),
            ],
        ];
    }

    private function collectIntroductionVideo(array &$payload, ?array $existing): array
    {
        $errors = [];
        $source = $payload['introduction_video_source'] ?? 'youtube';
        $payload['introduction_video_path'] = $existing['introduction_video_path'] ?? null;
        $payload['introduction_video_url'] = $existing['introduction_video_url'] ?? null;

        if ($source === 'youtube') {
            $url = trim((string) Request::post('introduction_video_url', ''));
            if ($url !== '' && !CourseModule::youtubeId($url)) {
                $errors[] = 'Enter a valid YouTube URL for the course introduction.';
            }
            $payload['introduction_video_url'] = $url !== '' ? $url : null;
            $payload['introduction_video_path'] = null;
            return $errors;
        }

        $file = Request::file('introduction_video');
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $payload['introduction_video_path'] = UploadService::storeVideo($file, 'course-introductions');
                $payload['introduction_video_url'] = null;
            } catch (UploadException $e) {
                $errors[] = $e->getMessage();
            }
        } elseif (!$payload['introduction_video_path']) {
            $payload['introduction_video_path'] = null;
        }

        return $errors;
    }

    private function paymentProvider(): string
    {
        $provider = StoreSetting::get('course_payment_provider', 'mpesa') ?: 'mpesa';
        return in_array($provider, ['mpesa', 'stripe'], true) ? $provider : 'mpesa';
    }

    private function paymentsEnabled(): bool
    {
        return StoreSetting::get('course_payments_enabled', '0') === '1';
    }

    private function kshUsdRate(): float
    {
        $rate = (float) (StoreSetting::get('course_ksh_usd_rate', '130') ?: 130);
        return $rate > 0 ? $rate : 130;
    }

    private function courseForms(?array $course = null): array
    {
        $forms = Form::forRole((int) $this->user['role_id'], true, 'course');
        $withFields = [];
        foreach ($forms as $form) {
            // Branches have nothing to do with a course: those questions are left out.
            $form['fields'] = array_values(array_filter(
                FormField::forForm((int) $form['id']),
                static fn(array $f): bool => !in_array($f['field_type'] ?? '', ['branches', 'branch_select'], true)
            ));
            $form['answers'] = ($course && (int) ($course['form_id'] ?? 0) === (int) $form['id'])
                ? ($course['answers'] ?? [])
                : [];
            $withFields[] = $form;
        }
        return $withFields;
    }

    private function markEnrollment(array $courses): array
    {
        $ids = [];
        try {
            $ids = CourseEnrollment::idsForUser((int) $this->user['id']);
        } catch (\Throwable $e) {
            $ids = [];
        }
        $lookup = array_fill_keys($ids, true);
        foreach ($courses as &$course) {
            $course['is_enrolled'] = !empty($lookup[(int) $course['id']]);
            $course['is_completed'] = $course['is_enrolled'] && Course::isCompletedByUser((int) $course['id'], (int) $this->user['id']);
        }
        unset($course);
        return $courses;
    }

    private function requireViewer(): void
    {
        if (!Authz::canViewCourses($this->user)) {
            flashError('Courses are available for students, tutors, trainers, teachers, and organisation admins.');
            redirect('/account/dashboard');
        }
    }

    private function requireCreator(): void
    {
        if (!Authz::canCreateCourses($this->user)) {
            flashError('You can create courses after an organisation approves you as their tutor.');
            redirect('/account/courses');
        }
    }

    /** Students see the tutor of every course, so a tutor fills in what they see first. */
    private function requirePublicProfile(): void
    {
        if (($this->user['role_slug'] ?? '') !== 'trainer') {
            return;
        }
        $missing = \App\Models\User::missingPublicProfile($this->user);
        if ($missing) {
            flashError('Before creating a course, add your ' . implode(', ', $missing) . ' under Profile → What students see.');
            redirect('/account/profile#public-profile');
        }
    }

    private function requireFullRegistration(array $course): void
    {
        if (empty($course['requires_full_registration'])) {
            return;
        }
        if (!AccountRedirect::needsProfile($this->user)) {
            return;
        }
        flashError('Complete your full registration form before enrolling in this course.');
        redirect('/account/profile');
    }

    private function accessibleCourse(int $id): array
    {
        $course = Course::find($id);
        if (!$course || !Authz::canAccessCourse($this->user, $course)) {
            flashError('That course could not be found.');
            redirect('/account/courses');
        }
        return $course;
    }

    private function editableCourse(int $id): array
    {
        $course = $this->accessibleCourse($id);
        if (!Authz::canEditCourse($this->user, $course)) {
            flashError('Only the tutor who created this course can edit it.');
            redirect('/account/courses/' . $id);
        }
        return $course;
    }

    private function ownedModule(array $course, int $moduleId): array
    {
        $module = CourseModule::find($moduleId);
        if (!$module || (int) $module['course_id'] !== (int) $course['id']) {
            flashError('That module could not be found.');
            redirect('/account/courses/' . $course['id']);
        }
        return $module;
    }

    private function blankModule(): array
    {
        return [
            'title' => '',
            'description' => '',
            'summary' => '',
            'notes' => '',
            'duration_minutes' => 10,
            'video_source' => 'upload',
            'video_url' => '',
            'materials' => [],
            'quiz_questions' => [
                ['question' => '', 'type' => 'single_choice', 'options' => ['', ''], 'correct' => 0, 'accepted_answers' => []],
            ],
            'pass_percent' => 80,
        ];
    }
}
