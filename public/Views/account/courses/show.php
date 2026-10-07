<?php
$course = $course ?? [];
$modules = $modules ?? [];
$canEdit = !empty($canEdit);
$isStudent = !empty($isStudent);
$isEnrolled = !empty($isEnrolled);
$editingModule = $editingModule ?? null;
$moduleForm = $moduleForm ?? ['title' => '', 'description' => '', 'summary' => '', 'notes' => '', 'duration_minutes' => 10, 'video_source' => 'upload', 'video_url' => '', 'quiz_questions' => [], 'pass_percent' => 80];
if (empty($moduleForm['quiz_questions'])) {
    $moduleForm['quiz_questions'] = [['question' => '', 'type' => 'single_choice', 'options' => ['', ''], 'correct' => 0, 'accepted_answers' => []]];
}
$finalQuestions = $course['final_exam_questions'] ?? [];
if (!$finalQuestions) {
    $finalQuestions = [['question' => '', 'type' => 'single_choice', 'options' => ['', ''], 'correct' => 0, 'accepted_answers' => []]];
}
$finalPassPercent = (int) ($course['final_pass_percent'] ?? 80);
$finalProgress = $finalProgress ?? null;
$modulesComplete = !empty($modulesComplete);
$attachmentApplication = $attachmentApplication ?? null;
$paymentsEnabled = !empty($paymentsEnabled);
$durations = $durations ?? \App\Models\CourseModule::durations();
$materials = is_array($course['materials'] ?? null) ? $course['materials'] : [];
$fileHref = static function ($file): string {
    $path = is_array($file) ? (string) ($file['url'] ?? $file['path'] ?? '') : (string) $file;
    return imageUrl($path);
};
$fileName = static function ($file): string {
    if (is_array($file) && !empty($file['name'])) {
        return (string) $file['name'];
    }
    $path = is_array($file) ? (string) ($file['url'] ?? $file['path'] ?? '') : (string) $file;
    return $path !== '' ? basename($path) : 'Download';
};
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section class="rounded-xl border bg-white p-6 shadow-sm space-y-3" style="border-color:var(--ke-line)">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Course</p>
        <h1 class="mt-2 font-serif-heading text-3xl font-bold"><?= e($course['title'] ?? 'Course') ?></h1>
        <p class="mt-1 text-sm font-semibold" style="color:var(--ke-muted)">
          <?= e($course['category_name'] ?? '') ?> · <?= e($course['organisation_name'] ?? '') ?>
          <?php $courseDuration = \App\Models\FormField::formatAnswer(['field_type' => 'duration'], (is_array($course['answers'] ?? null) ? $course['answers']['course_duration'] ?? null : null)); ?>
          <?php if ($courseDuration !== '—'): ?> · <?= e($courseDuration) ?><?php endif; ?>
        </p>
        <p class="mt-2 text-lg font-black" style="color:var(--ke-green)">Enrollment fee: Ksh <?= number_format((float) ($course['enrollment_fee_ksh'] ?? 0), 2) ?></p>
        <div class="mt-2 flex flex-wrap gap-2">
          <span class="rounded-full border border-neutral-200 px-3 py-1 text-[11px] font-black uppercase text-neutral-700"><?= !empty($course['certificate_enabled']) ? 'Certificate enabled' : 'No certificate' ?></span>
          <?php if (!empty($course['requires_attachment'])): ?>
            <span class="rounded-full border px-3 py-1 text-[11px] font-black uppercase" style="border-color:var(--ke-green);color:var(--ke-green)">Attachment required</span>
          <?php endif; ?>
        </div>
        <?php if (!empty($course['first_name']) || !empty($course['email'])): ?>
          <p class="text-xs font-semibold text-neutral-600">Tutor: <?= e(trim(($course['first_name'] ?? '') . ' ' . ($course['last_name'] ?? '')) ?: ($course['email'] ?? '')) ?></p>
        <?php endif; ?>
        <p class="text-[11px] font-black uppercase visibility-badge <?= ($course['visibility'] ?? 'strict') === 'global' ? 'is-global' : 'is-strict' ?>">
          <?= ($course['visibility'] ?? 'strict') === 'global' ? 'Global — all students' : 'Strict — this organisation' ?>
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <?php if ($canEdit): ?>
          <a href="<?= url('/account/courses/' . (int) $course['id'] . '/edit') ?>" class="btn-secondary">Edit details</a>
          <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/delete') ?>" onsubmit="return confirm('Delete this course and its modules?');">
            <?= csrfField() ?>
            <button type="submit" class="btn-danger">Delete</button>
          </form>
        <?php endif; ?>
        <?php if ($canEdit && isset($course['approval_status']) && $course['approval_status'] !== 'approved'): ?>
    <section class="rounded-xl border p-4 text-sm font-semibold" role="status" style="<?= $course['approval_status'] === 'rejected' ? 'border-color:#fecaca;background:#fef2f2;color:var(--ke-red)' : 'border-color:#fde68a;background:#fffbeb;color:#92400e' ?>">
      <?php if ($course['approval_status'] === 'rejected'): ?>
        Super Admin sent this course back<?= !empty($course['approval_note']) ? ': “' . e($course['approval_note']) . '”' : '.' ?> Edit the course to make the changes — saving it sends it for approval again.
      <?php else: ?>
        Waiting for Super Admin's approval. Students will see this course once it is approved.
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php if ($isStudent && !$isEnrolled): ?>
          <?php if ((float) ($course['enrollment_fee_ksh'] ?? 0) > 0): ?>
            <a href="<?= url('/account/courses/' . (int) $course['id'] . '/checkout') ?>" class="btn-primary">Enrol</a>
          <?php else: ?>
            <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/enroll') ?>">
              <?= csrfField() ?>
              <button type="submit" class="btn-primary"><?= (float) ($course['enrollment_fee_ksh'] ?? 0) > 0 ? 'Enroll for testing' : 'Enroll for course' ?></button>
            </form>
          <?php endif; ?>
        <?php elseif ($isStudent): ?>
          <span class="btn-secondary" style="padding:0.45rem 0.8rem;">Enrolled</span>
          <?php
            $feeKsh = (float) ($course['enrollment_fee_ksh'] ?? 0);
            $paidKshShow = $feeKsh > 0 ? \App\Services\WalletService::paidForCourse((int) $currentUser['id'], (int) $course['id']) : 0;
          ?>
          <?php if ($feeKsh > 0 && $paidKshShow < $feeKsh): ?>
            <a href="<?= url('/account/courses/' . (int) $course['id'] . '/checkout') ?>" class="btn-primary">Pay balance (Ksh <?= number_format($feeKsh - $paidKshShow, 2) ?>)</a>
          <?php endif; ?>
        <?php endif; ?>
        <a href="<?= url('/account/courses') ?>" class="btn-secondary">Back</a>
      </div>
    </div>
    <?php if (!empty($course['cover_image'])): ?>
      <img class="course-cover" src="<?= e(imageUrl($course['cover_image'])) ?>" alt="">
    <?php endif; ?>
    <?php if (!empty($course['description'])): ?>
      <p class="text-sm font-medium text-neutral-700"><?= nl2br(e($course['description'])) ?></p>
    <?php endif; ?>
    <?php if ($materials): ?>
      <div>
        <h2 class="font-serif-heading text-lg font-bold">Course materials</h2>
        <ul class="mt-2 space-y-1 text-sm font-semibold">
          <?php foreach ($materials as $file): ?>
            <li><a href="<?= e($fileHref($file)) ?>" target="_blank" rel="noopener" style="color:var(--ke-green)"><?= e($fileName($file)) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </section>

  <?php if ($isStudent && !$isEnrolled): ?>
    <section class="rounded-xl border bg-white p-6 shadow-sm space-y-3" style="border-color:var(--ke-line)">
      <h2 class="font-serif-heading text-lg font-bold">Enrollment required</h2>
      <p class="text-sm font-medium" style="color:var(--ke-muted)">Enroll for this course to open the modules, quizzes, and final exam.</p>
      <?php if ($paymentsEnabled && (float) ($course['enrollment_fee_ksh'] ?? 0) > 0): ?>
        <a href="<?= url('/account/courses/' . (int) $course['id'] . '/checkout') ?>" class="btn-primary">Enrol and see the payment plan</a>
      <?php else: ?>
        <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/enroll') ?>">
          <?= csrfField() ?>
          <button type="submit" class="btn-primary"><?= (float) ($course['enrollment_fee_ksh'] ?? 0) > 0 ? 'Enroll for testing' : 'Enroll now' ?></button>
        </form>
      <?php endif; ?>
    </section>
  <?php else: ?>
  <?php if (!empty($course['introduction_title']) || !empty($course['introduction_description']) || !empty($course['introduction_video_path']) || !empty($course['introduction_embed_url'])): ?>
    <section class="rounded-xl border bg-white p-6 shadow-sm space-y-4" style="border-color:var(--ke-line)">
      <div>
        <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Introduction</p>
        <h2 class="mt-2 font-serif-heading text-2xl font-bold"><?= e($course['introduction_title'] ?: 'Welcome to the course') ?></h2>
      </div>
      <?php if (!empty($course['introduction_description'])): ?>
        <p class="text-sm font-medium text-neutral-700"><?= nl2br(e($course['introduction_description'])) ?></p>
      <?php endif; ?>
      <?php if (!empty($course['introduction_embed_url']) || !empty($course['introduction_video_path'])): ?>
        <div class="module-player" oncontextmenu="return false;">
          <?php if (($course['introduction_video_source'] ?? '') === 'youtube' && !empty($course['introduction_embed_url'])): ?>
            <iframe
              src="<?= e($course['introduction_embed_url']) ?>"
              title="<?= e($course['introduction_title'] ?: $course['title']) ?>"
              allow="accelerometer; autoplay; encrypted-media; gyroscope"
              referrerpolicy="strict-origin-when-cross-origin"
            ></iframe>
          <?php elseif (!empty($course['introduction_video_path'])): ?>
            <video controls controlsList="nodownload noremoteplayback noplaybackrate" disablePictureInPicture playsinline oncontextmenu="return false;">
              <source src="<?= e(imageUrl($course['introduction_video_path'])) ?>">
            </video>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>
  <section class="rounded-xl border bg-white p-6 shadow-sm space-y-4" style="border-color:var(--ke-line)">
    <div>
      <h2 class="font-serif-heading text-lg font-bold">Modules</h2>
      <p class="mt-1 text-sm font-medium" style="color:var(--ke-muted)">Each module can include an overview, description, resources, video, and an optional quiz. Students pass each module (and pay for it) to open the next.</p>
    </div>
    <?php if (!$modules): ?>
      <p class="text-sm font-bold" style="color:var(--ke-muted)">No modules yet.</p>
    <?php endif; ?>
    <?php foreach ($modules as $index => $module):
      $moduleMaterials = is_array($module['materials'] ?? null) ? $module['materials'] : [];
      $locked = $isStudent && empty($module['is_unlocked']);
      $questions = $module['quiz_questions'] ?? [];
    ?>
      <article class="module-card <?= $locked ? 'is-locked' : '' ?>" id="topic-<?= (int) $module['id'] ?>">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h3 class="font-serif-heading text-xl font-bold"><?= ($index + 1) ?>. <?= e($module['title']) ?></h3>
          <p class="text-xs font-black uppercase tracking-wider" style="color:var(--ke-muted)"><?= e(\App\Models\CourseModule::formatDuration((int) $module['duration_minutes'])) ?></p>
        </div>
        <?php if ($locked): ?>
          <div class="module-lock">
            <p class="text-sm font-bold">Locked. Pass the quiz on the previous module to continue.</p>
          </div>
        <?php else: ?>
          <?php if (!empty($module['summary'])): ?>
            <p class="text-sm font-medium"><?= nl2br(e($module['summary'])) ?></p>
          <?php endif; ?>
          <?php if (!empty($module['description'])): ?>
            <p class="text-sm font-medium text-neutral-700"><?= nl2br(e($module['description'])) ?></p>
          <?php endif; ?>
          <?php if (!empty($module['notes'])): ?>
            <h4 class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">Notes</h4>
            <p class="text-sm font-medium"><?= nl2br(e($module['notes'])) ?></p>
          <?php endif; ?>
          <?php if ($moduleMaterials): ?>
            <h4 class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">Materials</h4>
            <ul class="space-y-1 text-sm font-semibold">
              <?php foreach ($moduleMaterials as $file): ?>
                <li><a href="<?= e($fileHref($file)) ?>" target="_blank" rel="noopener" style="color:var(--ke-green)"><?= e($fileName($file)) ?></a></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <div class="module-player" oncontextmenu="return false;">
            <?php if (($module['video_source'] ?? '') === 'youtube' && !empty($module['embed_url'])): ?>
              <iframe
                src="<?= e($module['embed_url']) ?>"
                title="<?= e($module['title']) ?>"
                allow="accelerometer; autoplay; encrypted-media; gyroscope"
                referrerpolicy="strict-origin-when-cross-origin"
              ></iframe>
            <?php elseif (!empty($module['video_path'])): ?>
              <video controls controlsList="nodownload noremoteplayback noplaybackrate" disablePictureInPicture playsinline oncontextmenu="return false;">
                <source src="<?= e(imageUrl($module['video_path'])) ?>">
              </video>
            <?php else: ?>
              <p class="text-sm font-bold" style="color:var(--ke-muted)">No video yet.</p>
            <?php endif; ?>
          </div>

          <?php if ($isStudent && $questions): ?>
            <div class="quiz-card">
              <h4 class="font-serif-heading text-lg font-bold">Module quiz</h4>
              <p class="text-sm font-medium" style="color:var(--ke-muted)">Score at least <?= (int) $module['pass_percent'] ?>% to open the next module.</p>
              <?php if (!empty($module['is_passed'])): ?>
                <p class="text-sm font-black" style="color:var(--ke-green)">Passed<?= !empty($module['progress']['score']) ? ' · ' . (int) $module['progress']['score'] . '%' : '' ?></p>
              <?php endif; ?>
              <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/modules/' . (int) $module['id'] . '/quiz') ?>" class="space-y-4">
                <?= csrfField() ?>
                <?php foreach ($questions as $qIndex => $question): ?>
                  <fieldset class="space-y-2">
                    <legend class="text-sm font-black"><?= ($qIndex + 1) ?>. <?= e($question['question']) ?></legend>
                    <?php if (($question['type'] ?? 'single_choice') === 'text'): ?>
                      <textarea name="answers[<?= (int) $qIndex ?>]" required rows="4" class="w-full rounded-lg border border-neutral-300 p-2.5 text-sm" placeholder="Type your answer"></textarea>
                    <?php elseif (($question['type'] ?? 'single_choice') === 'multiple_choice'): ?>
                      <?php foreach ($question['options'] as $oIndex => $option): ?>
                        <label class="flex items-center gap-2 text-sm font-semibold">
                          <input type="checkbox" name="answers[<?= (int) $qIndex ?>][]" value="<?= (int) $oIndex ?>" class="h-4 w-4" />
                          <?= e($option) ?>
                        </label>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <?php foreach ($question['options'] as $oIndex => $option): ?>
                        <label class="flex items-center gap-2 text-sm font-semibold">
                          <input type="radio" name="answers[<?= (int) $qIndex ?>]" value="<?= (int) $oIndex ?>" required class="h-4 w-4" />
                          <?= e($option) ?>
                        </label>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </fieldset>
                <?php endforeach; ?>
                <button type="submit" class="btn-primary"><?= !empty($module['is_passed']) ? 'Retake quiz' : 'Submit quiz' ?></button>
              </form>
            </div>
          <?php elseif ($isStudent && empty($questions)): ?>
            <p class="text-xs font-bold" style="color:var(--ke-green)">No quiz on this module, so the next module is open.</p>
          <?php elseif ($canEdit && $questions): ?>
            <p class="text-xs font-bold" style="color:var(--ke-muted)"><?= count($questions) ?> quiz question<?= count($questions) === 1 ? '' : 's' ?> · pass mark <?= (int) $module['pass_percent'] ?>%</p>
          <?php endif; ?>
        <?php endif; ?>
        <?php if ($canEdit): ?>
          <a href="<?= url('/account/courses/' . (int) $course['id'] . '?module=' . (int) $module['id']) ?>#module-editor" class="btn-secondary" style="padding:0.35rem 0.65rem;">Edit module</a>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </section>

  <?php endif; ?>

  <?php if ($isStudent): ?>
    <section class="rounded-xl border bg-white p-6 shadow-sm flex items-center justify-between gap-4" style="border-color:var(--ke-line)">
      <div>
        <h2 class="font-serif-heading text-lg font-bold">Attachment</h2>
        <p class="mt-1 text-sm font-medium" style="color:var(--ke-muted)">Choose an organisation and branch for your attachment, and track your progress.</p>
      </div>
      <a href="<?= url('/account/attachment-providers') ?>" class="btn-secondary shrink-0">Open</a>
    </section>
  <?php endif; ?>

  <?php if ($canEdit): ?>
    <section id="module-editor" class="rounded-xl border bg-white p-6 shadow-sm space-y-4" style="border-color:<?= $editingModule ? 'var(--ke-green);box-shadow:0 0 0 2px var(--ke-green)' : 'var(--ke-line)' ?>;scroll-margin-top:80px">
      <h2 class="font-serif-heading text-lg font-bold"><?= $editingModule ? 'Edit module: ' . e($editingModule['title']) : 'Add module' ?></h2>
      <form method="post" action="<?= $editingModule
        ? url('/account/courses/' . (int) $course['id'] . '/modules/' . (int) $editingModule['id'])
        : url('/account/courses/' . (int) $course['id'] . '/modules') ?>" enctype="multipart/form-data" class="space-y-4" id="module-form">
        <?= csrfField() ?>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Module title</label>
          <input type="text" name="title" required value="<?= e($moduleForm['title'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" />
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Duration</label>
          <input type="text" name="duration_minutes" value="<?= e(\App\Models\CourseModule::formatDuration((int) ($moduleForm['duration_minutes'] ?? 10))) ?>" placeholder="10 min, 30 min, 1 hr, 2.5 hrs, 2 1/4 hrs" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" />
          <p class="field-hint">Examples: 10 min, 30 min, 1 hr, 2 hrs, 2.5 hrs, 2 1/4 hrs.</p>
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Module overview</label>
          <textarea name="summary" rows="3" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm"><?= e($moduleForm['summary'] ?? '') ?></textarea>
          <p class="field-hint">A short overview students see on the module card, even before they unlock or pay for it.</p>
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Description</label>
          <textarea name="description" rows="4" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm"><?= e($moduleForm['description'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Module notes</label>
          <textarea name="notes" rows="4" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm"><?= e($moduleForm['notes'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Resources (PDF, Word, images)</label>
          <input type="file" name="materials[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,image/*" class="mt-2 block w-full text-sm" />
        </div>
        <fieldset class="rounded-lg border p-4 space-y-3" style="border-color:var(--ke-line)">
          <legend class="px-1 text-[11px] font-black uppercase" style="color:var(--ke-muted)">Video</legend>
          <label class="flex items-center gap-2 text-sm font-semibold">
            <input type="radio" name="video_source" value="upload" <?= ($moduleForm['video_source'] ?? 'upload') !== 'youtube' ? 'checked' : '' ?> class="h-4 w-4 js-video-source" />
            Upload a video
          </label>
          <label class="flex items-center gap-2 text-sm font-semibold">
            <input type="radio" name="video_source" value="youtube" <?= ($moduleForm['video_source'] ?? '') === 'youtube' ? 'checked' : '' ?> class="h-4 w-4 js-video-source" />
            YouTube (plays on this page only — learners never see or copy the URL)
          </label>
          <div class="js-upload-wrap">
            <label class="text-[11px] font-bold uppercase text-neutral-600">Video file</label>
            <input type="file" name="video" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov,.m4v" class="mt-2 block w-full text-sm" />
          </div>
          <div class="js-youtube-wrap">
            <label class="text-[11px] font-bold uppercase text-neutral-600">YouTube URL (kept private)</label>
            <input type="url" name="video_url" value="<?= e($moduleForm['video_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=..." class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" autocomplete="off" />
            <p class="field-hint">The link is stored for embedding only. Learners watch it in-platform and cannot copy it from this page.</p>
          </div>
        </fieldset>
        <fieldset class="rounded-lg border p-4 space-y-3" style="border-color:var(--ke-line)">
          <legend class="px-1 text-[11px] font-black uppercase" style="color:var(--ke-muted)">End-of-module quiz (optional)</legend>
          <?php $moduleHasQuiz = !empty($editingModule) ? !empty($moduleForm['quiz_questions']) : !empty($moduleForm['has_quiz']); ?>
          <label class="flex items-center gap-2 text-sm font-bold text-gray-800">
            <input type="checkbox" name="has_quiz" value="1" class="h-4 w-4 js-has-quiz" <?= $moduleHasQuiz ? 'checked' : '' ?>> This module has a quiz
          </label>
          <div class="js-quiz-body space-y-3" <?= $moduleHasQuiz ? '' : 'hidden' ?>>
          <p class="text-sm font-medium" style="color:var(--ke-muted)">Add choice, multiple-answer, text, explanation, or code questions. Students must reach the pass mark to open the next module.</p>
          <div>
            <label class="text-[11px] font-bold uppercase text-neutral-600">Pass mark (%)</label>
            <input type="number" name="pass_percent" min="1" max="100" value="<?= (int) ($moduleForm['pass_percent'] ?? 80) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" />
          </div>
          <div id="quiz-questions" class="space-y-4" data-question-prefix="questions">
            <?php foreach ($moduleForm['quiz_questions'] as $qIndex => $question):
              $type = $question['type'] ?? 'single_choice';
              $options = $question['options'] ?? ['', ''];
              while (count($options) < 2) {
                  $options[] = '';
              }
              $correctList = is_array($question['correct'] ?? null) ? array_map('intval', $question['correct']) : [(int) ($question['correct'] ?? 0)];
              $acceptedAnswers = implode("\n", array_map('strval', $question['accepted_answers'] ?? []));
            ?>
              <div class="quiz-question rounded-lg border p-3 space-y-3" style="border-color:var(--ke-line)">
                <label class="text-[11px] font-bold uppercase text-neutral-600">Question</label>
                <input type="text" name="questions[<?= (int) $qIndex ?>][text]" value="<?= e($question['question'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" />
                <label class="text-[11px] font-bold uppercase text-neutral-600">Answer type</label>
                <select name="questions[<?= (int) $qIndex ?>][type]" class="js-question-type w-full rounded-lg border border-neutral-300 p-2.5 text-sm">
                  <option value="single_choice" <?= $type === 'single_choice' ? 'selected' : '' ?>>Single correct choice</option>
                  <option value="multiple_choice" <?= $type === 'multiple_choice' ? 'selected' : '' ?>>Multiple correct choices</option>
                  <option value="text" <?= $type === 'text' ? 'selected' : '' ?>>Text, explanation, or code</option>
                </select>
                <div class="js-choice-options space-y-2">
                  <?php foreach ($options as $oIndex => $option): ?>
                    <label class="flex items-center gap-2 text-sm">
                      <input type="radio" name="questions[<?= (int) $qIndex ?>][correct]" value="<?= (int) $oIndex ?>" <?= in_array((int) $oIndex, $correctList, true) ? 'checked' : '' ?> class="h-4 w-4 js-single-correct" />
                      <input type="checkbox" name="questions[<?= (int) $qIndex ?>][correct][]" value="<?= (int) $oIndex ?>" <?= in_array((int) $oIndex, $correctList, true) ? 'checked' : '' ?> class="h-4 w-4 js-multiple-correct" />
                      <input type="text" name="questions[<?= (int) $qIndex ?>][options][]" value="<?= e($option) ?>" placeholder="Choice <?= (int) $oIndex + 1 ?>" class="flex-1 rounded-lg border border-neutral-300 p-2 text-sm" />
                    </label>
                  <?php endforeach; ?>
                  <button type="button" class="btn-secondary js-add-option" style="padding:0.3rem 0.6rem;">Add choice</button>
                </div>
                <div class="js-text-answer">
                  <label class="text-[11px] font-bold uppercase text-neutral-600">Accepted text/code answers</label>
                  <textarea name="questions[<?= (int) $qIndex ?>][accepted_answers]" rows="3" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" placeholder="One accepted answer per line. Leave blank for reflection/explanation."><?= e($acceptedAnswers) ?></textarea>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn-secondary js-add-question" id="add-question" data-target="quiz-questions">Add question</button>
          </div>
          <p class="text-xs font-semibold js-no-quiz-note" style="color:var(--ke-muted)" <?= $moduleHasQuiz ? 'hidden' : '' ?>>No quiz: students tick "Mark as done" after reading the module.</p>
        </fieldset>
        <div class="flex flex-wrap items-center gap-3">
          <button type="submit" class="btn-primary"><?= $editingModule ? 'Save module' : 'Add module' ?></button>
          <?php if ($editingModule): ?>
            <a href="<?= url('/account/courses/' . (int) $course['id']) ?>" class="btn-secondary">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
      <?php if ($editingModule): ?>
        <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/modules/' . (int) $editingModule['id'] . '/delete') ?>" onsubmit="return confirm('Delete this module?');">
          <?= csrfField() ?>
          <button type="submit" class="btn-danger">Delete module</button>
        </form>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php // The final exam comes last: after the modules and the module form. ?>
  <?php if (!($isStudent && !$isEnrolled)): ?>
  <section class="rounded-xl border bg-white p-6 shadow-sm space-y-4" id="final-exam" style="border-color:var(--ke-line)">
    <div>
      <h2 class="font-serif-heading text-lg font-bold">Final exam</h2>
      <p class="mt-1 text-sm font-medium" style="color:var(--ke-muted)">Optional. When the course has one, students take it once they have passed every module and paid. Passing it completes the course and earns its certificate. Without one, students finish by ticking "Complete course".</p>
    </div>

    <?php if ($isStudent): ?>
      <?php if (!$modulesComplete): ?>
        <div class="rounded-lg border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">Pass every module quiz to unlock the final exam.</div>
      <?php elseif (empty($course['final_exam_questions'])): ?>
        <div class="rounded-lg border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">The tutor has not added the final exam yet.</div>
      <?php else: ?>
        <div class="quiz-card">
          <h3 class="font-serif-heading text-lg font-bold">Course final exam</h3>
          <p class="text-sm font-medium" style="color:var(--ke-muted)">Score at least <?= $finalPassPercent ?>% to complete the course.</p>
          <?php if (!empty($finalProgress['passed'])): ?>
            <p class="text-sm font-black" style="color:var(--ke-green)">Passed<?= !empty($finalProgress['score']) ? ' · ' . (int) $finalProgress['score'] . '%' : '' ?>.</p>
            <?php if (empty($course['certificate_enabled'])): ?>
              <p class="text-xs font-bold text-neutral-600">This course does not award a certificate.</p>
            <?php elseif (!empty($course['requires_attachment'])): ?>
              <p class="text-xs font-bold text-neutral-600">Complete the attachment workflow below before this skill appears on your certificate.</p>
            <?php else: ?>
              <a href="<?= url('/account/certificate') ?>" class="btn-primary" style="padding:0.4rem 0.75rem;">Open certificate</a>
            <?php endif; ?>
          <?php endif; ?>
          <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/final-exam/submit') ?>" class="space-y-4">
            <?= csrfField() ?>
            <?php foreach (($course['final_exam_questions'] ?? []) as $qIndex => $question): ?>
              <fieldset class="space-y-2">
                <legend class="text-sm font-black"><?= ($qIndex + 1) ?>. <?= e($question['question']) ?></legend>
                <?php if (($question['type'] ?? 'single_choice') === 'text'): ?>
                  <textarea name="answers[<?= (int) $qIndex ?>]" required rows="4" class="w-full rounded-lg border border-neutral-300 p-2.5 text-sm" placeholder="Type your answer"></textarea>
                <?php elseif (($question['type'] ?? 'single_choice') === 'multiple_choice'): ?>
                  <?php foreach ($question['options'] as $oIndex => $option): ?>
                    <label class="flex items-center gap-2 text-sm font-semibold">
                      <input type="checkbox" name="answers[<?= (int) $qIndex ?>][]" value="<?= (int) $oIndex ?>" class="h-4 w-4" />
                      <?= e($option) ?>
                    </label>
                  <?php endforeach; ?>
                <?php else: ?>
                  <?php foreach ($question['options'] as $oIndex => $option): ?>
                    <label class="flex items-center gap-2 text-sm font-semibold">
                      <input type="radio" name="answers[<?= (int) $qIndex ?>]" value="<?= (int) $oIndex ?>" required class="h-4 w-4" />
                      <?= e($option) ?>
                    </label>
                  <?php endforeach; ?>
                <?php endif; ?>
              </fieldset>
            <?php endforeach; ?>
            <button type="submit" class="btn-primary"><?= !empty($finalProgress['passed']) ? 'Retake final exam' : 'Submit final exam' ?></button>
          </form>
        </div>
      <?php endif; ?>
    <?php elseif ($canEdit): ?>
      <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/final-exam') ?>" class="space-y-4">
        <?= csrfField() ?>
        <?php $courseHasFinal = !empty($course['final_exam_questions']); ?>
        <label class="flex items-center gap-2 text-sm font-bold text-gray-800">
          <input type="checkbox" name="has_final" value="1" class="h-4 w-4" <?= $courseHasFinal ? 'checked' : '' ?>
                 onchange="this.form.querySelector('.js-final-body').hidden = !this.checked"> This course has a final exam
        </label>
        <div class="js-final-body space-y-4" <?= $courseHasFinal ? '' : 'hidden' ?>>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Questions each student gets</label>
          <input type="number" name="final_paper_size" min="1" value="<?= (int) ($course['final_paper_size'] ?? 0) ?: '' ?>" placeholder="All of them" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" />
          <p class="field-hint">Write more questions than this to make a question bank. Each student gets their own random selection, in a random order, with the answer choices shuffled. Students need 100% to pass and earn the certificate.</p>
        </div>
        <div id="final-questions" class="space-y-4" data-question-prefix="final_questions">
          <?php foreach ($finalQuestions as $qIndex => $question):
            $type = $question['type'] ?? 'single_choice';
            $options = $question['options'] ?? ['', ''];
            while (count($options) < 2) {
                $options[] = '';
            }
            $correctList = is_array($question['correct'] ?? null) ? array_map('intval', $question['correct']) : [(int) ($question['correct'] ?? 0)];
            $acceptedAnswers = implode("\n", array_map('strval', $question['accepted_answers'] ?? []));
          ?>
            <div class="quiz-question rounded-lg border p-3 space-y-3" style="border-color:var(--ke-line)">
              <label class="text-[11px] font-bold uppercase text-neutral-600">Question</label>
              <input type="text" name="final_questions[<?= (int) $qIndex ?>][text]" value="<?= e($question['question'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" />
              <label class="text-[11px] font-bold uppercase text-neutral-600">Answer type</label>
              <select name="final_questions[<?= (int) $qIndex ?>][type]" class="js-question-type w-full rounded-lg border border-neutral-300 p-2.5 text-sm">
                <option value="single_choice" <?= $type === 'single_choice' ? 'selected' : '' ?>>Single correct choice</option>
                <option value="multiple_choice" <?= $type === 'multiple_choice' ? 'selected' : '' ?>>Multiple correct choices</option>
                <option value="text" <?= $type === 'text' ? 'selected' : '' ?>>Text, explanation, or code</option>
              </select>
              <div class="js-choice-options space-y-2">
                <?php foreach ($options as $oIndex => $option): ?>
                  <label class="flex items-center gap-2 text-sm">
                    <input type="radio" name="final_questions[<?= (int) $qIndex ?>][correct]" value="<?= (int) $oIndex ?>" <?= in_array((int) $oIndex, $correctList, true) ? 'checked' : '' ?> class="h-4 w-4 js-single-correct" />
                    <input type="checkbox" name="final_questions[<?= (int) $qIndex ?>][correct][]" value="<?= (int) $oIndex ?>" <?= in_array((int) $oIndex, $correctList, true) ? 'checked' : '' ?> class="h-4 w-4 js-multiple-correct" />
                    <input type="text" name="final_questions[<?= (int) $qIndex ?>][options][]" value="<?= e($option) ?>" placeholder="Choice <?= (int) $oIndex + 1 ?>" class="flex-1 rounded-lg border border-neutral-300 p-2 text-sm" />
                  </label>
                <?php endforeach; ?>
                <button type="button" class="btn-secondary js-add-option" style="padding:0.3rem 0.6rem;">Add choice</button>
              </div>
              <div class="js-text-answer">
                <label class="text-[11px] font-bold uppercase text-neutral-600">Accepted text/code answers</label>
                <textarea name="final_questions[<?= (int) $qIndex ?>][accepted_answers]" rows="3" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" placeholder="One accepted answer per line. Leave blank for reflection/explanation."><?= e($acceptedAnswers) ?></textarea>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn-secondary js-add-question" data-target="final-questions">Add question</button>
        </div>
        <button type="submit" class="btn-primary">Save final exam settings</button>
      </form>
    <?php else: ?>
      <p class="text-sm font-bold" style="color:var(--ke-muted)">The final exam is available after all modules are complete.</p>
    <?php endif; ?>
  </section>
  <?php endif; ?>
