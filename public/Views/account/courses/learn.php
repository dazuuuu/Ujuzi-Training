<?php
/**
 * An enrolled student's course: the chosen lesson (introduction, a module, or
 * the final exam) in the middle, the course content list on the right.
 * Requires $course, $modules (with is_unlocked / is_passed / progress),
 * $finalProgress, $modulesComplete, $lesson (requested key), $feeKsh,
 * $paidKsh and $settled.
 */
use App\Models\CourseModule;

$modulesComplete = !empty($modulesComplete);
$finalQuestions = $course['final_exam_questions'] ?? [];
$hasFinal = !empty($finalQuestions);
$finalPassed = !empty($finalProgress['passed']);
$hasIntro = !empty($course['introduction_title']) || !empty($course['introduction_description'])
    || !empty($course['introduction_video_path']) || !empty($course['introduction_embed_url']);
$materials = is_array($course['materials'] ?? null) ? $course['materials'] : [];
$fileHref = static function ($file): string {
    return imageUrl(is_array($file) ? (string) ($file['url'] ?? $file['path'] ?? '') : (string) $file);
};
$fileName = static function ($file): string {
    if (is_array($file) && !empty($file['name'])) {
        return (string) $file['name'];
    }
    $path = is_array($file) ? (string) ($file['url'] ?? $file['path'] ?? '') : (string) $file;
    return $path !== '' ? basename($path) : 'Download';
};

// Every step of the course, in order, with its state: done, open or locked.
$lessons = [];
if ($hasIntro) {
    $lessons['intro'] = ['type' => 'intro', 'title' => $course['introduction_title'] ?: 'Introduction', 'meta' => 'Start here', 'state' => 'open'];
}
foreach ($modules as $i => $module) {
    $lessons['m' . (int) $module['id']] = [
        'type' => 'module', 'index' => $i, 'module' => $module, 'title' => $module['title'],
        'meta' => CourseModule::formatDuration((int) $module['duration_minutes']) . (!empty($module['quiz_questions']) ? ' · quiz' : ''),
        'state' => !empty($module['is_passed']) ? 'done' : (empty($module['is_unlocked']) ? 'locked' : 'open'),
    ];
}
$lessons['final'] = [
    'type' => 'final', 'title' => 'Final exam',
    'meta' => $hasFinal ? count($finalQuestions) . ' questions · pass ' . (int) ($course['final_pass_percent'] ?? 80) . '%' : 'Not set yet',
    'state' => $finalPassed ? 'done' : (($modulesComplete && $hasFinal) ? 'open' : 'locked'),
];

// Which lesson to show: the one asked for, else where the student should carry on.
$current = isset($lessons[$lesson]) ? $lesson : null;
if ($current === null) {
    $passedAny = (bool) array_filter($modules, static fn(array $m): bool => !empty($m['is_passed']));
    foreach ($lessons as $key => $l) {
        if ($l['type'] === 'module' && $l['state'] === 'open') { $current = $key; break; }
    }
    if ($hasIntro && !$passedAny) {
        $current = 'intro';
    }
    $current ??= $lessons['final']['state'] !== 'locked' ? 'final' : array_key_first($lessons);
}
$keys = array_keys($lessons);
$at = array_search($current, $keys, true);
$prevKey = $keys[$at - 1] ?? null;
$nextKey = $keys[$at + 1] ?? null;
$here = $lessons[$current];

$steps = count($modules) + ($hasFinal ? 1 : 0);
$done = count(array_filter($modules, static fn(array $m): bool => !empty($m['is_passed']))) + ($finalPassed ? 1 : 0);
$overall = $steps ? (int) round($done * 100 / $steps) : 0;
$owed = max(0, $feeKsh - $paidKsh);
$lessonUrl = static fn(string $key): string => url('/account/courses/' . (int) $course['id']) . '?lesson=' . urlencode($key) . '#lesson';

/** Renders one quiz / exam question set. */
$questionsHtml = static function (array $questions): void {
    foreach ($questions as $qIndex => $question) {
        $type = $question['type'] ?? 'single_choice';
        echo '<fieldset class="space-y-2"><legend class="mb-2 text-sm font-black text-gray-800">' . ($qIndex + 1) . '. ' . e($question['question']) . '</legend>';
        if ($type === 'text') {
            echo '<textarea name="answers[' . (int) $qIndex . ']" required rows="3" class="w-full rounded-lg border border-neutral-300 p-2.5 text-sm" placeholder="Type your answer"></textarea>';
        } else {
            foreach ($question['options'] as $oIndex => $option) {
                $input = $type === 'multiple_choice'
                    ? '<input type="checkbox" name="answers[' . (int) $qIndex . '][]" value="' . (int) $oIndex . '" class="h-4 w-4">'
                    : '<input type="radio" name="answers[' . (int) $qIndex . ']" value="' . (int) $oIndex . '" required class="h-4 w-4">';
                echo '<label class="learn-option">' . $input . '<span>' . e($option) . '</span></label>';
            }
        }
        echo '</fieldset>';
    }
};

