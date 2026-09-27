<?php

namespace App\Controllers\Account;

use App\Core\AccountRedirect;
use App\Core\Authz;
use App\Core\Request;
use App\Models\AttachmentApplication;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Services\WalletException;
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
        $this->persist(null);
    }

    public function show(string $id): void
    {
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

        // Enrolled students learn in the lesson view: the chosen lesson in the
        // middle, the course's modules down the side. Tutors and admins keep
        // the full page with the editing tools.
        if ($isStudent && $isEnrolled && !$canEdit) {
            $paidKsh = 0.0;
            try {
                $paidKsh = WalletService::paidForCourse((int) $this->user['id'], (int) $course['id']);
            } catch (\Throwable $e) {
                $paidKsh = 0.0;
            }
            $this->render('account.courses.learn', [
                'pageTitle' => $course['title'],
                'activeNav' => 'courses',
                'course' => $course,
                'modules' => $modules,
                'finalProgress' => $finalProgress,
                'modulesComplete' => $modulesComplete,
                'lesson' => (string) Request::query('lesson', ''),
                'feeKsh' => (float) ($course['enrollment_fee_ksh'] ?? 0),
                'paidKsh' => $paidKsh,
                'settled' => Course::feeSettledByUser((int) $course['id'], (int) $this->user['id']),
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

        $this->render('account.courses.checkout', [
            'pageTitle' => 'Course payment',
            'activeNav' => 'courses',
            'course' => $course,
            'paidKsh' => $paid,
            'balanceKsh' => WalletService::balanceKsh($userId),
            'minimumKsh' => WalletService::minimumPayment($course, $paid),
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

        try {
            WalletService::payCourse((int) $this->user['id'], $course, (float) Request::post('amount_ksh', 0));
        } catch (WalletException $e) {
            flashError($e->getMessage());
            redirect('/account/courses/' . $course['id'] . '/checkout');
        }

        $left = (float) $course['enrollment_fee_ksh'] - WalletService::paidForCourse((int) $this->user['id'], (int) $course['id']);
        flashSuccess($left > 0
            ? 'Payment received. You are enrolled. Balance left: Ksh ' . number_format($left, 2) . '.'
            : 'Course fully paid. You are enrolled.');
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

        $questions = CourseModule::normalizeQuestions($this->postedQuestionsFrom('final_questions'));
        $passPercent = CourseModule::normalizePassPercent(Request::post('final_pass_percent', 80));
        if (!$questions) {
            flashError('Add at least one valid final exam question. Choice questions need at least two answer choices.');
            redirect('/account/courses/' . $course['id'] . '#final-exam');
        }

        Course::updateFinalExam((int) $course['id'], $questions, $passPercent);
        flashSuccess('Final exam saved. Students unlock it after passing every module quiz.');
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
            flashError('Pass the previous module quiz before opening this one.');
            redirect('/account/courses/' . $course['id']);
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

        if ($result['passed']) {
            $message = 'You scored ' . $result['score'] . '%. The next module is now open.';
            if (Course::modulesCompletedByUser((int) $course['id'], (int) $this->user['id'])) {
                $message = 'You scored ' . $result['score'] . '%. All modules are complete. Take the final exam to add this skill to your certificate.';
            }
            flashSuccess($message);
        } else {
            flashError('You scored ' . $result['score'] . '%. You need ' . (int) $current['pass_percent'] . '% to unlock the next module. Try again.');
        }
        // After a pass, go straight to the next module (or the final exam); after a fail, stay to retry.
        $next = 'm' . $module['id'];
        if ($result['passed']) {
            $ids = array_map(static fn(array $m): int => (int) $m['id'], CourseModule::forCourse((int) $course['id']));
            $at = array_search((int) $module['id'], $ids, true);
            $next = ($at !== false && isset($ids[$at + 1])) ? 'm' . $ids[$at + 1] : 'final';
        }
        redirect('/account/courses/' . $course['id'] . '?lesson=' . $next . '#lesson');
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
            flashError('Pass every module quiz before taking the final exam.');
            redirect('/account/courses/' . $course['id'] . '#final-exam');
        }
        if (empty($course['final_exam_questions'])) {
            flashError('The tutor has not published a final exam for this course yet.');
            redirect('/account/courses/' . $course['id'] . '#final-exam');
        }

        $posted = Request::post('answers', []);
        if (!is_array($posted)) {
            $posted = [];
        }
        $result = Course::gradeFinalExam($course, $posted);
        CourseFinalExamProgress::saveAttempt([
            'user_id' => (int) $this->user['id'],
            'course_id' => (int) $course['id'],
            'score' => $result['score'],
            'passed' => $result['passed'],
            'answers' => $posted,
        ]);

        if ($result['passed']) {
            flashSuccess('You scored ' . $result['score'] . '%. This course skill is now listed on your certificate.');
        } else {
            flashError('You scored ' . $result['score'] . '%. You need ' . (int) $course['final_pass_percent'] . '% to pass the final exam. Try again.');
        }
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

        $fields = FormField::forForm($formId);
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

        $quiz = CourseModule::normalizeQuestions($this->postedQuestions());
        $passPercent = CourseModule::normalizePassPercent(Request::post('pass_percent', 80));
        if (!$quiz) {
            $errors[] = 'Add at least one valid module quiz question. Choice questions need at least two answer choices.';
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
            $form['fields'] = FormField::forForm((int) $form['id']);
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
