<?php
$course = $course ?? [];
$modules = $modules ?? [];
$canEdit = !empty($canEdit);
$isStudent = !empty($isStudent);
$editingModule = $editingModule ?? null;
$moduleForm = $moduleForm ?? ['title' => '', 'description' => '', 'summary' => '', 'notes' => '', 'duration_minutes' => 10, 'video_source' => 'upload', 'video_url' => '', 'quiz_questions' => [], 'pass_percent' => 70];
if (empty($moduleForm['quiz_questions'])) {
    $moduleForm['quiz_questions'] = [['question' => '', 'options' => ['', '', '', ''], 'correct' => 0]];
}
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
        <p class="mt-1 text-sm font-semibold" style="color:var(--ke-muted)"><?= e($course['category_name'] ?? '') ?> · <?= e($course['organisation_name'] ?? '') ?></p>
        <p class="text-[11px] font-black uppercase visibility-badge <?= ($course['visibility'] ?? 'strict') === 'global' ? 'is-global' : 'is-strict' ?>">
          <?= ($course['visibility'] ?? 'strict') === 'global' ? 'Global — all students' : 'Strict — this organisation' ?>
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <?php if ($canEdit): ?>
          <a href="<?= url('/account/courses/' . (int) $course['id'] . '/edit') ?>" class="btn-secondary">Edit details</a>
          <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/delete') ?>" onsubmit="return confirm('Delete this course and its topics?');">
            <?= csrfField() ?>
            <button type="submit" class="btn-danger">Delete</button>
          </form>
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

  <section class="rounded-xl border bg-white p-6 shadow-sm space-y-4" style="border-color:var(--ke-line)">
    <div>
      <h2 class="font-serif-heading text-lg font-bold">Topics</h2>
      <p class="mt-1 text-sm font-medium" style="color:var(--ke-muted)">Each topic (module / unit) can include a video, description, materials, and a multiple-choice quiz. Students must pass a topic’s quiz before the next one opens.</p>
    </div>
    <?php if (!$modules): ?>
      <p class="text-sm font-bold" style="color:var(--ke-muted)">No topics yet.</p>
    <?php endif; ?>
    <?php foreach ($modules as $index => $module):
      $moduleMaterials = is_array($module['materials'] ?? null) ? $module['materials'] : [];
      $locked = $isStudent && empty($module['is_unlocked']);
      $questions = $module['quiz_questions'] ?? [];
    ?>
      <article class="module-card <?= $locked ? 'is-locked' : '' ?>" id="topic-<?= (int) $module['id'] ?>">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h3 class="font-serif-heading text-xl font-bold"><?= ($index + 1) ?>. <?= e($module['title']) ?></h3>
          <p class="text-xs font-black uppercase tracking-wider" style="color:var(--ke-muted)"><?= (int) $module['duration_minutes'] ?> minutes</p>
        </div>
        <?php if ($locked): ?>
          <div class="module-lock">
            <p class="text-sm font-bold">Locked. Pass the quiz on the previous topic to continue.</p>
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
              <h4 class="font-serif-heading text-lg font-bold">Topic quiz</h4>
              <p class="text-sm font-medium" style="color:var(--ke-muted)">Score at least <?= (int) $module['pass_percent'] ?>% to open the next topic.</p>
              <?php if (!empty($module['is_passed'])): ?>
                <p class="text-sm font-black" style="color:var(--ke-green)">Passed<?= !empty($module['progress']['score']) ? ' · ' . (int) $module['progress']['score'] . '%' : '' ?></p>
              <?php endif; ?>
              <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/modules/' . (int) $module['id'] . '/quiz') ?>" class="space-y-4">
                <?= csrfField() ?>
                <?php foreach ($questions as $qIndex => $question): ?>
                  <fieldset class="space-y-2">
                    <legend class="text-sm font-black"><?= ($qIndex + 1) ?>. <?= e($question['question']) ?></legend>
                    <?php foreach ($question['options'] as $oIndex => $option): ?>
                      <label class="flex items-center gap-2 text-sm font-semibold">
                        <input type="radio" name="answers[<?= (int) $qIndex ?>]" value="<?= (int) $oIndex ?>" required class="h-4 w-4" />
                        <?= e($option) ?>
                      </label>
                    <?php endforeach; ?>
                  </fieldset>
                <?php endforeach; ?>
                <button type="submit" class="btn-primary"><?= !empty($module['is_passed']) ? 'Retake quiz' : 'Submit quiz' ?></button>
              </form>
            </div>
          <?php elseif ($isStudent && empty($questions)): ?>
            <p class="text-xs font-bold" style="color:var(--ke-green)">No quiz on this topic — the next topic is open.</p>
          <?php elseif ($canEdit && $questions): ?>
            <p class="text-xs font-bold" style="color:var(--ke-muted)"><?= count($questions) ?> quiz question<?= count($questions) === 1 ? '' : 's' ?> · pass mark <?= (int) $module['pass_percent'] ?>%</p>
          <?php endif; ?>
        <?php endif; ?>
        <?php if ($canEdit): ?>
          <a href="<?= url('/account/courses/' . (int) $course['id'] . '?module=' . (int) $module['id']) ?>" class="btn-secondary" style="padding:0.35rem 0.65rem;">Edit topic</a>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </section>

  <?php if ($canEdit): ?>
    <section class="rounded-xl border bg-white p-6 shadow-sm space-y-4" style="border-color:var(--ke-line)">
      <h2 class="font-serif-heading text-lg font-bold"><?= $editingModule ? 'Edit topic' : 'Add topic' ?></h2>
      <form method="post" action="<?= $editingModule
        ? url('/account/courses/' . (int) $course['id'] . '/modules/' . (int) $editingModule['id'])
        : url('/account/courses/' . (int) $course['id'] . '/modules') ?>" enctype="multipart/form-data" class="space-y-4" id="module-form">
        <?= csrfField() ?>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Topic title</label>
          <input type="text" name="title" required value="<?= e($moduleForm['title'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" />
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Duration</label>
          <select name="duration_minutes" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm">
            <?php foreach ($durations as $minutes): ?>
              <option value="<?= (int) $minutes ?>" <?= (int) ($moduleForm['duration_minutes'] ?? 10) === (int) $minutes ? 'selected' : '' ?>>
                <?= (int) $minutes ?> minutes
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Summary</label>
          <textarea name="summary" rows="3" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm"><?= e($moduleForm['summary'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Description</label>
          <textarea name="description" rows="4" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm"><?= e($moduleForm['description'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Notes</label>
          <textarea name="notes" rows="4" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm"><?= e($moduleForm['notes'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Materials (PDF, Word, images)</label>
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
          <legend class="px-1 text-[11px] font-black uppercase" style="color:var(--ke-muted)">End-of-topic quiz</legend>
          <p class="text-sm font-medium" style="color:var(--ke-muted)">Add multiple-choice questions. Students must pass to unlock the next topic. Leave blank if this topic has no gate.</p>
          <div>
            <label class="text-[11px] font-bold uppercase text-neutral-600">Pass mark (%)</label>
            <input type="number" name="pass_percent" min="1" max="100" value="<?= (int) ($moduleForm['pass_percent'] ?? 70) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" />
          </div>
          <div id="quiz-questions" class="space-y-4">
            <?php foreach ($moduleForm['quiz_questions'] as $qIndex => $question):
              $options = $question['options'] ?? ['', '', '', ''];
              while (count($options) < 4) {
                  $options[] = '';
              }
            ?>
              <div class="quiz-question rounded-lg border p-3 space-y-2" style="border-color:var(--ke-line)">
                <label class="text-[11px] font-bold uppercase text-neutral-600">Question</label>
                <input type="text" name="questions[<?= (int) $qIndex ?>][text]" value="<?= e($question['question'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" />
                <?php foreach ($options as $oIndex => $option): ?>
                  <label class="flex items-center gap-2 text-sm">
                    <input type="radio" name="questions[<?= (int) $qIndex ?>][correct]" value="<?= (int) $oIndex ?>" <?= (int) ($question['correct'] ?? 0) === (int) $oIndex ? 'checked' : '' ?> class="h-4 w-4" />
                    <input type="text" name="questions[<?= (int) $qIndex ?>][options][]" value="<?= e($option) ?>" placeholder="Option <?= (int) $oIndex + 1 ?>" class="flex-1 rounded-lg border border-neutral-300 p-2 text-sm" />
                  </label>
                <?php endforeach; ?>
                <p class="text-[11px] font-semibold" style="color:var(--ke-muted)">Select the radio next to the correct answer.</p>
              </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn-secondary" id="add-question">Add question</button>
        </fieldset>
        <div class="flex flex-wrap items-center gap-3">
          <button type="submit" class="btn-primary"><?= $editingModule ? 'Save topic' : 'Add topic' ?></button>
          <?php if ($editingModule): ?>
            <a href="<?= url('/account/courses/' . (int) $course['id']) ?>" class="btn-secondary">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
      <?php if ($editingModule): ?>
        <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/modules/' . (int) $editingModule['id'] . '/delete') ?>" onsubmit="return confirm('Delete this topic?');">
          <?= csrfField() ?>
          <button type="submit" class="btn-danger">Delete topic</button>
        </form>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</div>