</div>

<script>
(function () {
  // Quiz on/off: hide the questions when the module has no quiz.
  document.querySelectorAll('.js-has-quiz').forEach(function (box) {
    box.addEventListener('change', function () {
      var set = box.closest('fieldset');
      set.querySelector('.js-quiz-body').hidden = !box.checked;
      set.querySelector('.js-no-quiz-note').hidden = box.checked;
    });
  });
  function syncVideoSource() {
    var youtube = document.querySelector('input[name="video_source"][value="youtube"]');
    var isYoutube = !!(youtube && youtube.checked);
    document.querySelectorAll('.js-upload-wrap').forEach(function (el) { el.classList.toggle('hidden', isYoutube); });
    document.querySelectorAll('.js-youtube-wrap').forEach(function (el) { el.classList.toggle('hidden', !isYoutube); });
  }
  document.querySelectorAll('.js-video-source').forEach(function (input) {
    input.addEventListener('change', syncVideoSource);
  });
  syncVideoSource();

  function syncQuestionType(block) {
    var select = block.querySelector('.js-question-type');
    var type = select ? select.value : 'single_choice';
    var choiceWrap = block.querySelector('.js-choice-options');
    var textWrap = block.querySelector('.js-text-answer');
    if (choiceWrap) {
      choiceWrap.classList.toggle('hidden', type === 'text');
      choiceWrap.querySelectorAll('input[type="text"]').forEach(function (input) {
        input.disabled = type === 'text';
      });
      choiceWrap.querySelectorAll('.js-single-correct').forEach(function (input, index) {
        input.classList.toggle('hidden', type !== 'single_choice');
        input.disabled = type !== 'single_choice';
        if (type === 'single_choice' && index === 0 && !choiceWrap.querySelector('.js-single-correct:checked')) {
          input.checked = true;
        }
      });
      choiceWrap.querySelectorAll('.js-multiple-correct').forEach(function (input, index) {
        input.classList.toggle('hidden', type !== 'multiple_choice');
        input.disabled = type !== 'multiple_choice';
        if (type === 'multiple_choice' && index === 0 && !choiceWrap.querySelector('.js-multiple-correct:checked')) {
          input.checked = true;
        }
      });
    }
    if (textWrap) {
      textWrap.classList.toggle('hidden', type !== 'text');
      textWrap.querySelectorAll('textarea').forEach(function (input) {
        input.disabled = type !== 'text';
      });
    }
  }

  function syncAllQuestions() {
    document.querySelectorAll('.quiz-question').forEach(syncQuestionType);
  }

  document.addEventListener('change', function (event) {
    if (event.target.classList.contains('js-question-type')) {
      var block = event.target.closest('.quiz-question');
      if (block) syncQuestionType(block);
    }
  });

  document.addEventListener('click', function (event) {
    if (!event.target.classList.contains('js-add-option')) {
      return;
    }
    var block = event.target.closest('.quiz-question');
    var wrap = event.target.closest('.js-choice-options');
    var select = block ? block.querySelector('.js-question-type') : null;
    if (!block || !wrap || !select) {
      return;
    }
    var questionInput = block.querySelector('input[name$="[text]"]');
    var match = questionInput ? questionInput.name.match(/^(.+)\[(\d+)\]\[text\]$/) : null;
    if (!match) {
      return;
    }
    var prefix = match[1];
    var qIndex = match[2];
    var optionIndex = wrap.querySelectorAll('input[name$="[options][]"]').length;
    var label = document.createElement('label');
    label.className = 'flex items-center gap-2 text-sm';
    label.innerHTML =
      '<input type="radio" name="' + prefix + '[' + qIndex + '][correct]" value="' + optionIndex + '" class="h-4 w-4 js-single-correct" />' +
      '<input type="checkbox" name="' + prefix + '[' + qIndex + '][correct][]" value="' + optionIndex + '" class="h-4 w-4 js-multiple-correct" />' +
      '<input type="text" name="' + prefix + '[' + qIndex + '][options][]" placeholder="Choice ' + (optionIndex + 1) + '" class="flex-1 rounded-lg border border-neutral-300 p-2 text-sm" />';
    wrap.insertBefore(label, event.target);
    syncQuestionType(block);
  });

  document.querySelectorAll('.js-add-question').forEach(function (addBtn) {
    addBtn.addEventListener('click', function () {
      var wrap = document.getElementById(addBtn.getAttribute('data-target'));
      if (!wrap) {
        return;
      }
      var index = wrap.querySelectorAll('.quiz-question').length;
      var prefix = wrap.getAttribute('data-question-prefix') || 'questions';
      var block = document.createElement('div');
      block.className = 'quiz-question rounded-lg border p-3 space-y-3';
      block.style.borderColor = 'var(--ke-line)';
      block.innerHTML =
        '<label class="text-[11px] font-bold uppercase text-neutral-600">Question</label>' +
        '<input type="text" name="' + prefix + '[' + index + '][text]" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" />' +
        '<label class="text-[11px] font-bold uppercase text-neutral-600">Answer type</label>' +
        '<select name="' + prefix + '[' + index + '][type]" class="js-question-type w-full rounded-lg border border-neutral-300 p-2.5 text-sm">' +
          '<option value="single_choice">Single correct choice</option>' +
          '<option value="multiple_choice">Multiple correct choices</option>' +
          '<option value="text">Text, explanation, or code</option>' +
        '</select>' +
        '<div class="js-choice-options space-y-2">' +
          [0,1].map(function (i) {
            return '<label class="flex items-center gap-2 text-sm">' +
              '<input type="radio" name="' + prefix + '[' + index + '][correct]" value="' + i + '"' + (i === 0 ? ' checked' : '') + ' class="h-4 w-4 js-single-correct" />' +
              '<input type="checkbox" name="' + prefix + '[' + index + '][correct][]" value="' + i + '"' + (i === 0 ? ' checked' : '') + ' class="h-4 w-4 js-multiple-correct" />' +
              '<input type="text" name="' + prefix + '[' + index + '][options][]" placeholder="Choice ' + (i + 1) + '" class="flex-1 rounded-lg border border-neutral-300 p-2 text-sm" />' +
              '</label>';
          }).join('') +
          '<button type="button" class="btn-secondary js-add-option" style="padding:0.3rem 0.6rem;">Add choice</button>' +
        '</div>' +
        '<div class="js-text-answer">' +
          '<label class="text-[11px] font-bold uppercase text-neutral-600">Accepted text/code answers</label>' +
          '<textarea name="' + prefix + '[' + index + '][accepted_answers]" rows="3" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" placeholder="One accepted answer per line. Leave blank for reflection/explanation."></textarea>' +
        '</div>';
      wrap.appendChild(block);
      syncQuestionType(block);
    });
  });
  syncAllQuestions();
})();
</script>

<?php require __DIR__ . '/../layout-footer.php'; ?>
