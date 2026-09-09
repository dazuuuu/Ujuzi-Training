<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Core\Request;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseFinalExamProgress;
use App\Models\CourseModule;
use App\Models\CourseModuleProgress;
use App\Models\Form;
use App\Models\FormField;
use App\Models\OrganisationBranch;
use App\Models\OrganisationCategory;
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
                $courses = Course::forLearner(Authz::learnerOrganisationIds($this->user));
                $courses = $this->markEnrollment($courses);
            } catch (\Throwable $e) {
                $courses = [];
            }
            $orgIds = Authz::learnerOrganisationIds($this->user);
            $branches = [];
            try {
                foreach ($orgIds as $orgId) {
                    $branches = array_merge($branches, OrganisationBranch::forOrganisation($orgId));
                }
            } catch (\Throwable $e) {
                $branches = [];
            }
        } elseif (Authz::isOrganisationAdmin($this->user)) {
            $courses = Course::forOrganisation((int) $this->user['organisation_id']);
            $branches = [];
        } else {
            $courses = Course::forTrainer((int) $this->user['id']);
            $branches = [];
        }

        $this->render('account.courses.index', [
            'pageTitle' => 'Courses',
            'activeNav' => 'courses',
            'courses' => $courses,
            'branches' => $branches,
            'canCreate' => Authz::canCreateCourses($this->user),
            'isStudent' => Authz::isStudent($this->user),
        ]);
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
        if ($isStudent && $isEnrolled) {
            try {
                $progress = CourseModuleProgress::forUserCourse((int) $this->user['id'], (int) $course['id']);
                $modules = CourseModule::withUnlockState($modules, $progress);
                $modulesComplete = Course::modulesCompletedByUser((int) $course['id'], (int) $this->user['id']);
                $finalProgress = CourseFinalExamProgress::findForUserCourse((int) $this->user['id'], (int) $course['id']);
            } catch (\Throwable $e) {
                $modules = CourseModule::withUnlockState($modules, []);
            }
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

        CourseEnrollment::enroll((int) $this->user['id'], (int) $course['id']);
        flashSuccess('You are enrolled. Start with the first module.');
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
            flashError('Add at least one valid final exam question with two or more answer choices.');
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
        redirect('/account/courses/' . $course['id'] . '#topic-' . $module['id']);
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
        redirect('/account/courses/' . $course['id'] . '#final-exam');
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
        $duration = (int) Request::post('duration_minutes', 10);
        if (!in_array($duration, CourseModule::durations(), true)) {
            $duration = 10;
        }
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
                $errors[] = 'Upload a module video (about 10, 20, 30, or 60 minutes).';
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
            $errors[] = 'Add at least one valid module quiz question with two or more answer choices.';
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
            $out[] = [
                'question' => $row['text'] ?? ($row['question'] ?? ''),
                'options' => $row['options'] ?? [],
                'correct' => $row['correct'] ?? 0,
            ];
        }
        return $out;
    }

    private function mapCourseAnswers(array $answers, array $fields): array
    {
        $title = '';
        $description = '';
        $categoryId = 0;
        $cover = null;
        $materials = [];
        foreach ($fields as $field) {
            $type = $field['field_type'] ?? 'text';
            $key = $field['field_key'] ?? '';
            $value = $answers[$key] ?? null;
            if ($type === 'category' && !$categoryId) {
                $categoryId = (int) $value;
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
            if ($type === 'image' && is_string($value) && $value !== '') {
                $cover = $value;
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
        $category = $categoryId ? OrganisationCategory::find($categoryId) : null;
        $approved = Authz::approvedOrganisationIds($this->user);
        if (!$category || !in_array((int) $category['organisation_id'], $approved, true)) {
            $errors[] = 'Pick a category from an organisation that has approved you.';
        }

        return [
            'errors' => $errors,
            'payload' => [
                'organisation_id' => $category ? (int) $category['organisation_id'] : 0,
                'category_id' => $categoryId,
                'title' => $title,
                'description' => $description !== '' ? $description : null,
                'cover_image' => $cover,
                'materials' => array_values($materials),
            ],
        ];
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
                ['question' => '', 'options' => ['', '', '', ''], 'correct' => 0],
            ],
            'pass_percent' => 80,
        ];
    }
}
