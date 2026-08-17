<?php
/** Requires $currentUser, $forms, $completedForms, $totalForms, $canManageUsers, $recentManaged in scope. */
require __DIR__ . '/layout-header.php';
?>

<div class="space-y-8">
  <div>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)"><?= e($currentUser['role_name']) ?></p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold text-[#0a0a0a]">Hello, <?= e($currentUser['first_name'] ?: userDisplayName($currentUser)) ?></h1>
    <p class="mt-2 text-sm font-medium text-neutral-600">
      <?= e($currentUser['organisation_name'] ?? 'No organisation') ?>
      <?php if (!empty($currentUser['has_admin_features'])): ?>
        · Admin-like tools are available for your organisation
      <?php else: ?>
        · Your account is managed by your organisation
      <?php endif; ?>
    </p>
  </div>

  <section class="grid gap-4 sm:grid-cols-3">
    <a href="<?= url('/account/profile') ?>" class="rounded-xl bg-white p-5" style="border:2px solid var(--ke-green)">
      <p class="text-[11px] font-black uppercase tracking-widest" style="color:var(--ke-green)">Profile forms</p>
      <p class="mt-3 text-2xl font-black"><?= (int) $completedForms ?>/<?= (int) $totalForms ?></p>
      <p class="mt-1 text-xs font-bold text-neutral-600">Completed</p>
    </a>
    <div class="rounded-xl border border-neutral-300 bg-white p-5">
      <p class="text-[11px] font-black uppercase tracking-widest text-neutral-600">Role</p>
      <p class="mt-3 text-lg font-black"><?= e($currentUser['role_name']) ?></p>
      <p class="mt-1 text-xs font-bold text-neutral-600"><?= !empty($currentUser['is_under_organisation']) ? 'Under organisation power' : 'Organisation owner role' ?></p>
    </div>
    <?php if ($canManageUsers): ?>
      <a href="<?= url('/account/people') ?>" class="rounded-xl bg-white p-5" style="border:2px solid var(--ke-red)">
        <p class="text-[11px] font-black uppercase tracking-widest" style="color:var(--ke-red)">People</p>
        <p class="mt-3 text-2xl font-black"><?= count($managedUsers ?? []) ?></p>
        <p class="mt-1 text-xs font-bold text-neutral-600">You can manage</p>
      </a>
    <?php elseif (!empty($canViewCourses)): ?>
      <a href="<?= url('/account/courses') ?>" class="rounded-xl bg-white p-5" style="border:2px solid var(--ke-green)">
        <p class="text-[11px] font-black uppercase tracking-widest" style="color:var(--ke-green)">Courses</p>
        <p class="mt-3 text-lg font-black"><?= !empty($canCreateCourses) ? 'Create & teach' : 'Review catalogue' ?></p>
        <p class="mt-1 text-xs font-bold text-neutral-600">Open courses</p>
      </a>
    <?php else: ?>
      <div class="rounded-xl border border-neutral-300 bg-white p-5">
        <p class="text-[11px] font-black uppercase tracking-widest text-neutral-600">Workspace</p>
        <p class="mt-3 text-lg font-black">Ready</p>
        <p class="mt-1 text-xs font-bold text-neutral-600">Dashboard and profile provisioned</p>
      </div>
    <?php endif; ?>
  </section>

  <?php if (($currentUser['role_slug'] ?? '') === 'organisation_admin'): ?>
    <section class="rounded-xl bg-white p-6 shadow-sm" style="border:2px solid var(--ke-red)">
      <h2 class="font-serif-heading text-lg font-bold">Trainer requests</h2>
      <p class="mt-1 text-xs font-semibold" style="color:var(--ke-muted)">Approve trainers, tutors, or teachers who selected your organisation. They are assigned to you only after you approve them.</p>
      <div class="mt-4 divide-y divide-neutral-100">
        <?php if (empty($pendingTrainerRequests)): ?>
          <p class="py-4 text-sm font-bold text-neutral-600">No pending trainer requests.</p>
        <?php endif; ?>
        <?php foreach ($pendingTrainerRequests ?? [] as $request): ?>
          <div class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p class="text-sm font-black"><?= e(userDisplayName($request)) ?></p>
              <p class="text-xs font-semibold text-neutral-600"><?= e($request['role_name'] ?? 'Trainer') ?> · <?= e($request['email'] ?: ($request['phone'] ?? '')) ?></p>
            </div>
            <div class="flex items-center gap-2">
              <form method="post" action="<?= url('/account/trainer-requests/' . (int) $request['id'] . '/approve') ?>">
                <?= csrfField() ?>
                <button type="submit" class="btn-primary" style="padding:0.35rem 0.75rem;">Approve</button>
              </form>
              <form method="post" action="<?= url('/account/trainer-requests/' . (int) $request['id'] . '/reject') ?>">
                <?= csrfField() ?>
                <button type="submit" class="btn-danger" style="padding:0.35rem 0.75rem;">Reject</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!empty($isOrgAdmin)): ?>
    <section class="rounded-xl bg-white p-6 shadow-sm" style="border:2px solid var(--ke-green)">
      <div class="flex items-center justify-between gap-3">
        <div>
          <h2 class="font-serif-heading text-lg font-bold">Course categories</h2>
          <p class="mt-1 text-xs font-semibold" style="color:var(--ke-muted)">List the subjects your organisation offers. Approved tutors only see these when they create a course.</p>
        </div>
        <a href="<?= url('/account/categories') ?>" class="btn-primary">Manage categories</a>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!empty($canViewCourses)): ?>
    <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
      <div class="flex items-center justify-between gap-3">
        <div>
          <h2 class="font-serif-heading text-lg font-bold">Courses</h2>
          <p class="mt-1 text-xs font-semibold" style="color:var(--ke-muted)"><?= !empty($canCreateCourses)
            ? 'Create courses after an organisation approves you, then add one video module at a time.'
            : 'Review courses tutors have created for your organisation.' ?></p>
        </div>
        <a href="<?= url(!empty($canCreateCourses) ? '/account/courses/create' : '/account/courses') ?>" class="btn-primary"><?= !empty($canCreateCourses) ? 'Create a course' : 'View courses' ?></a>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!empty($memberships)): ?>
    <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
      <h2 class="font-serif-heading text-lg font-bold">Your organisations</h2>
      <p class="mt-1 text-xs font-semibold" style="color:var(--ke-muted)">Organisation admins must approve you before you are assigned as their tutor.</p>
      <div class="mt-4 divide-y divide-neutral-100">
        <?php foreach ($memberships as $membership):
          $status = $membership['status'] ?? 'pending';
          $statusLabel = $status === 'approved' ? 'Approved' : ($status === 'rejected' ? 'Rejected' : 'Waiting for approval');
          $statusColor = $status === 'approved' ? 'var(--ke-green)' : ($status === 'rejected' ? 'var(--ke-red)' : '#8a6d00');
        ?>
          <div class="flex items-center justify-between py-3">
            <p class="text-sm font-black"><?= e($membership['organisation_name'] ?? 'Organisation') ?></p>
            <span class="text-[11px] font-black uppercase tracking-widest" style="color:<?= $statusColor ?>"><?= e($statusLabel) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php elseif (($currentUser['role_slug'] ?? '') === 'trainer'): ?>
    <section class="rounded-xl border border-dashed p-6" style="border-color:var(--ke-line)">
      <h2 class="font-serif-heading text-lg font-bold">Your organisations</h2>
      <p class="mt-2 text-sm font-medium" style="color:var(--ke-muted)">Open your profile form, pick the organisation(s) you want to teach for, then wait for each organisation admin to approve you.</p>
      <a href="<?= url('/account/profile') ?>" class="btn-primary mt-4 inline-flex">Open profile form</a>
    </section>
  <?php endif; ?>

  <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between gap-3">
      <h2 class="font-serif-heading text-lg font-bold">Assigned profile forms</h2>
      <a href="<?= url('/account/profile') ?>" class="btn-primary">Open profile</a>
    </div>
    <div class="mt-4 divide-y divide-neutral-100">
      <?php if (!$forms): ?>
        <p class="py-4 text-sm font-bold text-neutral-600">No forms assigned to your role yet.</p>
      <?php endif; ?>
      <?php foreach ($forms as $form): ?>
        <div class="py-3">
          <p class="text-sm font-black text-black"><?= e($form['title']) ?></p>
          <p class="text-xs font-semibold text-neutral-600"><?= e($form['description'] ?: 'Fill this on your profile page.') ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if ($canManageUsers): ?>
    <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
      <div class="flex items-center justify-between gap-3">
        <h2 class="font-serif-heading text-lg font-bold">People in your organisation</h2>
        <a href="<?= url('/account/people/create') ?>" class="btn-primary">Add person</a>
      </div>
      <div class="mt-4 divide-y divide-neutral-100">
        <?php if (empty($recentManaged)): ?>
          <p class="py-4 text-sm font-bold text-neutral-600">No people in your scope yet.</p>
        <?php endif; ?>
        <?php foreach ($recentManaged as $person): ?>
          <div class="flex items-center justify-between py-3">
            <div>
              <p class="text-sm font-black"><?= e(userDisplayName($person)) ?></p>
              <p class="text-xs font-semibold text-neutral-600"><?= e($person['role_name']) ?></p>
            </div>
            <a href="<?= url('/account/people/' . (int) $person['id']) ?>" class="btn-secondary" style="padding:0.35rem 0.65rem;">View</a>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
