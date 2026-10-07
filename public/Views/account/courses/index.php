<?php
$courses = $courses ?? [];
$branches = $branches ?? [];
$canCreate = !empty($canCreate);
$isStudent = !empty($isStudent);
$courseGroups = is_array($courseGroups ?? null) ? $courseGroups : [];
$isOrgAdmin = ($currentUser['role_slug'] ?? '') === 'organisation_admin';
$paymentsEnabled = !empty($paymentsEnabled);
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Learning</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold"><?= $isStudent ? 'My courses' : 'Courses' ?></h1>
      <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600"><?php
        if ($isStudent) {
            echo 'Courses from the organisation that accepted you, then general courses open to every student.';
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

  <?php if ($isOrgAdmin): ?>
    <?php require __DIR__ . '/../partials/trainer-requests.php'; ?>
  <?php endif; ?>


  <?php if (!$courses): ?>
    <div class="rounded-xl border border-dashed p-8 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">
      <?= $isStudent ? 'No courses yet. Courses from your organisation and general courses appear here as soon as tutors publish them.' : ($isOrgAdmin ? 'No tutors have created courses under your organisation yet. Approve tutors first, then their published courses will appear here for review.' : ($canCreate ? 'No courses yet. Create your first course when ready.' : 'No courses yet. Once an organisation approves you as a tutor, the create option will appear here.')) ?>
    </div>
  <?php else: ?>
    <?php if ($courseGroups): ?>
      <?php foreach ($courseGroups as $group): ?>
        <section class="space-y-3">
          <div>
            <h2 class="font-serif-heading text-lg font-bold"><?= e($group['title']) ?></h2>
            <p class="mt-1 text-xs font-semibold" style="color:var(--ke-muted)"><?= e($group['note']) ?></p>
          </div>
          <?php $gridId = 'courses-' . e($group['key'] ?? 'group'); $shown = 10; $total = count($group['courses']); ?>
          <div class="course-tile-grid" id="<?= $gridId ?>">
            <?php foreach ($group['courses'] as $i => $course): ?>
              <?php $extraCard = $i >= $shown; require __DIR__ . '/../partials/course-card.php'; ?>
            <?php endforeach; ?>
          </div>
          <?php if ($total > $shown): ?>
            <div class="text-center">
              <button type="button" class="btn-secondary" data-show-all="<?= $gridId ?>">View more (<?= $total - $shown ?> more)</button>
            </div>
          <?php endif; ?>
        </section>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="course-tile-grid">
        <?php foreach ($courses as $course): ?>
          <?php $extraCard = false; require __DIR__ . '/../partials/course-card.php'; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
