<?php
/**
 * A student's course page, before and after enrolling: the chosen lesson (the
 * course's About page, a module, or the final exam / completion step) in the
 * middle, the course outline on the right.
 * Requires $course, $modules (with is_unlocked / is_passed / progress and,
 * once enrolled, needs_payment / in_history / access), $isEnrolled, $tutor,
 * $paymentsEnabled, $finalProgress, $modulesComplete, $lesson, $feeKsh,
 * $paidKsh, $settled, $plan (ModuleAccess::plan), $prices (each module's
 * share of the fee), $perModule and $walletKsh.
 */
use App\Models\CourseModule;
use App\Services\ModuleAccess;

$plan = $plan ?? ['next_price' => 0, 'from_wallet' => 0, 'from_credit' => 0];
$prices = $prices ?? [];
$perModule = !empty($perModule);
$walletKsh = (float) ($walletKsh ?? 0);
$isEnrolled = !empty($isEnrolled);
$tutor = $tutor ?? null;
$quizResult = $quizResult ?? null;
$runEndsAt = $runEndsAt ?? null;

$modulesComplete = !empty($modulesComplete);
$finalQuestions = $course['final_exam_questions'] ?? [];
// This student's own paper: random questions from the bank, choices shuffled.
$finalPaperQuestions = $finalPaperQuestions ?? [];
$paperCount = (int) ($course['final_paper_size'] ?? 0) ?: count($finalQuestions);
$hasFinal = !empty($finalQuestions);
$finalPassed = $isEnrolled && !empty($finalProgress['passed']);
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
$courseUrl = url('/account/courses/' . (int) $course['id']);
$enrolUrl = $paymentsEnabled && $feeKsh > 0 ? url('/account/courses/' . (int) $course['id'] . '/checkout') : null;

// Every step of the course, in order, with its state: done, open, pay, locked, or enrol (not enrolled yet).
$lessons = ['about' => ['type' => 'about', 'title' => 'About this course', 'meta' => 'Overview, description and materials', 'state' => 'open']];
foreach ($modules as $i => $module) {
    $state = !$isEnrolled ? 'enrol'
        : (!empty($module['is_done']) ? 'done'
        : (empty($module['is_unlocked']) ? 'locked'
        : (!empty($module['needs_payment']) ? 'pay' : 'open')));
    $lessons['m' . (int) $module['id']] = [
        'type' => 'module', 'index' => $i, 'module' => $module, 'title' => $module['title'],
        'meta' => CourseModule::formatDuration((int) $module['duration_minutes']) . (!empty($module['quiz_questions']) ? ' · quiz' : ''),
        'state' => $state,
        'history' => !empty($module['in_history']),
        // What this module costs: what was paid for it, else its share of the fee.
        'price' => $module['access']['price_ksh'] ?? ($prices[$i] ?? 0),
    ];
}
$lessons['final'] = [
    'type' => 'final', 'title' => $hasFinal ? 'Final exam' : 'Complete course',
    'meta' => $hasFinal ? $paperCount . ' questions · 100% to pass' : 'Tick it off after the last module',
    'state' => $finalPassed ? 'done' : ($isEnrolled && $modulesComplete ? 'open' : 'locked'),
];

// Where the student should carry on.
$nextKey = null;
foreach ($lessons as $key => $l) {
    if ($l['type'] === 'module' && in_array($l['state'], ['open', 'pay'], true)) { $nextKey = $key; break; }
}
if ($nextKey === null && $lessons['final']['state'] === 'open') {
    $nextKey = 'final';
}
$started = (bool) array_filter($modules, static fn(array $m): bool => !empty($m['is_done']) || !empty($m['access']));

// Which lesson to show: the one asked for, else About (before starting and once
// finished), else where the student should carry on.
$current = isset($lessons[$lesson]) ? $lesson : null;
if ($current === null) {
    $current = (!$isEnrolled || $finalPassed || !$started || $nextKey === null) ? 'about' : $nextKey;
}
$keys = array_keys($lessons);
$at = array_search($current, $keys, true);
$prevKey = $keys[$at - 1] ?? null;
$followKey = $keys[$at + 1] ?? null;
$here = $lessons[$current];

$steps = count($modules) + ($hasFinal ? 1 : 0);
$done = count(array_filter($modules, static fn(array $m): bool => !empty($m['is_done']))) + ($hasFinal && $finalPassed ? 1 : 0);
$overall = $steps ? (int) round($done * 100 / $steps) : ($finalPassed ? 100 : 0);
$owed = max(0, $feeKsh - $paidKsh);
$lessonUrl = static fn(string $key): string => $courseUrl . '?lesson=' . urlencode($key) . '#lesson';
$tutorName = $tutor ? userDisplayName($tutor) : trim(($course['first_name'] ?? '') . ' ' . ($course['last_name'] ?? ''));
$certUrl = url('/account/certificate?course=' . (int) $course['id']);

/** Renders one quiz / exam question set. */
$questionsHtml = static function (array $questions, bool $required = true): void {
    $req = $required ? ' required' : '';
    foreach ($questions as $qIndex => $question) {
        $type = $question['type'] ?? 'single_choice';
        echo '<fieldset class="space-y-2" data-question><legend class="mb-2 text-sm font-black text-gray-800">' . ($qIndex + 1) . '. ' . e($question['question']) . '</legend>';
        if ($type === 'text') {
            echo '<textarea name="answers[' . (int) $qIndex . ']"' . $req . ' rows="3" class="w-full rounded-lg border border-neutral-300 p-2.5 text-sm" placeholder="Type your answer"></textarea>';
        } else {
            foreach ($question['options'] as $oIndex => $option) {
                $input = $type === 'multiple_choice'
                    ? '<input type="checkbox" name="answers[' . (int) $qIndex . '][]" value="' . (int) $oIndex . '" class="h-4 w-4">'
                    : '<input type="radio" name="answers[' . (int) $qIndex . ']" value="' . (int) $oIndex . '"' . $req . ' class="h-4 w-4">';
                echo '<label class="learn-option">' . $input . '<span>' . e($option) . '</span></label>';
            }
        }
        echo '</fieldset>';
    }
};

