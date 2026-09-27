<?php
/** One course card. Requires $course, plus $isStudent, $canCreate, $paymentsEnabled and $currentUser in scope. */
$tutorName = trim(($course['first_name'] ?? '') . ' ' . ($course['last_name'] ?? '')) ?: ($course['email'] ?? 'Tutor');
$catName = $course['category_name'] ?? 'Uncategorised';
$orgName = $course['organisation_name'] ?? '';
$feeKsh = (float) ($course['enrollment_fee_ksh'] ?? 0);
$price = $feeKsh > 0 ? 'Ksh ' . number_format($feeKsh, 2) : 'Free';
$isFree = $feeKsh == 0;
$coverImage = $course['cover_image'] ?? null;
// Only a real image file is a cover (a PDF saved by mistake would show a broken image).
$hasCover = is_string($coverImage) && preg_match('/\.(jpe?g|png|webp|gif)$/i', $coverImage);
// A red "New" banner for 14 days after the course was created, then it goes away by itself.
$createdAt = strtotime((string) ($course['created_at'] ?? '')) ?: 0;
$isNew = $createdAt > strtotime('-14 days');
$description = trim((string) ($course['description'] ?? ''));
$isLong = mb_strlen($description) > 110 || mb_strlen((string) $course['title']) > 55;
?>
<article class="course-tile flex h-full w-full flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm transition-shadow duration-200 hover:shadow-md" <?= !empty($extraCard) ? 'data-extra hidden' : '' ?>>
  <!-- Cover Image Proportional 16:9 -->
  <div class="relative w-full aspect-video bg-neutral-100 flex items-center justify-center overflow-hidden shrink-0">
    <?php if ($hasCover): ?>
      <img src="<?= e(imageUrl($coverImage)) ?>" alt="<?= e($course['title']) ?>" loading="lazy" class="w-full h-full object-cover">
    <?php else: ?>
      <div class="w-full h-full bg-gradient-to-br from-green-700 to-green-900 flex items-center justify-center text-white text-5xl font-black opacity-90">
        <?= e(mb_strtoupper(mb_substr(trim((string) $course['title']), 0, 1)) ?: 'C') ?>
      </div>
    <?php endif; ?>
    <!-- Badges overlay -->
    <div class="absolute top-3 left-3 flex flex-col gap-2">
      <span class="bg-black/80 backdrop-blur-sm text-white text-[10px] font-black uppercase px-2 py-1 rounded">
        <?= $isFree ? 'Free' : 'Paid' ?>
      </span>
      <?php if ($isNew): ?>
        <span class="course-new-ribbon">New</span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Body -->
  <div class="p-4 flex flex-col flex-grow">
    <div class="flex items-center justify-between gap-2 mb-1">
      <p class="text-[10px] font-black uppercase tracking-wider text-green-700 truncate"><?= e($catName) ?></p>
      <p class="text-[10px] font-bold text-neutral-500 truncate" title="<?= e($orgName) ?>"><?= e($orgName) ?></p>
    </div>
    <h2 class="course-tile-title mb-2 text-lg font-bold leading-tight text-neutral-900" title="<?= e($course['title']) ?>"><?= e($course['title']) ?></h2>
    
    <div class="mt-auto pt-3 flex flex-col gap-3">
      <p class="course-tile-desc text-xs text-neutral-600"><?= e($description) ?></p>
      <?php if ($isLong): ?>
        <button type="button" class="course-card-more" aria-expanded="false">View more details</button>
      <?php endif; ?>
      
      <div class="flex items-center gap-2">
        <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-[10px] font-bold shrink-0">
          <?= strtoupper(substr($tutorName, 0, 1)) ?>
        </div>
        <p class="text-xs font-semibold text-neutral-700 truncate"><?= e($tutorName) ?></p>
      </div>

      <div class="flex flex-wrap items-center gap-1.5 border-t border-neutral-100 pt-3">
        <?php if (!empty($course['certificate_enabled'])): ?>
          <span class="rounded bg-neutral-100 px-1.5 py-0.5 text-[9px] font-bold uppercase text-neutral-600">Cert</span>
        <?php endif; ?>
        <?php if (!empty($course['requires_attachment'])): ?>
          <span class="rounded bg-green-50 px-1.5 py-0.5 text-[9px] font-bold uppercase text-green-700">Attach</span>
        <?php endif; ?>
        <p class="ml-auto text-sm font-black text-green-700"><?= $price ?></p>
      </div>
    </div>
  </div>

  <!-- Actions Footer -->
  <div class="p-4 pt-0 mt-auto flex flex-col sm:flex-row flex-wrap gap-2">
    <?php if ($isStudent && empty($course['is_enrolled'])): ?>
      <a href="<?= url('/account/courses/' . (int) $course['id']) ?>" class="flex-1 inline-flex justify-center items-center rounded-lg bg-neutral-100 hover:bg-neutral-200 text-neutral-800 text-xs font-bold py-2 px-3 transition-colors">
        View More
      </a>
      <?php if ($paymentsEnabled && (float) ($course['enrollment_fee_ksh'] ?? 0) > 0): ?>
        <a href="<?= url('/account/courses/' . (int) $course['id'] . '/checkout') ?>" class="flex-1 inline-flex justify-center items-center rounded-lg bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-2 px-3 transition-colors shadow-sm">
          Enroll
        </a>
      <?php else: ?>
        <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/enroll') ?>" class="flex-1 flex">
          <?= csrfField() ?>
          <button type="submit" class="w-full inline-flex justify-center items-center rounded-lg bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-2 px-3 transition-colors shadow-sm">
            <?= (float) ($course['enrollment_fee_ksh'] ?? 0) > 0 ? 'Enroll (test)' : 'Enroll' ?>
          </button>
        </form>
      <?php endif; ?>
    <?php else: ?>
      <a href="<?= url('/account/courses/' . (int) $course['id']) ?>" class="flex-1 inline-flex justify-center items-center rounded-lg bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-2 px-3 transition-colors shadow-sm">
        <?= $isStudent ? 'Continue Learning' : 'View Course' ?>
      </a>
    <?php endif; ?>

    <?php if ($canCreate && (int) ($course['trainer_user_id'] ?? 0) === (int) ($currentUser['id'] ?? 0)): ?>
      <div class="w-full flex gap-2 mt-1">
        <a href="<?= url('/account/courses/' . (int) $course['id']) ?>" class="flex-1 inline-flex justify-center items-center rounded-lg border border-neutral-300 hover:bg-neutral-50 text-neutral-700 text-[11px] font-bold py-1.5 px-2 transition-colors">
          Modules
        </a>
        <a href="<?= url('/account/courses/' . (int) $course['id'] . '/edit') ?>" class="flex-1 inline-flex justify-center items-center rounded-lg border border-neutral-300 hover:bg-neutral-50 text-neutral-700 text-[11px] font-bold py-1.5 px-2 transition-colors">
          Edit
        </a>
      </div>
    <?php endif; ?>
  </div>
</article>
