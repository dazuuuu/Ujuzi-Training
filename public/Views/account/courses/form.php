<?php
$forms = $forms ?? [];
$course = $course ?? null;
$errors = $errors ?? [];
$visibility = is_array($course) ? ($course['visibility'] ?? 'strict') : 'strict';
$action = $course ? url('/account/courses/' . (int) $course['id']) : url('/account/courses');
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-8">
  <div>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Courses</p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold"><?= $course ? 'Edit course' : 'Create a course' ?></h1>
    <p class="mt-2 text-sm font-medium" style="color:var(--ke-muted)">Fill the form Super Admin assigned to tutors. After you save, open the course to add modules, videos, resources, quizzes, and the final exam.</p>
  </div>

  <?php foreach ($errors as $err): ?>
    <div class="rounded-lg border border-rose-300 bg-rose-50 p-3 text-sm font-semibold text-rose-800"><?= e($err) ?></div>
  <?php endforeach; ?>

  <?php if (!$forms): ?>
    <div class="rounded-xl border border-dashed p-8 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No course form is assigned to your role yet. Ask Super Admin to create a form, set its purpose to Course creation, and assign it to Trainer / Tutor / Teacher.</div>
  <?php endif; ?>

  <?php foreach ($forms as $form):
    $answers = $form['answers'] ?? [];
    $hasFiles = false;
    foreach ($form['fields'] as $field) {
        if (\App\Models\FormFieldTypes::isFile($field['field_type'] ?? '')) {
            $hasFiles = true;
            break;
        }
    }
  ?>
    <form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="rounded-xl border bg-white p-6 shadow-sm space-y-4" style="border-color:var(--ke-line)">
      <?= csrfField() ?>
      <input type="hidden" name="form_id" value="<?= (int) $form['id'] ?>" />
      <div class="border-b border-neutral-100 pb-3">
        <h2 class="font-serif-heading text-lg font-bold"><?= e($form['title']) ?></h2>
        <?php if (!empty($form['description'])): ?>
          <p class="mt-1 text-sm font-medium" style="color:var(--ke-muted)"><?= e($form['description']) ?></p>
        <?php endif; ?>
      </div>
      <?php foreach ($form['fields'] as $field): ?>
        <div>
          <?php
            $value = $answers[$field['field_key']] ?? '';
            require dirname(__DIR__) . '/partials/field.php';
          ?>
        </div>
      <?php endforeach; ?>
      <div class="rounded-lg border p-4" style="border-color:var(--ke-line)">
        <label class="text-[11px] font-bold uppercase text-neutral-600" for="course-cover">Cover image<?= empty($course['cover_image']) ? ' *' : '' ?></label>
        <div class="mt-2 flex flex-wrap items-center gap-3">
          <?php if (!empty($course['cover_image']) && preg_match('/\.(jpe?g|png|webp|gif)$/i', (string) $course['cover_image'])): ?>
            <img src="<?= e(imageUrl($course['cover_image'])) ?>" alt="Current cover" class="h-20 w-32 rounded-lg object-cover">
          <?php endif; ?>
          <input id="course-cover" type="file" name="course_cover" accept="image/jpeg,image/png,image/webp" <?= empty($course['cover_image']) ? 'required' : '' ?> class="block min-w-0 flex-1 text-sm" />
        </div>
        <p class="field-hint">Shown on the course card and on the homepage. A wide picture (16:9) looks best.</p>
      </div>
      <input type="hidden" name="is_published" value="0" />
      <label class="flex items-center gap-2 text-sm font-bold">
        <input type="checkbox" name="is_published" value="1" <?= empty($course) || !empty($course['is_published']) ? 'checked' : '' ?> class="h-4 w-4" />
        Published
      </label>
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Enrollment fee (KSH)</label>
        <input type="number" name="enrollment_fee_ksh" min="0" step="1" value="<?= e((string) (float) ($course['enrollment_fee_ksh'] ?? 0)) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" />
        <p class="field-hint">Students confirm this fee before enrollment. Use 0 for a free course.</p>
      </div>
      <input type="hidden" name="requires_attachment" value="0" />
      <label class="flex items-start gap-2 text-sm font-bold">
        <input type="checkbox" name="requires_attachment" value="1" <?= !empty($course['requires_attachment']) ? 'checked' : '' ?> class="mt-1 h-4 w-4" />
        <span>
          Requires attachment after passing
          <span class="block text-xs font-semibold text-neutral-600">Students will see attachment organisations after passing this course and must be recommended before the certificate opens.</span>
        </span>
      </label>
      <input type="hidden" name="certificate_enabled" value="0" />
      <label class="flex items-start gap-2 text-sm font-bold">
        <input type="checkbox" name="certificate_enabled" value="1" <?= !isset($course['certificate_enabled']) || !empty($course['certificate_enabled']) ? 'checked' : '' ?> class="mt-1 h-4 w-4" />
        <span>
          Award certificate for this course
          <span class="block text-xs font-semibold text-neutral-600">Turn this off for free courses without certificates, or paid courses that should not appear on certificates.</span>
        </span>
      </label>
      <input type="hidden" name="requires_full_registration" value="0" />
      <label class="flex items-start gap-2 text-sm font-bold">
        <input type="checkbox" name="requires_full_registration" value="1" <?= !empty($course['requires_full_registration']) ? 'checked' : '' ?> class="mt-1 h-4 w-4" />
        <span>
          Require full registration before enrolling
          <span class="block text-xs font-semibold text-neutral-600">Students must complete the registration form assigned to students (autofilled from what they've already saved) before they can enroll.</span>
        </span>
      </label>
      <fieldset class="rounded-lg border p-4 space-y-3" style="border-color:var(--ke-line)">
        <legend class="px-1 text-[11px] font-black uppercase" style="color:var(--ke-muted)">Course introduction</legend>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Introduction title</label>
          <input type="text" name="introduction_title" value="<?= e($course['introduction_title'] ?? '') ?>" placeholder="Welcome to this course" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" />
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Introduction description</label>
          <textarea name="introduction_description" rows="4" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm"><?= e($course['introduction_description'] ?? '') ?></textarea>
        </div>
        <label class="flex items-center gap-2 text-sm font-semibold">
          <input type="radio" name="introduction_video_source" value="youtube" <?= ($course['introduction_video_source'] ?? 'youtube') !== 'upload' ? 'checked' : '' ?> class="h-4 w-4 js-intro-video-source" />
          YouTube introduction
        </label>
        <label class="flex items-center gap-2 text-sm font-semibold">
          <input type="radio" name="introduction_video_source" value="upload" <?= ($course['introduction_video_source'] ?? '') === 'upload' ? 'checked' : '' ?> class="h-4 w-4 js-intro-video-source" />
          Upload introduction video
        </label>
        <div class="js-intro-youtube-wrap">
          <label class="text-[11px] font-bold uppercase text-neutral-600">YouTube URL</label>
          <input type="url" name="introduction_video_url" value="<?= e($course['introduction_video_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=..." class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" autocomplete="off" />
        </div>
        <div class="js-intro-upload-wrap">
          <label class="text-[11px] font-bold uppercase text-neutral-600">Video file</label>
          <input type="file" name="introduction_video" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov,.m4v" class="mt-2 block w-full text-sm" />
          <?php if (!empty($course['introduction_video_path'])): ?>
            <p class="field-hint">A video is already uploaded. Uploading a new one replaces it.</p>
          <?php endif; ?>
        </div>
      </fieldset>
      <fieldset class="rounded-lg border p-4 space-y-2" style="border-color:var(--ke-line)">
        <legend class="px-1 text-[11px] font-black uppercase" style="color:var(--ke-muted)">Who can see this course</legend>
        <label class="flex items-start gap-2 text-sm font-semibold">
          <input type="radio" name="visibility" value="strict" class="mt-1 h-4 w-4" <?= $visibility !== 'global' ? 'checked' : '' ?> />
          <span><strong>Strict</strong> (default) — only students who selected this organisation see it.</span>
        </label>
        <label class="flex items-start gap-2 text-sm font-semibold">
          <input type="radio" name="visibility" value="global" class="mt-1 h-4 w-4" <?= $visibility === 'global' ? 'checked' : '' ?> />
          <span><strong>Global</strong> — every student can see it, regardless of organisation (for basic skills and similar courses).</span>
        </label>
      </fieldset>
      <button type="submit" class="btn-primary"><?= $course ? 'Save course' : 'Create course' ?></button>
    </form>
  <?php endforeach; ?>
</div>

<script>
(function () {
  function syncIntroVideoSource() {
    var upload = document.querySelector('input[name="introduction_video_source"][value="upload"]');
    var isUpload = !!(upload && upload.checked);
    document.querySelectorAll('.js-intro-upload-wrap').forEach(function (el) { el.classList.toggle('hidden', !isUpload); });
    document.querySelectorAll('.js-intro-youtube-wrap').forEach(function (el) { el.classList.toggle('hidden', isUpload); });
  }
  document.querySelectorAll('.js-intro-video-source').forEach(function (input) {
    input.addEventListener('change', syncIntroVideoSource);
  });
  syncIntroVideoSource();
})();
</script>

<?php require __DIR__ . '/../layout-footer.php'; ?>