require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-5">
  <!-- Course header -->
  <section class="learn-card flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">
      <a href="<?= url('/account/courses') ?>" class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">← My courses</a>
      <h1 class="mt-1 font-serif-heading text-2xl font-bold leading-tight sm:text-3xl"><?= e($course['title']) ?></h1>
      <p class="mt-1 text-sm font-semibold text-neutral-500">
        <?= e($course['organisation_name'] ?? '') ?><?= !empty($course['category_name']) ? ' · ' . e($course['category_name']) : '' ?>
        <?php if (!empty($course['first_name'])): ?> · Tutor: <?= e(trim($course['first_name'] . ' ' . ($course['last_name'] ?? ''))) ?><?php endif; ?>
      </p>
    </div>
    <div class="flex shrink-0 items-center gap-4">
      <?php if ($feeKsh > 0 && !$settled && $owed > 0): ?>
        <div class="text-right">
          <p class="text-xs font-bold text-neutral-500">Balance</p>
          <p class="text-sm font-black" style="color:var(--ke-red)">Ksh <?= number_format($owed, 2) ?></p>
          <a href="<?= url('/account/courses/' . (int) $course['id'] . '/checkout') ?>" class="text-xs font-black underline" style="color:var(--ke-green)">Pay balance</a>
        </div>
      <?php endif; ?>
      <div class="flex items-center gap-3">
        <?php $pct = $overall; $ringSize = 56; $ringLabel = 'Course progress'; require __DIR__ . '/../partials/progress-ring.php'; ?>
        <div>
          <p class="text-sm font-black text-gray-800"><?= $done ?> of <?= $steps ?></p>
          <p class="text-xs font-semibold text-neutral-500">steps complete</p>
        </div>
      </div>
    </div>
  </section>

  <div class="learn-layout">
    <!-- The lesson -->
    <main class="learn-card min-w-0" id="lesson" aria-labelledby="lesson-title">
      <?php if ($here['type'] === 'module' && $here['state'] === 'locked'): ?>
        <div class="flex flex-col items-center gap-3 px-6 py-16 text-center">
          <span class="flex h-14 w-14 items-center justify-center rounded-full bg-neutral-100 text-neutral-500"><?= icon('clock', 'h-7 w-7') ?></span>
          <h2 id="lesson-title" class="font-serif-heading text-xl font-bold"><?= e($here['title']) ?> is locked</h2>
          <p class="max-w-md text-sm font-medium text-neutral-600">Pass the quiz on the module before it to open this one.</p>
          <?php foreach ($lessons as $key => $l): if ($l['state'] === 'open' && $l['type'] === 'module'): ?>
            <a href="<?= e($lessonUrl($key)) ?>" class="btn-primary">Continue with <?= e($l['title']) ?></a>
          <?php break; endif; endforeach; ?>
        </div>

      <?php elseif ($here['type'] === 'final'): ?>
        <div class="space-y-5 p-5 sm:p-6">
          <div>
            <p class="learn-section-title">Final exam</p>
            <h2 id="lesson-title" class="mt-1 font-serif-heading text-2xl font-bold">Course final exam</h2>
          </div>
          <?php if (!$hasFinal): ?>
            <p class="rounded-lg border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">The tutor has not added the final exam yet.</p>
          <?php elseif (!$modulesComplete): ?>
            <p class="rounded-lg border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">Pass every module quiz to unlock the final exam.</p>
          <?php else: ?>
            <p class="text-sm font-medium text-neutral-600">Score at least <?= (int) ($course['final_pass_percent'] ?? 80) ?>% to complete the course.</p>
            <?php if ($finalPassed): ?>
              <div class="flex flex-wrap items-center gap-3 rounded-xl p-4" style="background:#ecf7f0">
                <span style="color:var(--ke-green)"><?= icon('check', 'h-6 w-6') ?></span>
                <p class="text-sm font-black" style="color:var(--ke-green)">Passed<?= !empty($finalProgress['score']) ? ' · ' . (int) $finalProgress['score'] . '%' : '' ?></p>
                <?php if (!empty($course['certificate_enabled']) && empty($course['requires_attachment']) && $settled): ?>
                  <a href="<?= url('/account/certificate') ?>" class="btn-primary ml-auto">Open My Certificates</a>
                <?php elseif (!$settled): ?>
                  <p class="w-full text-xs font-bold text-neutral-600">Clear your fee balance to complete the course and get the certificate.</p>
                <?php elseif (!empty($course['requires_attachment'])): ?>
                  <p class="w-full text-xs font-bold text-neutral-600">Complete your attachment before this skill appears on your certificate.</p>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/final-exam/submit') ?>" class="space-y-5">
              <?= csrfField() ?>
              <?php $questionsHtml($finalQuestions); ?>
              <button type="submit" class="btn-primary"><?= $finalPassed ? 'Retake final exam' : 'Submit final exam' ?></button>
            </form>
          <?php endif; ?>
        </div>

      <?php else:
        // The introduction or an open module.
        $isIntro = $here['type'] === 'intro';
        $module = $here['module'] ?? null;
        $embed = $isIntro ? ($course['introduction_embed_url'] ?? '') : ($module['embed_url'] ?? '');
        $videoPath = $isIntro ? ($course['introduction_video_path'] ?? '') : ($module['video_path'] ?? '');
        $isYoutube = $isIntro ? (($course['introduction_video_source'] ?? '') === 'youtube') : (($module['video_source'] ?? '') === 'youtube');
        $cover = (string) ($course['cover_image'] ?? '');
        $lessonMaterials = $isIntro ? $materials : (is_array($module['materials'] ?? null) ? $module['materials'] : []);
        $questions = $module['quiz_questions'] ?? [];
      ?>
        <div class="learn-video" oncontextmenu="return false;">
          <?php if ($isYoutube && $embed): ?>
            <iframe src="<?= e($embed) ?>" title="<?= e($here['title']) ?>" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
          <?php elseif ($videoPath): ?>
            <video controls controlsList="nodownload noremoteplayback noplaybackrate" disablePictureInPicture playsinline oncontextmenu="return false;">
              <source src="<?= e(imageUrl($videoPath)) ?>">
            </video>
          <?php elseif ($isIntro && $cover !== '' && preg_match('/\.(jpe?g|png|webp|gif)$/i', $cover)): ?>
            <img src="<?= e(imageUrl($cover)) ?>" alt="">
          <?php else: ?>
            <div class="learn-video-empty"><?= icon('book', 'h-10 w-10') ?><span>No video for this lesson</span></div>
          <?php endif; ?>
        </div>

        <div class="space-y-6 p-5 sm:p-6">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="learn-section-title"><?= $isIntro ? 'Introduction' : 'Module ' . ($here['index'] + 1) . ' of ' . count($modules) ?></p>
              <h2 id="lesson-title" class="mt-1 font-serif-heading text-2xl font-bold"><?= e($here['title']) ?></h2>
              <?php if (!$isIntro): ?><p class="mt-1 text-xs font-bold text-neutral-500"><?= e(CourseModule::formatDuration((int) $module['duration_minutes'])) ?></p><?php endif; ?>
            </div>
            <?php if ($here['state'] === 'done'): ?>
              <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-black" style="background:#ecf7f0;color:var(--ke-green)"><?= icon('check', 'h-4 w-4') ?> Completed</span>
            <?php endif; ?>
          </div>

          <?php if ($isIntro): ?>
            <?php if (!empty($course['introduction_description'])): ?>
              <div class="learn-prose"><?= nl2br(e($course['introduction_description'])) ?></div>
            <?php elseif (!empty($course['description'])): ?>
              <div class="learn-prose"><?= nl2br(e($course['description'])) ?></div>
            <?php endif; ?>
          <?php else: ?>
            <?php foreach (['summary' => 'Overview', 'description' => 'About this module', 'notes' => 'Notes'] as $field => $label): ?>
              <?php if (!empty($module[$field])): ?>
                <section>
                  <h3 class="learn-section-title"><?= $label ?></h3>
                  <div class="learn-prose mt-2"><?= nl2br(e($module[$field])) ?></div>
                </section>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endif; ?>

          <?php if ($lessonMaterials): ?>
            <section>
              <h3 class="learn-section-title"><?= $isIntro ? 'Course materials' : 'Materials' ?></h3>
              <ul class="mt-2 flex flex-wrap gap-2">
                <?php foreach ($lessonMaterials as $file): ?>
                  <li><a href="<?= e($fileHref($file)) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-green)"><?= icon('file', 'h-4 w-4') ?> <?= e($fileName($file)) ?></a></li>
                <?php endforeach; ?>
              </ul>
            </section>
          <?php endif; ?>

          <?php if (!$isIntro && $questions): ?>
            <section class="rounded-xl border p-5 space-y-4" style="border-color:var(--ke-line);background:#fbfcfb">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                  <h3 class="font-serif-heading text-lg font-bold">Module quiz</h3>
                  <p class="text-xs font-semibold text-neutral-500">Score at least <?= (int) $module['pass_percent'] ?>% to open the next module.</p>
                </div>
                <?php if (!empty($module['is_passed'])): ?>
                  <span class="text-sm font-black" style="color:var(--ke-green)">Passed<?= !empty($module['progress']['score']) ? ' · ' . (int) $module['progress']['score'] . '%' : '' ?></span>
                <?php endif; ?>
              </div>
              <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/modules/' . (int) $module['id'] . '/quiz') ?>" class="space-y-5">
                <?= csrfField() ?>
                <?php $questionsHtml($questions); ?>
                <button type="submit" class="btn-primary"><?= !empty($module['is_passed']) ? 'Retake quiz' : 'Submit quiz' ?></button>
              </form>
            </section>
          <?php elseif (!$isIntro): ?>
            <p class="text-xs font-bold" style="color:var(--ke-green)">No quiz on this module, so the next module is open.</p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Previous / next -->
      <nav class="flex items-center justify-between gap-3 border-t px-5 py-4" style="border-color:var(--ke-line)" aria-label="Lesson navigation">
        <?php if ($prevKey): ?>
          <a href="<?= e($lessonUrl($prevKey)) ?>" class="btn-secondary">← <?= e(mb_strimwidth($lessons[$prevKey]['title'], 0, 28, '…')) ?></a>
        <?php else: ?><span></span><?php endif; ?>
        <?php if ($nextKey && $lessons[$nextKey]['state'] !== 'locked'): ?>
          <a href="<?= e($lessonUrl($nextKey)) ?>" class="btn-primary"><?= e(mb_strimwidth($lessons[$nextKey]['title'], 0, 28, '…')) ?> →</a>
        <?php elseif ($nextKey): ?>
          <span class="text-xs font-bold text-neutral-400">Next: <?= e(mb_strimwidth($lessons[$nextKey]['title'], 0, 24, '…')) ?> (locked)</span>
        <?php endif; ?>
      </nav>
    </main>

    <!-- Course content -->
    <aside class="learn-aside space-y-4">
      <nav class="learn-card p-3" aria-label="Course content">
        <div class="flex items-center justify-between px-2 pb-2 pt-1">
          <h2 class="font-serif-heading text-base font-bold">Course content</h2>
          <span class="text-xs font-bold text-neutral-500"><?= $done ?>/<?= $steps ?> done</span>
        </div>
        <ol class="space-y-1">
          <?php $n = 0; foreach ($lessons as $key => $l):
            $isModule = $l['type'] === 'module';
            if ($isModule) { $n++; }
            $classes = 'learn-item' . ($key === $current ? ' is-current' : '') . ($l['state'] === 'done' ? ' is-done' : '') . ($l['state'] === 'locked' ? ' is-locked' : '');
          ?>
            <li>
              <a href="<?= e($lessonUrl($key)) ?>" class="<?= $classes ?>" <?= $key === $current ? 'aria-current="step"' : '' ?>>
                <span class="learn-item-mark" aria-hidden="true">
                  <?php if ($l['state'] === 'done'): ?><?= icon('check', 'h-4 w-4') ?>
                  <?php elseif ($l['state'] === 'locked'): ?><?= icon('clock', 'h-3.5 w-3.5') ?>
                  <?php elseif ($l['type'] === 'intro'): ?><?= icon('book', 'h-3.5 w-3.5') ?>
                  <?php elseif ($l['type'] === 'final'): ?><?= icon('award', 'h-3.5 w-3.5') ?>
                  <?php else: ?><?= $n ?><?php endif; ?>
                </span>
                <span class="min-w-0">
                  <span class="learn-item-title truncate"><?= e($l['title']) ?></span>
                  <span class="learn-item-meta"><?= e($l['meta']) ?><?= $l['state'] === 'locked' ? ' · locked' : '' ?></span>
                </span>
              </a>
            </li>
          <?php endforeach; ?>
        </ol>
      </nav>

      <a href="<?= url('/account/attachment-providers') ?>" class="learn-card app-press flex items-center gap-3 p-4">
        <span class="app-tile-icon shrink-0"><?= icon('briefcase') ?></span>
        <span class="min-w-0">
          <span class="block text-sm font-bold text-gray-800">Attachment</span>
          <span class="block text-xs font-semibold text-neutral-500">Choose where to do your attachment and track it.</span>
        </span>
      </a>
    </aside>
  </div>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