/** The last quiz / exam result, shown inside that section only. */
$resultBox = static function (string $key) use ($quizResult): void {
    if (!$quizResult || ($quizResult['key'] ?? '') !== $key) {
        return;
    }
    $ok = !empty($quizResult['passed']);
    echo '<p class="rounded-lg border px-4 py-3 text-sm font-bold" role="status" style="'
        . ($ok ? 'border-color:#b7e1c7;background:#ecf7f0;color:var(--ke-green)' : 'border-color:#fecaca;background:#fef2f2;color:var(--ke-red)')
        . '">' . e((string) $quizResult['message']) . '</p>';
};

/** The enrol button: to the payment plan for a paid course, straight in for a free one. */
$enrolButton = static function (string $label = 'Enrol now', string $class = 'btn-primary') use ($enrolUrl, $course): void {
    if ($enrolUrl) {
        echo '<a href="' . e($enrolUrl) . '" class="' . $class . '">' . icon('check', 'inline h-4 w-4 align-[-2px]') . ' ' . e($label) . '</a>';
        return;
    }
    echo '<form method="post" action="' . url('/account/courses/' . (int) $course['id'] . '/enroll') . '" class="inline">' . csrfField()
        . '<button type="submit" class="' . $class . '">' . icon('check', 'inline h-4 w-4 align-[-2px]') . ' ' . e($label) . '</button></form>';
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
        <a href="<?= url('/account/organisations/' . (int) $course['organisation_id']) ?>" class="underline decoration-dotted"><?= e($course['organisation_name'] ?? '') ?></a><?= !empty($course['category_name']) ? ' · ' . e($course['category_name']) : '' ?>
        <?php if ($tutorName !== ''): ?> · Tutor:
          <?php if ($isEnrolled): ?><a href="<?= url('/account/tutors/' . (int) $course['trainer_user_id']) ?>" class="underline decoration-dotted"><?= e($tutorName) ?></a><?php else: ?><?= e($tutorName) ?><?php endif; ?>
        <?php endif; ?>
      </p>
    </div>
    <div class="flex shrink-0 items-center gap-4">
      <?php if (!$isEnrolled): ?>
        <div class="text-right">
          <p class="text-xs font-bold text-neutral-500">Course fee</p>
          <p class="text-lg font-black" style="color:var(--ke-green)"><?= $feeKsh > 0 ? 'Ksh ' . number_format($feeKsh, 2) : 'Free' ?></p>
          <?php if ($feeKsh > 0): ?><p class="text-xs font-semibold text-neutral-500"><?= ModuleAccess::coins($feeKsh) ?> coins</p><?php endif; ?>
        </div>
      <?php else: ?>
        <?php if ($feeKsh > 0 && !$settled && $owed > 0): ?>
          <div class="text-right">
            <p class="text-xs font-bold text-neutral-500">Balance</p>
            <p class="text-sm font-black" style="color:var(--ke-red)">Ksh <?= number_format($owed, 2) ?></p>
            <a href="<?= url('/account/courses/' . (int) $course['id'] . '/checkout') ?>" class="text-xs font-black underline" style="color:var(--ke-green)">Payment plan</a>
          </div>
        <?php endif; ?>
        <div class="flex items-center gap-3">
          <?php $pct = $overall; $ringSize = 56; $ringLabel = 'Course progress'; require __DIR__ . '/../partials/progress-ring.php'; ?>
          <div>
            <?php if ($finalPassed): ?>
              <p class="text-sm font-black" style="color:var(--ke-green)">Completed</p>
            <?php else: ?>
              <p class="text-sm font-black text-gray-800"><?= $done ?> of <?= $steps ?></p>
            <?php endif; ?>
            <p class="text-xs font-semibold text-neutral-500">steps complete</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <?php if ($isEnrolled && $runEndsAt): ?>
    <p class="text-xs font-semibold text-neutral-500">
      Your access runs until <strong><?= e(date('j M Y', $runEndsAt)) ?></strong>. Until then you can retake the course for free; after that it resets and you pay to take it again.
    </p>
  <?php endif; ?>

  <?php
    // Wallet warning: the next module to pay for costs more than the wallet holds.
    $nextToPay = null;
    foreach ($lessons as $l) { if ($l['state'] === 'pay') { $nextToPay = $l; break; } }
  ?>
  <?php if ($isEnrolled && $perModule && $nextToPay && $plan['from_wallet'] > $walletKsh + 0.001): ?>
    <section class="flex flex-wrap items-center gap-3 rounded-xl border p-4 text-sm font-semibold" role="alert" style="border-color:#f59e0b;background:#fffbeb;color:#92400e">
      <?= icon('alert', 'h-5 w-5 shrink-0') ?>
      <p class="min-w-0 flex-1">Your wallet has <strong><?= ModuleAccess::coins($walletKsh) ?> coins</strong>, but <?= e($nextToPay['title']) ?> needs <strong><?= ModuleAccess::coins($plan['from_wallet']) ?> coins</strong>. You can't continue until you top up.</p>
      <a href="<?= url('/account/wallet') ?>" class="btn-primary shrink-0">Deposit</a>
    </section>
  <?php endif; ?>

  <div class="learn-layout">
    <!-- The lesson -->
    <main class="learn-card min-w-0" id="lesson" aria-labelledby="lesson-title">
      <?php if ($here['type'] === 'about'):
        $embed = $course['introduction_embed_url'] ?? '';
        $videoPath = $course['introduction_video_path'] ?? '';
        $isYoutube = ($course['introduction_video_source'] ?? '') === 'youtube';
        $cover = (string) ($course['cover_image'] ?? '');
        $hasCover = $cover !== '' && preg_match('/\.(jpe?g|png|webp|gif)$/i', $cover);
        $overview = trim((string) ($course['introduction_description'] ?? ''));
        $description = trim((string) ($course['description'] ?? ''));
        $tabs = [];
        if ($overview !== '' || !empty($course['introduction_title']) || $modules) { $tabs['overview'] = 'Course overview'; }
        $tabs['description'] = 'Description';
        if ($materials) { $tabs['materials'] = 'Materials'; }
        if ($tutorName !== '') { $tabs['tutor'] = 'Tutor'; }
      ?>
        <div class="space-y-5 p-5 sm:p-6">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="lesson-title" class="font-serif-heading text-2xl font-bold">About this course</h2>
            <div class="flex flex-wrap items-center gap-2">
              <?php if (!$isEnrolled): ?>
                <?php $enrolButton('Enrol now'); ?>
              <?php elseif ($finalPassed): ?>
                <span class="inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-xs font-black uppercase" style="background:#ecf7f0;color:var(--ke-green)"><?= icon('check', 'h-4 w-4') ?> Completed</span>
                <?php if (!empty($course['certificate_enabled'])): ?><a href="<?= $certUrl ?>" class="btn-primary">View certificate</a><?php endif; ?>
                <?php if ($modules && $runEndsAt && $runEndsAt > time()): ?>
                  <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/retake') ?>" class="inline" onsubmit="return confirm('Start this course again? Your progress resets; your certificate stays.');">
                    <?= csrfField() ?>
                    <button type="submit" class="btn-secondary"><?= icon('refresh', 'inline h-4 w-4 align-[-2px]') ?> Retake course</button>
                  </form>
                <?php endif; ?>
              <?php elseif ($nextKey): ?>
                <a href="<?= e($lessonUrl($nextKey)) ?>" class="btn-primary"><?= $started ? 'Continue: ' . e(mb_strimwidth($lessons[$nextKey]['title'], 0, 30, '…')) : 'Start course' ?> →</a>
              <?php endif; ?>
            </div>
          </div>

          <div class="learn-video rounded-xl" oncontextmenu="return false;">
            <?php if ($isYoutube && $embed): ?>
              <iframe src="<?= e($embed) ?>" title="<?= e($course['introduction_title'] ?: $course['title']) ?>" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            <?php elseif ($videoPath): ?>
              <video controls controlsList="nodownload noremoteplayback noplaybackrate" disablePictureInPicture playsinline oncontextmenu="return false;">
                <source src="<?= e(imageUrl($videoPath)) ?>">
              </video>
            <?php elseif ($hasCover): ?>
              <img src="<?= e(imageUrl($cover)) ?>" alt="">
            <?php else: ?>
              <div class="learn-video-empty" style="background:linear-gradient(135deg,#0f5132,#006b3f)"><span class="font-serif-heading text-5xl font-bold text-white"><?= e(mb_strtoupper(mb_substr(trim($course['title']), 0, 1))) ?></span></div>
            <?php endif; ?>
          </div>

          <div class="course-tabs" role="tablist" data-tabs="about" aria-label="About this course">
            <?php $first = true; foreach ($tabs as $tabKey => $tabLabel): ?>
              <button type="button" role="tab" id="tab-<?= $tabKey ?>" data-tab aria-controls="panel-<?= $tabKey ?>" aria-selected="<?= $first ? 'true' : 'false' ?>" <?= $first ? '' : 'tabindex="-1"' ?>><?= e($tabLabel) ?></button>
            <?php $first = false; endforeach; ?>
          </div>

          <?php $first = true; foreach ($tabs as $tabKey => $tabLabel): ?>
            <section role="tabpanel" id="panel-<?= $tabKey ?>" data-tab-panel="about" aria-labelledby="tab-<?= $tabKey ?>" <?= $first ? '' : 'hidden' ?>>
              <?php if ($tabKey === 'overview'): ?>
                <?php if (!empty($course['introduction_title'])): ?><h3 class="font-bold text-gray-800"><?= e($course['introduction_title']) ?></h3><?php endif; ?>
                <?php if ($overview !== ''): ?><div class="learn-prose mt-2"><?= nl2br(e($overview)) ?></div><?php endif; ?>
                <?php if ($modules): ?>
                  <h3 class="learn-section-title mt-5">Modules</h3>
                  <div class="module-card-row mt-2">
                    <?php foreach ($lessons as $key => $l): if ($l['type'] !== 'module') { continue; }
                      $locked = !in_array($l['state'], ['open', 'done'], true);
                      $cardOverview = trim((string) ($l['module']['summary'] ?? ''));
                    ?>
                      <a href="<?= e($lessonUrl($key)) ?>" class="module-card<?= $locked ? ' is-locked' : '' ?>">
                        <span class="flex items-center justify-between gap-2">
                          <span class="text-[11px] font-black uppercase text-neutral-500">Module <?= $l['index'] + 1 ?></span>
                          <span aria-hidden="true" style="color:<?= $locked ? '#9ca3af' : 'var(--ke-green)' ?>"><?= icon($l['state'] === 'done' ? 'check' : ($locked ? 'lock' : 'unlock'), 'h-4 w-4') ?></span>
                        </span>
                        <span class="mt-1 block text-sm font-bold text-gray-800"><?= e($l['title']) ?></span>
                        <?php if ($cardOverview !== ''): ?><span class="mt-1 block text-xs font-medium text-neutral-600"><?= e(mb_strimwidth($cardOverview, 0, 120, '…')) ?></span><?php endif; ?>
                        <span class="mt-2 block text-[11px] font-bold text-neutral-500"><?= e($l['meta']) ?><?= $l['state'] === 'pay' ? ' · pay to open' : ($l['state'] === 'enrol' ? ' · enrol to open' : '') ?></span>
                      </a>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              <?php elseif ($tabKey === 'description'): ?>
                <?php if ($description !== ''): ?>
                  <div class="learn-prose"><?= nl2br(e($description)) ?></div>
                <?php else: ?>
                  <p class="text-sm font-semibold text-neutral-500">The tutor hasn't added a description yet.</p>
                <?php endif; ?>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                  <div class="rounded-lg border p-3" style="border-color:var(--ke-line)"><dt class="text-[11px] font-black uppercase text-neutral-500">Modules</dt><dd class="font-black text-gray-800"><?= count($modules) ?: 'None' ?></dd></div>
                  <div class="rounded-lg border p-3" style="border-color:var(--ke-line)"><dt class="text-[11px] font-black uppercase text-neutral-500">Final exam</dt><dd class="font-black text-gray-800"><?= $hasFinal ? count($finalQuestions) . ' questions' : 'None' ?></dd></div>
                  <div class="rounded-lg border p-3" style="border-color:var(--ke-line)"><dt class="text-[11px] font-black uppercase text-neutral-500">Certificate</dt><dd class="font-black text-gray-800"><?= !empty($course['certificate_enabled']) ? 'Yes' : 'No' ?></dd></div>
                </dl>
              <?php elseif ($tabKey === 'materials'): ?>
                <?php if ($isEnrolled): ?>
                  <ul class="flex flex-wrap gap-2">
                    <?php foreach ($materials as $file): ?>
                      <li><a href="<?= e($fileHref($file)) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-green)"><?= icon('file', 'h-4 w-4') ?> <?= e($fileName($file)) ?></a></li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <ul class="space-y-1.5">
                    <?php foreach ($materials as $file): ?>
                      <li class="flex items-center gap-2 text-sm font-semibold text-neutral-600"><?= icon('lock', 'h-4 w-4 shrink-0') ?> <?= e($fileName($file)) ?></li>
                    <?php endforeach; ?>
                  </ul>
                  <p class="mt-3 text-xs font-bold text-neutral-500">Enrol to download the course materials.</p>
                <?php endif; ?>
              <?php elseif ($tabKey === 'tutor'): ?>
                <div class="flex flex-wrap items-center gap-4">
                  <?php if (!empty($tutor['photo_path'])): ?>
                    <img src="<?= e(imageUrl($tutor['photo_path'])) ?>" alt="" class="h-16 w-16 shrink-0 rounded-full object-cover">
                  <?php else: ?>
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full text-2xl font-black text-white" style="background:var(--ke-green)" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($tutorName, 0, 1))) ?></span>
                  <?php endif; ?>
                  <div class="min-w-0 flex-1">
                    <p class="font-black text-gray-800"><?= e($tutorName) ?></p>
                    <?php if (!empty($tutor['headline'])): ?><p class="text-sm font-semibold text-neutral-600"><?= e($tutor['headline']) ?></p><?php endif; ?>
                    <?php if (!$isEnrolled): ?><p class="mt-1 text-xs font-bold text-neutral-500">Enrol to see the tutor's contacts and experience.</p><?php endif; ?>
                  </div>
                  <?php if ($isEnrolled): ?><a href="<?= url('/account/tutors/' . (int) $course['trainer_user_id']) ?>" class="btn-secondary">View tutor</a><?php endif; ?>
                </div>
                <?php if (!empty($tutor['bio'])): ?><div class="learn-prose mt-3"><?= nl2br(e(mb_strimwidth((string) $tutor['bio'], 0, 400, '…'))) ?></div><?php endif; ?>
              <?php endif; ?>
            </section>
          <?php $first = false; endforeach; ?>
        </div>

      <?php elseif ($here['type'] === 'module' && $here['state'] === 'enrol'): ?>
        <div class="flex flex-col items-center gap-4 px-6 py-14 text-center">
          <span class="app-tile-icon" style="width:3.5rem;height:3.5rem"><?= icon('lock', 'h-7 w-7') ?></span>
          <div>
            <p class="learn-section-title">Module <?= $here['index'] + 1 ?> of <?= count($modules) ?></p>
            <h2 id="lesson-title" class="mt-1 font-serif-heading text-2xl font-bold"><?= e($here['title']) ?></h2>
          </div>
          <?php if (!empty($here['module']['summary'])): ?><div class="max-w-md text-left"><p class="learn-section-title">Module overview</p><p class="mt-1 text-sm font-medium text-neutral-600"><?= nl2br(e($here['module']['summary'])) ?></p></div><?php endif; ?>
          <p class="text-sm font-bold text-neutral-600"><?= $here['price'] > 0 ? 'Module cost: ' . ModuleAccess::coins((float) $here['price']) . ' coins, paid when you open it.' : 'Enrol to open this module.' ?></p>
          <?php $enrolButton('Enrol now'); ?>
        </div>

      <?php elseif ($here['type'] === 'module' && $here['state'] === 'locked'): ?>
        <div class="flex flex-col items-center gap-3 px-6 py-16 text-center">
          <span class="flex h-14 w-14 items-center justify-center rounded-full bg-neutral-100 text-neutral-500"><?= icon('lock', 'h-7 w-7') ?></span>
          <h2 id="lesson-title" class="font-serif-heading text-xl font-bold"><?= e($here['title']) ?> is locked</h2>
          <?php if (!empty($here['module']['summary'])): ?><div class="max-w-md text-left"><p class="learn-section-title">Module overview</p><p class="mt-1 text-sm font-medium text-neutral-600"><?= nl2br(e($here['module']['summary'])) ?></p></div><?php endif; ?>
          <p class="max-w-md text-sm font-medium text-neutral-600">Pass the module before it to open this one.</p>
          <?php if ($nextKey && $nextKey !== 'final'): ?>
            <a href="<?= e($lessonUrl($nextKey)) ?>" class="btn-primary">Continue with <?= e($lessons[$nextKey]['title']) ?></a>
          <?php endif; ?>
        </div>

      <?php elseif ($here['type'] === 'final'): ?>
        <div class="space-y-5 p-5 sm:p-6">
          <div>
            <p class="learn-section-title"><?= $hasFinal ? 'Final exam' : 'Last step' ?></p>
            <h2 id="lesson-title" class="mt-1 font-serif-heading text-2xl font-bold"><?= $hasFinal ? 'Course final exam' : 'Complete the course' ?></h2>
          </div>
          <?php if (!$isEnrolled): ?>
            <p class="rounded-lg border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">Enrol and finish every module to reach this step.</p>
            <?php $enrolButton('Enrol now'); ?>
          <?php elseif (!$modulesComplete): ?>
            <p class="rounded-lg border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)"><?= $hasFinal ? 'Pass every module to unlock the final exam.' : 'Finish every module, then tick the course completed here.' ?></p>
          <?php elseif (!$hasFinal): ?>
            <?php if ($finalPassed): ?>
              <div class="flex flex-wrap items-center gap-3 rounded-xl p-4" style="background:#ecf7f0">
                <span style="color:var(--ke-green)"><?= icon('check', 'h-6 w-6') ?></span>
                <p class="text-sm font-black" style="color:var(--ke-green)">Course completed</p>
                <?php if (!empty($course['certificate_enabled'])): ?><a href="<?= $certUrl ?>" class="btn-primary ml-auto">View certificate</a><?php endif; ?>
              </div>
            <?php elseif (!$settled): ?>
              <p class="rounded-lg border p-4 text-sm font-bold" style="border-color:#f59e0b;background:#fffbeb;color:#92400e">Clear your balance of Ksh <?= number_format($owed, 2) ?> to complete the course.</p>
            <?php else: ?>
              <p class="text-sm font-medium text-neutral-600">This course has no final exam. You've finished every module — tick it completed to get your certificate.</p>
              <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/complete') ?>">
                <?= csrfField() ?>
                <button type="submit" class="btn-primary"><?= icon('check', 'inline h-4 w-4 align-[-2px]') ?> Mark course as completed</button>
              </form>
            <?php endif; ?>
          <?php elseif (!$settled && !$finalPassed): ?>
            <p class="rounded-lg border p-4 text-sm font-bold" style="border-color:#f59e0b;background:#fffbeb;color:#92400e">Clear your balance of Ksh <?= number_format($owed, 2) ?> before the final exam.</p>
          <?php else: ?>
            <?php $resultBox('final'); ?>
            <?php $attachmentGrade = !empty($course['requires_attachment']) ? \App\Models\AttachmentAssessment::gradeForStudentCourse((int) ($currentUser['id'] ?? 0), (int) $course['id']) : null; ?>
            <?php if ($attachmentGrade !== null): ?>
              <p class="rounded-lg border px-3 py-2 text-sm font-bold" style="border-color:var(--ke-line)">Attachment assessment: <span style="color:var(--ke-green)"><?= rtrim(rtrim(number_format($attachmentGrade, 1), '0'), '.') ?>%</span> — sent by your attachment organisation and counted with this exam.</p>
            <?php endif; ?>
            <p class="text-sm font-medium text-neutral-600">Answer every question, then submit once at the end. You'll get your score after submitting. Your paper is your own — its questions and their order differ for every student. You need 100% to complete the course and get its certificate.</p>
            <?php if ($finalPassed): ?>
              <div class="flex flex-wrap items-center gap-3 rounded-xl p-4" style="background:#ecf7f0">
                <span style="color:var(--ke-green)"><?= icon('check', 'h-6 w-6') ?></span>
                <p class="text-sm font-black" style="color:var(--ke-green)">Passed<?= !empty($finalProgress['score']) ? ' · ' . (int) $finalProgress['score'] . '%' : '' ?></p>
                <?php if (!empty($course['certificate_enabled']) && empty($course['requires_attachment']) && $settled): ?>
                  <a href="<?= $certUrl ?>" class="btn-primary ml-auto">View certificate</a>
                <?php elseif (!$settled): ?>
                  <p class="w-full text-xs font-bold text-neutral-600">Clear your fee balance to complete the course and get the certificate.</p>
                <?php elseif (!empty($course['requires_attachment'])): ?>
                  <p class="w-full text-xs font-bold text-neutral-600">Complete your attachment before this skill appears on your certificate.</p>
                <?php endif; ?>
              </div>
              <p class="text-xs font-semibold text-neutral-500">A passed exam can't be taken again.</p>
            <?php else: ?>
              <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/final-exam/submit') ?>" class="space-y-5" id="final-exam-form" novalidate data-stepper>
                <?= csrfField() ?>
                <?php $questionsHtml($finalPaperQuestions ?: $finalQuestions, false); ?>
                <button type="submit" class="btn-primary">Submit final exam</button>
              </form>
              <script>
                (function () {
                  var form = document.getElementById('final-exam-form');
                  form.addEventListener('submit', function (event) {
                    var unanswered = 0;
                    form.querySelectorAll('[data-question]').forEach(function (q) {
                      var text = q.querySelector('textarea');
                      var answered = text ? text.value.trim() !== '' : !!q.querySelector('input:checked');
                      if (!answered) { unanswered++; }
                    });
                    var msg = unanswered
                      ? unanswered + ' question' + (unanswered === 1 ? ' is' : 's are') + ' not answered. Submit the exam anyway?'
                      : 'Submit your final exam? You can\'t change your answers after submitting.';
                    if (!confirm(msg)) { event.preventDefault(); }
                  });
                })();
              </script>
            <?php endif; ?>
          <?php endif; ?>
        </div>

      <?php elseif ($here['type'] === 'module' && $here['state'] === 'pay'):
        $short = $plan['from_wallet'] > $walletKsh + 0.001;
      ?>
        <div class="flex flex-col items-center gap-4 px-6 py-14 text-center">
          <span class="app-tile-icon" style="width:3.5rem;height:3.5rem"><?= icon('lock', 'h-7 w-7') ?></span>
          <div>
            <p class="learn-section-title">Module <?= $here['index'] + 1 ?> of <?= count($modules) ?> · locked until paid</p>
            <h2 id="lesson-title" class="mt-1 font-serif-heading text-2xl font-bold"><?= e($here['title']) ?></h2>
          </div>
          <?php if (!empty($here['module']['summary'])): ?><div class="max-w-md text-left"><p class="learn-section-title">Module overview</p><p class="mt-1 text-sm font-medium text-neutral-600"><?= nl2br(e($here['module']['summary'])) ?></p></div><?php endif; ?>
          <p class="max-w-md text-sm font-medium text-neutral-600">
            This module costs <strong><?= ModuleAccess::coins($plan['next_price']) ?> coins</strong> (Ksh <?= number_format($plan['next_price'], 2) ?>).
            <?php if ($plan['from_credit'] > 0): ?>
              Ksh <?= number_format($plan['from_credit'], 2) ?> comes from money you already paid for this course<?= $plan['from_wallet'] > 0 ? ', and ' . ModuleAccess::coins($plan['from_wallet']) . ' coins from your wallet' : '' ?>.
            <?php endif; ?>
            Once unlocked it stays open for <?= ModuleAccess::ACCESS_DAYS ?> days.
          </p>
          <p class="text-xs font-bold text-neutral-500">Wallet: <?= ModuleAccess::coins($walletKsh) ?> coins (Ksh <?= number_format($walletKsh, 2) ?>)</p>
          <?php if ($short): ?>
            <p class="rounded-lg border px-4 py-2 text-sm font-bold" role="alert" style="border-color:#f59e0b;background:#fffbeb;color:#92400e">Not enough coins — deposit <?= ModuleAccess::coins($plan['from_wallet'] - $walletKsh) ?> more to open this module.</p>
            <a href="<?= url('/account/wallet') ?>" class="btn-primary">Deposit to My wallet</a>
          <?php else: ?>
            <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/modules/' . (int) $here['module']['id'] . '/unlock') ?>">
              <?= csrfField() ?>
              <button type="submit" class="btn-primary">Unlock module<?= $plan['from_wallet'] > 0 ? ' · ' . ModuleAccess::coins($plan['from_wallet']) . ' coins' : '' ?></button>
            </form>
          <?php endif; ?>
        </div>

      <?php else:
        // An open or finished module.
        $module = $here['module'];
        $embed = $module['embed_url'] ?? '';
        $videoPath = $module['video_path'] ?? '';
        $isYoutube = ($module['video_source'] ?? '') === 'youtube';
        $lessonMaterials = is_array($module['materials'] ?? null) ? $module['materials'] : [];
        $questions = $module['quiz_questions'] ?? [];
      ?>
        <?php if (($isYoutube && $embed) || $videoPath): ?>
          <div class="learn-video" oncontextmenu="return false;">
            <?php if ($isYoutube && $embed): ?>
              <iframe src="<?= e($embed) ?>" title="<?= e($here['title']) ?>" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            <?php else: ?>
              <video controls controlsList="nodownload noremoteplayback noplaybackrate" disablePictureInPicture playsinline oncontextmenu="return false;">
                <source src="<?= e(imageUrl($videoPath)) ?>">
              </video>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="space-y-6 p-5 sm:p-6">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="learn-section-title">Module <?= $here['index'] + 1 ?> of <?= count($modules) ?></p>
              <h2 id="lesson-title" class="mt-1 font-serif-heading text-2xl font-bold"><?= e($here['title']) ?></h2>
              <p class="mt-1 text-xs font-bold text-neutral-500"><?= e(CourseModule::formatDuration((int) $module['duration_minutes'])) ?></p>
            </div>
            <?php if ($here['state'] === 'done'): ?>
              <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-black" style="background:#ecf7f0;color:var(--ke-green)"><?= icon('check', 'h-4 w-4') ?> Completed</span>
            <?php endif; ?>
          </div>

          <?php
            // The module's parts as tabs right under the video: overview, about, notes, materials, then the quiz.
            $moduleTabs = [];
            foreach (['summary' => 'Overview', 'description' => 'About', 'notes' => 'Notes'] as $field => $label) {
                if (!empty($module[$field])) { $moduleTabs[$field] = $label; }
            }
            if ($lessonMaterials) { $moduleTabs['materials'] = 'Materials'; }
            $moduleTabs['quiz'] = $questions ? 'Quiz' : 'Finish';
            $firstTab = ($quizResult && ($quizResult['key'] ?? '') === 'm' . (int) $module['id']) ? 'quiz' : array_key_first($moduleTabs);
          ?>
          <div class="course-tabs" role="tablist" data-tabs="module" aria-label="This module">
            <?php foreach ($moduleTabs as $tabKey => $tabLabel): $on = $tabKey === $firstTab; ?>
              <button type="button" role="tab" id="mtab-<?= $tabKey ?>" data-tab aria-controls="mpanel-<?= $tabKey ?>" aria-selected="<?= $on ? 'true' : 'false' ?>" <?= $on ? '' : 'tabindex="-1"' ?>><?= e($tabLabel) ?></button>
            <?php endforeach; ?>
          </div>

          <?php foreach (['summary', 'description', 'notes'] as $field): if (!isset($moduleTabs[$field])) { continue; } ?>
            <section role="tabpanel" id="mpanel-<?= $field ?>" data-tab-panel="module" aria-labelledby="mtab-<?= $field ?>" <?= $field === $firstTab ? '' : 'hidden' ?>>
              <div class="learn-prose"><?= nl2br(e($module[$field])) ?></div>
            </section>
          <?php endforeach; ?>

          <?php if ($lessonMaterials): ?>
          <section role="tabpanel" id="mpanel-materials" data-tab-panel="module" aria-labelledby="mtab-materials" <?= $firstTab === 'materials' ? '' : 'hidden' ?>>
            <section>
              <h3 class="learn-section-title">Materials</h3>
              <ul class="mt-2 flex flex-wrap gap-2">
                <?php foreach ($lessonMaterials as $file): ?>
                  <li><a href="<?= e($fileHref($file)) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-green)"><?= icon('file', 'h-4 w-4') ?> <?= e($fileName($file)) ?></a></li>
                <?php endforeach; ?>
              </ul>
            </section>
          </section>
          <?php endif; ?>

          <section role="tabpanel" id="mpanel-quiz" data-tab-panel="module" aria-labelledby="mtab-quiz" <?= $firstTab === 'quiz' ? '' : 'hidden' ?>>
          <?php if ($questions): ?>
            <section id="quiz" class="rounded-xl border p-5 space-y-4" style="border-color:var(--ke-line);background:#fbfcfb">
              <?php $resultBox($here['type'] === 'module' ? 'm' . (int) $module['id'] : ''); ?>
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                  <h3 class="font-serif-heading text-lg font-bold">Module quiz</h3>
                  <p class="text-xs font-semibold text-neutral-500">Score at least <?= (int) $module['pass_percent'] ?>% to open the next module.</p>
                </div>
                <?php if (!empty($module['is_passed'])): ?>
                  <span class="text-sm font-black" style="color:var(--ke-green)">Passed<?= !empty($module['progress']['score']) ? ' · ' . (int) $module['progress']['score'] . '%' : '' ?></span>
                <?php endif; ?>
              </div>
              <?php if (!empty($module['is_passed'])): ?>
                <p class="text-xs font-semibold text-neutral-500">You passed this quiz. A passed quiz can't be taken again — you can keep reading the module.</p>
                <?php if ($followKey && !in_array($lessons[$followKey]['state'], ['locked', 'enrol'], true)): ?>
                  <a href="<?= e($lessonUrl($followKey)) ?>" class="btn-primary inline-block">Go to <?= e(mb_strimwidth($lessons[$followKey]['title'], 0, 30, '…')) ?> →</a>
                <?php endif; ?>
              <?php else: ?>
                <?php if (!empty($module['progress'])): ?>
                  <p class="rounded-lg border px-3 py-2 text-xs font-bold" style="border-color:#fde68a;background:#fffbeb;color:#92400e">Last score <?= (int) ($module['progress']['score'] ?? 0) ?>%. Pass this quiz to open the next module — try again below.</p>
                <?php endif; ?>
                <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/modules/' . (int) $module['id'] . '/quiz') ?>" class="space-y-5" novalidate data-stepper>
                  <?= csrfField() ?>
                  <?php $questionsHtml($questions); ?>
                  <button type="submit" class="btn-primary">Submit quiz</button>
                </form>
              <?php endif; ?>
            </section>
          <?php elseif (empty($module['is_done'])): ?>
            <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/modules/' . (int) $module['id'] . '/done') ?>" class="flex flex-wrap items-center gap-3 rounded-xl border p-4" style="border-color:var(--ke-line);background:#fbfcfb">
              <?= csrfField() ?>
              <p class="min-w-0 flex-1 text-sm font-semibold text-neutral-600">This module has no quiz. When you've read it, tick it done to move on.</p>
              <button type="submit" class="btn-primary"><?= icon('check', 'inline h-4 w-4 align-[-2px]') ?> Mark as done</button>
            </form>
          <?php endif; ?>
          </section>
        </div>
      <?php endif; ?>

      <!-- Previous / next -->
      <nav class="flex items-center justify-between gap-3 border-t px-5 py-4" style="border-color:var(--ke-line)" aria-label="Lesson navigation">
        <?php if ($prevKey): ?>
          <a href="<?= e($lessonUrl($prevKey)) ?>" class="btn-secondary">← <?= e(mb_strimwidth($lessons[$prevKey]['title'], 0, 24, '…')) ?></a>
        <?php else: ?><span></span><?php endif; ?>
        <?php if ($followKey && !in_array($lessons[$followKey]['state'], ['locked', 'enrol'], true)): ?>
          <a href="<?= e($lessonUrl($followKey)) ?>" class="btn-primary"><?= e(mb_strimwidth($lessons[$followKey]['title'], 0, 24, '…')) ?> →</a>
        <?php elseif ($followKey): ?>
          <span class="text-right text-xs font-bold text-neutral-400">Next: <?= e(mb_strimwidth($lessons[$followKey]['title'], 0, 22, '…')) ?> (locked)</span>
        <?php endif; ?>
      </nav>
    </main>

    <!-- Course outline -->
    <aside class="learn-aside space-y-4">
      <nav class="learn-card p-3" aria-label="Course outline">
        <div class="flex items-center justify-between px-2 pb-2 pt-1">
          <h2 class="text-xs font-black uppercase tracking-widest text-gray-800">Course outline</h2>
          <?php if ($isEnrolled && $steps): ?><span class="text-xs font-bold text-neutral-500"><?= $done ?>/<?= $steps ?> done</span><?php endif; ?>
        </div>
        <a href="<?= e($lessonUrl('about')) ?>" class="learn-item<?= $current === 'about' ? ' is-current' : '' ?>" <?= $current === 'about' ? 'aria-current="step"' : '' ?>>
          <span class="learn-item-mark" aria-hidden="true"><?= icon('book', 'h-3.5 w-3.5') ?></span>
          <span class="min-w-0"><span class="learn-item-title">About this course</span><span class="learn-item-meta">Overview, description, materials</span></span>
        </a>

        <?php if (!$modules): ?>
          <p class="px-2 py-3 text-xs font-semibold text-neutral-500">This course has no modules<?= $hasFinal ? ' — go straight to the final exam.' : '.' ?></p>
        <?php endif; ?>
        <ol class="outline-list">
          <?php foreach ($lessons as $key => $l):
            if ($l['type'] !== 'module' || !empty($l['history'])) { continue; }
            $m = $l['module'];
            $n = $l['index'] + 1;
            $state = $l['state'];
            $open = in_array($state, ['open', 'done'], true);
            $showCost = $feeKsh > 0 && (float) $l['price'] > 0 && in_array($state, ['enrol', 'pay', 'locked'], true);
            $summaryText = trim((string) ($m['summary'] ?? '')) ?: trim((string) ($m['description'] ?? ''));
          ?>
            <li class="outline-row<?= $key === $current ? ' is-current' : '' ?>">
              <span class="outline-lock<?= $open ? ' is-open' : '' ?><?= $state === 'done' ? ' is-done' : '' ?>" aria-hidden="true">
                <?= icon($state === 'done' ? 'check' : ($open ? 'unlock' : 'lock'), 'h-5 w-5') ?>
              </span>
              <div class="min-w-0 flex-1">
                <a href="<?= e($lessonUrl($key)) ?>" class="outline-title" <?= $key === $current ? 'aria-current="step"' : '' ?>><?= $n ?>. <?= e($l['title']) ?></a>
                <p class="learn-item-meta"><?= e($l['meta']) ?><?= $state === 'done' ? ' · completed' : '' ?></p>
                <?php if ($showCost): ?>
                  <p class="outline-cost">Module cost: <?= ModuleAccess::coins((float) $l['price']) ?> coin<?= ModuleAccess::coins((float) $l['price']) === '1' ? '' : 's' ?></p>
                <?php endif; ?>
                <div class="outline-actions">
                  <?php if ($state === 'enrol'): ?>
                    <?php if ($enrolUrl): ?>
                      <a href="<?= e($enrolUrl) ?>" class="outline-btn is-primary">Enrol now</a>
                    <?php else: ?>
                      <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/enroll') ?>"><?= csrfField() ?><button type="submit" class="outline-btn is-primary">Enrol now</button></form>
                    <?php endif; ?>
                  <?php elseif ($state === 'pay'): ?>
                    <a href="<?= e($lessonUrl($key)) ?>" class="outline-btn is-primary">Unlock</a>
                  <?php elseif ($state === 'open'): ?>
                    <a href="<?= e($lessonUrl($key)) ?>" class="outline-btn is-primary"><?= !empty($m['progress']) ? 'Continue' : 'Start' ?></a>
                  <?php elseif ($state === 'done'): ?>
                    <a href="<?= e($lessonUrl($key)) ?>" class="outline-btn">Review</a>
                  <?php else: ?>
                    <span class="outline-btn is-disabled">Locked</span>
                  <?php endif; ?>
                  <?php if ($summaryText !== ''): ?>
                    <details class="outline-summary">
                      <summary class="outline-btn">Module summary</summary>
                      <p><?= nl2br(e(mb_strimwidth($summaryText, 0, 500, '…'))) ?></p>
                    </details>
                  <?php endif; ?>
                </div>
              </div>
            </li>
          <?php endforeach; ?>

          <?php if ($hasFinal || $isEnrolled):
            $f = $lessons['final'];
          ?>
            <li class="outline-row<?= $current === 'final' ? ' is-current' : '' ?>">
              <span class="outline-lock<?= $f['state'] !== 'locked' ? ' is-open' : '' ?><?= $f['state'] === 'done' ? ' is-done' : '' ?>" aria-hidden="true"><?= icon($f['state'] === 'done' ? 'check' : 'award', 'h-5 w-5') ?></span>
              <div class="min-w-0 flex-1">
                <a href="<?= e($lessonUrl('final')) ?>" class="outline-title"><?= e($f['title']) ?></a>
                <p class="learn-item-meta"><?= e($f['meta']) ?><?= $f['state'] === 'locked' ? ' · after the last module' : '' ?></p>
              </div>
            </li>
          <?php endif; ?>
        </ol>
      </nav>

      <?php $historyLessons = array_filter($lessons, static fn(array $l): bool => !empty($l['history'])); ?>
      <?php if ($historyLessons): ?>
        <nav class="learn-card p-3" aria-label="History">
          <div class="px-2 pb-2 pt-1">
            <h2 class="font-serif-heading text-base font-bold">History</h2>
            <p class="text-xs font-semibold text-neutral-500">Modules you finished more than <?= ModuleAccess::ACCESS_DAYS ?> days after paying. Open one to read it again.</p>
          </div>
          <ol class="space-y-1">
            <?php foreach ($historyLessons as $key => $l): ?>
              <li>
                <a href="<?= e($lessonUrl($key)) ?>" class="learn-item is-done<?= $key === $current ? ' is-current' : '' ?>">
                  <span class="learn-item-mark" aria-hidden="true"><?= icon('check', 'h-4 w-4') ?></span>
                  <span class="min-w-0"><span class="learn-item-title truncate"><?= e($l['title']) ?></span><span class="learn-item-meta">Module <?= $l['index'] + 1 ?> · revisit</span></span>
                </a>
              </li>
            <?php endforeach; ?>
          </ol>
        </nav>
      <?php endif; ?>

      <?php if ($isEnrolled): ?>
        <a href="<?= url('/account/attachment-providers') ?>" class="learn-card app-press flex items-center gap-3 p-4">
          <span class="app-tile-icon shrink-0"><?= icon('briefcase') ?></span>
          <span class="min-w-0">
            <span class="block text-sm font-bold text-gray-800">Attachment</span>
            <span class="block text-xs font-semibold text-neutral-500">Choose where to do your attachment and track it.</span>
          </span>
        </a>
      <?php endif; ?>
    </aside>
  </div>
</div>

<script>
// Quizzes and exams one question at a time: the first question shows, picking
// an answer moves on by itself; Back / Next for the rest, Submit on the last.
(function () {
  document.querySelectorAll('form[data-stepper]').forEach(function (form) {
    var steps = Array.prototype.slice.call(form.querySelectorAll('[data-question]'));
    if (steps.length < 2) return;
    var submit = form.querySelector('button[type=submit]');
    var at = 0;
    var bar = document.createElement('div');
    bar.className = 'quiz-stepper';
    bar.innerHTML = '<div class="quiz-progress"><span></span></div><div class="quiz-nav"><button type="button" class="btn-secondary" data-back>← Back</button><span class="quiz-count"></span><button type="button" class="btn-primary" data-next>Next →</button></div>';
    form.insertBefore(bar.firstChild, steps[0]);
    form.insertBefore(bar.firstChild, submit);
    var fill = form.querySelector('.quiz-progress span');
    var back = form.querySelector('[data-back]'), next = form.querySelector('[data-next]'), count = form.querySelector('.quiz-count');
    function answered(q) { var t = q.querySelector('textarea'); return t ? t.value.trim() !== '' : !!q.querySelector('input:checked'); }
    function show(i) {
      at = Math.max(0, Math.min(steps.length - 1, i));
      steps.forEach(function (q, n) { q.hidden = n !== at; });
      count.textContent = 'Question ' + (at + 1) + ' of ' + steps.length;
      fill.style.width = Math.round((at + 1) * 100 / steps.length) + '%';
      back.disabled = at === 0;
      var last = at === steps.length - 1;
      next.hidden = last; submit.hidden = !last;
    }
    back.addEventListener('click', function () { show(at - 1); });
    next.addEventListener('click', function () {
      if (!answered(steps[at])) { steps[at].classList.add('quiz-missing'); return; }
      show(at + 1);
    });
    form.addEventListener('change', function (e) {
      var q = e.target.closest('[data-question]');
      if (!q) return;
      q.classList.remove('quiz-missing');
      // One answer to pick: go on by itself after a short pause.
      if (e.target.type === 'radio' && steps.indexOf(q) === at && at < steps.length - 1) { setTimeout(function () { show(at + 1); }, 350); }
    });
    form.addEventListener('submit', function (e) {
      var missing = steps.findIndex(function (q) { return !answered(q); });
      if (missing !== -1 && !form.id) { e.preventDefault(); show(missing); steps[missing].classList.add('quiz-missing'); }
    });
    show(0);
  });
})();
</script>
<style>
  .quiz-progress{height:6px;border-radius:99px;background:#eef0ee;overflow:hidden;}
  .quiz-progress span{display:block;height:100%;background:var(--ke-green);transition:width .2s ease;}
  .quiz-nav{display:flex;align-items:center;justify-content:space-between;gap:8px;}
  .quiz-count{font-size:.78rem;font-weight:800;color:#6b7280;}
  .quiz-missing legend::after{content:' — choose an answer';color:var(--ke-red);font-size:.75rem;}
</style>
<?php require __DIR__ . '/../layout-footer.php'; ?>