<script>
(function () {
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

  var wrap = document.getElementById('quiz-questions');
  var addBtn = document.getElementById('add-question');
  if (wrap && addBtn) {
    addBtn.addEventListener('click', function () {
      var index = wrap.querySelectorAll('.quiz-question').length;
      var block = document.createElement('div');
      block.className = 'quiz-question rounded-lg border p-3 space-y-2';
      block.style.borderColor = 'var(--ke-line)';
      block.innerHTML =
        '<label class="text-[11px] font-bold uppercase text-neutral-600">Question</label>' +
        '<input type="text" name="questions[' + index + '][text]" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" />' +
        [0,1,2,3].map(function (i) {
          return '<label class="flex items-center gap-2 text-sm">' +
            '<input type="radio" name="questions[' + index + '][correct]" value="' + i + '"' + (i === 0 ? ' checked' : '') + ' class="h-4 w-4" />' +
            '<input type="text" name="questions[' + index + '][options][]" placeholder="Option ' + (i + 1) + '" class="flex-1 rounded-lg border border-neutral-300 p-2 text-sm" />' +
            '</label>';
        }).join('') +
        '<p class="text-[11px] font-semibold" style="color:var(--ke-muted)">Select the radio next to the correct answer.</p>';
      wrap.appendChild(block);
    });
  }
})();
</script>

<?php require __DIR__ . '/../layout-footer.php'; ?>
