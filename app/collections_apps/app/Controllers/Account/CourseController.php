<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Core\Request;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Form;
use App\Models\FormField;
use App\Models\OrganisationCategory;
use App\Services\FormAnswerService;
use App\Services\UploadException;
use App\Services\UploadService;

class CourseController extends BaseAccountController
{
    public function index(): void
    {
        $this->requireViewer();
        $courses = Authz::isOrganisationAdmin($this->user)
            ? Course::forOrganisation((int) $this->user['organisation_id'])
            : Course::forTrainer((int) $this->user['id']);

        $this->render('account.courses.index', [
            'pageTitle' => 'Courses',
            'activeNav' => 'courses',
            'courses' => $courses,
            'canCreate' => Authz::canCreateCourses($this->user),
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
        if ($editId && Authz::canEditCourse($this->user, $course)) {
            foreach ($modules as $module) {
                if ((int) $module['id'] === $editId) {
                    $editingModule = $module;
                    break;
                }
            }
        }

        $this->render('account.courses.show', [
            'pageTitle' => $course['title'],
            'activeNav' => 'courses',
            'course' => $course,
            'modules' => $modules,
            'canEdit' => Authz::canEditCourse($this->user, $course),
            'durations' => CourseModule::durations(),
            'moduleForm' => $editingModule ?: $this->blankModule(),
            'editingModule' => $editingModule,
        ]);
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

        if ($id) {
            $existingCourse = Course::find($id);
            if (!$payload['cover_image'] && !empty($existingCourse['cover_image'])) {
                $payload['cover_image'] = $existingCourse['cover_image'];
            }
            if (!$payload['materials'] && !empty($existingCourse['materials'])) {
                $payload['materials'] = $existingCourse['materials'];
            }
            Course::update($id, $payload);
            flashSuccess('Course updated.');
            redirect('/account/courses/' . $id);
        }

        $newId = Course::create($payload);
        flashSuccess('Course created. Add modules — each module is a video lesson.');
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

        if ($errors) {
            flashError(implode(' ', $errors));
            redirect($redirect);
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
        ];

        if ($module) {
            CourseModule::update((int) $module['id'], $payload);
            flashSuccess('Module updated.');
        } else {
            CourseModule::create($payload);
            flashSuccess('Module added.');
        }
        redirect($redirect);
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

    private function requireViewer(): void
    {
        if (!Authz::canViewCourses($this->user)) {
            flashError('Courses are available after an organisation approves you as a tutor, or if you are an organisation admin.');
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
        ];
    }
}
