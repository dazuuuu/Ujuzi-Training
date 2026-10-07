<?php
/** One course card. Requires $course, plus $isStudent, $canCreate, $paymentsEnabled and $currentUser in scope. */
$tutorName = trim(($course['first_name'] ?? '') . ' ' . ($course['last_name'] ?? '')) ?: ($course['email'] ?? 'Tutor');
$catName = $course['category_name'] ?? 'Uncategorised';
$orgName = $course['organisation_name'] ?? '';
$feeKsh = (float) ($course['enrollment_fee_ksh'] ?? 0);
$price = $feeKsh > 0 ? 'Ksh ' . number_format($feeKsh) : 'Free';
$coverImage = $course['cover_image'] ?? null;
// Only a real image file is a cover (a PDF saved by mistake would show a broken image).
$hasCover = is_string($coverImage) && preg_match('/\.(jpe?g|png|webp|gif)$/i', $coverImage);
// A red "New" banner for 14 days after the course was created, then it goes away by itself.
$createdAt = strtotime((string) ($course['created_at'] ?? '')) ?: 0;
$isNew = $createdAt > strtotime('-14 days');
$description = trim((string) ($course['description'] ?? ''));
$isLong = mb_strlen($description) > 110 || mb_strlen((string) $course['title']) > 55;
$courseHref = url('/account/courses/' . (int) $course['id']);
$isEnrolledCard = !empty($course['is_enrolled']);
$isCompletedCard = !empty($course['is_completed']);
?>
<article class="course-tile flex h-full w-full min-w-0 flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm transition-shadow duration-200 hover:shadow-md" <?= !empty($extraCard) ? 'data-extra hidden' : '' ?>>
  <a href="<?= $courseHref ?>" class="relative block w-full aspect-video shrink-0 overflow-hidden bg-neutral-100" tabindex="-1" aria-hidden="true">
    <?php if ($hasCover): ?>
      <img src="<?= e(imageUrl($coverImage)) ?>" alt="" loading="lazy" class="h-full w-full object-cover">
    <?php else: ?>
      <span class="flex h-full w-full items-center justify-center bg-gradient-to-br from-green-700 to-green-900 text-4xl font-black text-white opacity-90"><?= e(mb_strtoupper(mb_substr(trim((string) $course['title']), 0, 1)) ?: 'C') ?></span>
    <?php endif; ?>
    <span class="absolute left-2 top-2 flex flex-col items-start gap-1.5">
      <?php if ($isNew): ?><span class="course-new-ribbon">New</span><?php endif; ?>
      <?php if ($isCompletedCard): ?>
        <span class="rounded px-2 py-0.5 text-[10px] font-black uppercase text-white" style="background:var(--ke-green)">Completed</span>
      <?php elseif ($isEnrolledCard): ?>
        <span class="rounded bg-black/75 px-2 py-0.5 text-[10px] font-black uppercase text-white">Enrolled</span>
      <?php endif; ?>
    </span>
  </a>

  <div class="flex min-w-0 flex-grow flex-col p-3 sm:p-4">
    <p class="truncate text-[10px] font-black uppercase tracking-wider text-green-700"><?= e($catName) ?><span class="font-bold normal-case text-neutral-500"> · <?= e($orgName) ?></span></p>
    <h2 class="course-tile-title mt-1 text-sm font-bold leading-tight text-neutral-900 sm:text-base" title="<?= e($course['title']) ?>"><a href="<?= $courseHref ?>"><?= e($course['title']) ?></a></h2>

    <div class="mt-auto flex flex-col gap-2 pt-2">
      <p class="course-tile-desc hidden text-xs text-neutral-600 sm:block"><?= e($description) ?></p>
      <?php if ($isLong): ?>
        <button type="button" class="course-card-more hidden sm:block" aria-expanded="false">View more details</button>
      <?php endif; ?>
      <div class="flex min-w-0 items-center justify-between gap-2 border-t border-neutral-100 pt-2">
        <p class="min-w-0 truncate text-[11px] font-semibold text-neutral-600"><?= e($tutorName) ?></p>
        <p class="shrink-0 text-xs font-black text-green-700 sm:text-sm"><?= $price ?></p>
      </div>
    </div>
  </div>

  <div class="course-tile-actions">
    <?php if ($isStudent && !$isEnrolledCard): ?>
      <a href="<?= $courseHref ?>" class="course-tile-btn">Details</a>
      <?php if ($paymentsEnabled && $feeKsh > 0): ?>
        <a href="<?= url('/account/courses/' . (int) $course['id'] . '/checkout') ?>" class="course-tile-btn is-primary">Enrol</a>
      <?php else: ?>
        <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/enroll') ?>" class="contents">
          <?= csrfField() ?>
          <button type="submit" class="course-tile-btn is-primary">Enrol</button>
        </form>
      <?php endif; ?>
    <?php elseif ($isStudent && $isCompletedCard): ?>
      <a href="<?= $courseHref ?>" class="course-tile-btn is-done"><?= icon('check', 'h-3.5 w-3.5') ?> Completed</a>
      <a href="<?= $courseHref ?>?lesson=about#lesson" class="course-tile-btn">Retake course</a>
    <?php elseif ($isStudent): ?>
      <a href="<?= $courseHref ?>" class="course-tile-btn is-primary is-wide">Continue learning</a>
    <?php else: ?>
      <a href="<?= $courseHref ?>" class="course-tile-btn is-primary is-wide">View course</a>
    <?php endif; ?>

    <?php if ($canCreate && (int) ($course['trainer_user_id'] ?? 0) === (int) ($currentUser['id'] ?? 0)): ?>
      <a href="<?= $courseHref ?>" class="course-tile-btn">Modules</a>
      <a href="<?= url('/account/courses/' . (int) $course['id'] . '/edit') ?>" class="course-tile-btn">Edit</a>
    <?php endif; ?>
  </div>
</article>
