<?php
$courses = $courses ?? [];
$branches = $branches ?? [];
$canCreate = !empty($canCreate);
$isStudent = !empty($isStudent);
$isOrgAdmin = ($currentUser['role_slug'] ?? '') === 'organisation_admin';
$paymentsEnabled = !empty($paymentsEnabled);
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Learning</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold">Courses</h1>
      <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600"><?php
        if ($isStudent) {
            echo 'These are published courses for the organisation you selected, plus any global courses available to every student.';
        } elseif ($canCreate) {
            echo 'Create a course from the form Super Admin assigned to tutors. After saving, add modules with videos, resources, quizzes, and a final exam.';
        } elseif ($isOrgAdmin) {
            echo 'Review courses created by tutors, trainers, and teachers under your organisation. Organisation admins do not create courses.';
        } else {
            echo 'Courses created by tutors approved in your organisation.';
        }
      ?></p>
    </div>
    <?php if ($canCreate): ?>
      <a href="<?= url('/account/courses/create') ?>" class="btn-primary">Create a course</a>
    <?php endif; ?>
  </section>

  <?php if ($isStudent && $branches): ?>
    <section class="space-y-3">
      <h2 class="font-serif-heading text-lg font-bold">Branches</h2>
      <div class="course-grid">
        <?php foreach ($branches as $branch): ?>
          <article class="course-card">
            <?php $headingTag = 'h3'; require __DIR__ . '/../partials/branch-card.php'; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($isOrgAdmin): ?>
    <?php require __DIR__ . '/../partials/trainer-requests.php'; ?>
  <?php endif; ?>

  <?php if ($isStudent): ?>
    <section class="srms-card border-l-4 border-blue-500 flex items-center justify-between">
      <div>
        <h2 class="font-bold text-gray-800 text-lg">Attachment</h2>
        <p class="mt-1 text-xs text-gray-500">Choose a provider and branch for your attachment.</p>
      </div>
      <a href="<?= url('/account/attachment-providers') ?>" class="bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold py-2 px-4 rounded text-xs transition">Open</a>
    </section>
  <?php endif; ?>

  <?php if (!$courses): ?>
    <div class="rounded-xl border border-dashed p-8 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">
      <?= $isStudent ? 'No courses for your organisation yet. Global courses will appear here when tutors publish them.' : ($isOrgAdmin ? 'No tutors have created courses under your organisation yet. Approve tutors first, then their published courses will appear here for review.' : ($canCreate ? 'No courses yet. Create your first course when ready.' : 'No courses yet. Once an organisation approves you as a tutor, the create option will appear here.')) ?>
    </div>
  <?php else: ?>
    <div class="course-grid">
      <?php foreach ($courses as $course): ?>
        <article class="course-card">
          <?php if (!empty($course['cover_image'])): ?>
            <img src="<?= e(imageUrl($course['cover_image'])) ?>" alt="">
          <?php endif; ?>
          <h2 class="font-serif-heading text-xl font-bold"><?= e($course['title']) ?></h2>
          <p class="text-xs font-bold uppercase tracking-wider" style="color:var(--ke-muted)"><?= e($course['category_name'] ?? 'Uncategorised') ?> · <?= e($course['organisation_name'] ?? '') ?></p>
          <p class="text-[11px] font-black uppercase visibility-badge <?= ($course['visibility'] ?? 'strict') === 'global' ? 'is-global' : 'is-strict' ?>">
            <?= ($course['visibility'] ?? 'strict') === 'global' ? 'Global — all students' : 'Strict — this organisation' ?>
          </p>
          <p class="text-sm font-black" style="color:var(--ke-green)">Ksh <?= number_format((float) ($course['enrollment_fee_ksh'] ?? 0), 2) ?></p>
          <div class="flex flex-wrap gap-2">
            <span class="rounded-full border border-neutral-200 px-2 py-1 text-[10px] font-black uppercase text-neutral-700"><?= !empty($course['certificate_enabled']) ? 'Certificate' : 'No certificate' ?></span>
            <?php if (!empty($course['requires_attachment'])): ?>
              <span class="rounded-full border px-2 py-1 text-[10px] font-black uppercase" style="border-color:var(--ke-green);color:var(--ke-green)">Attachment</span>
            <?php endif; ?>
          </div>
          <?php if (!empty($course['first_name']) || !empty($course['email'])): ?>
            <p class="text-xs font-semibold text-neutral-600">Tutor: <?= e(trim(($course['first_name'] ?? '') . ' ' . ($course['last_name'] ?? '')) ?: ($course['email'] ?? '')) ?></p>
          <?php endif; ?>
          <?php if (!empty($course['description'])): ?>
            <p class="text-sm font-medium text-neutral-700"><?= e($course['description']) ?></p>
          <?php endif; ?>
          <?php if (!$isStudent): ?>
            <p class="text-[11px] font-black uppercase" style="color: <?= !empty($course['is_published']) ? 'var(--ke-green)' : 'var(--ke-muted)' ?>"><?= !empty($course['is_published']) ? 'Published' : 'Draft' ?></p>
          <?php endif; ?>
          <div class="flex flex-wrap gap-2">
            <?php if ($isStudent && empty($course['is_enrolled'])): ?>
              <?php if ($paymentsEnabled && (float) ($course['enrollment_fee_ksh'] ?? 0) > 0): ?>
                <a href="<?= url('/account/courses/' . (int) $course['id'] . '/checkout') ?>" class="btn-primary" style="padding:0.4rem 0.75rem;">Enroll</a>
              <?php else: ?>
                <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/enroll') ?>">
                  <?= csrfField() ?>
                  <button type="submit" class="btn-primary" style="padding:0.4rem 0.75rem;"><?= (float) ($course['enrollment_fee_ksh'] ?? 0) > 0 ? 'Enroll for testing' : 'Enroll' ?></button>
                </form>
              <?php endif; ?>
              <a href="<?= url('/account/courses/' . (int) $course['id']) ?>" class="btn-secondary" style="padding:0.4rem 0.75rem;">Preview</a>
            <?php else: ?>
              <a href="<?= url('/account/courses/' . (int) $course['id']) ?>" class="btn-primary" style="padding:0.4rem 0.75rem;"><?= $isStudent ? 'Continue' : 'View' ?></a>
            <?php endif; ?>
            <?php if ($canCreate && (int) ($course['trainer_user_id'] ?? 0) === (int) ($currentUser['id'] ?? 0)): ?>
              <a href="<?= url('/account/courses/' . (int) $course['id']) ?>" class="btn-secondary" style="padding:0.4rem 0.75rem;">Edit modules</a>
              <a href="<?= url('/account/courses/' . (int) $course['id'] . '/edit') ?>" class="btn-secondary" style="padding:0.4rem 0.75rem;">Edit details</a>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
