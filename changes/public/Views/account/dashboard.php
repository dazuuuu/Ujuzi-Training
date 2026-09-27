<?php
/** Requires $currentUser, $forms, $completedForms, $totalForms, $canManageUsers, $recentManaged in scope. */
require __DIR__ . '/layout-header.php';
$pendingRequestCount = count($pendingTrainerRequests ?? []);
$paymentsEnabled = !empty($paymentsEnabled);
$isEnrolledInAnyCourse = !empty($isEnrolledInAnyCourse);
?>

<div class="space-y-6 mt-6">
  <?php if (!empty($needsProfile)): ?>
    <section class="bg-red-50 text-red-800 border-l-4 border-red-500 p-4 rounded flex justify-between items-center">
      <div>
        <p class="text-sm font-black">Complete your profile</p>
        <p class="mt-1 text-xs font-semibold">Finish the registration details assigned to your role so your account is fully set up.</p>
      </div>
      <a href="<?= url('/account/profile') ?>" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded text-xs transition">Open profile</a>
    </section>
  <?php endif; ?>

  <div class="srms-welcome-banner shadow-sm">
    <div>
      <h2 class="text-3xl font-bold text-blue-900 mb-2">Welcome,<br><?= e($currentUser['first_name'] ?: userDisplayName($currentUser)) ?>!</h2>
      <p class="text-blue-700 font-medium text-sm">
        Stay updated with your <?= !empty($isStudent) ? 'academic' : 'professional' ?> journey.
        <br>
        Organisation: <strong><?= e($currentUser['organisation_name'] ?? 'No organisation assigned') ?></strong>
        <?php if (!empty($currentUser['has_admin_features'])): ?>
          · Admin-like tools are available
        <?php endif; ?>
      </p>
    </div>
    <div class="hidden sm:block opacity-80" style="color:var(--ke-green)"><?= icon('graduation', 'h-16 w-16') ?></div>
  </div>

  <div class="srms-stat-grid">
    <a href="<?= url('/account/profile') ?>" class="srms-stat-card cursor-pointer hover:shadow-md transition">
      <div class="srms-stat-icon blue"><?= icon('file') ?></div>
      <div>
        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-widest">Profile forms</p>
        <p class="text-xl font-black text-gray-800"><?= (int) $completedForms ?> / <?= (int) $totalForms ?></p>
        <p class="text-[10px] font-bold text-blue-500">Completed</p>
      </div>
    </a>
    <div class="srms-stat-card">
      <div class="srms-stat-icon green"><?= icon('graduation') ?></div>
      <div>
        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-widest">Your Role</p>
        <p class="text-xl font-black text-gray-800"><?= e($currentUser['role_name']) ?></p>
        <p class="text-[10px] font-bold text-green-500"><?= !empty($currentUser['is_under_organisation']) ? 'Managed' : 'Owner' ?></p>
      </div>
    </div>
    <?php if (!empty($isOrgAdmin)): ?>
      <a href="<?= url('/account/trainer-requests') ?>" class="srms-stat-card cursor-pointer hover:shadow-md transition">
        <div class="srms-stat-icon orange"><?= icon('inbox') ?></div>
        <div>
          <p class="text-[11px] font-bold text-gray-500 uppercase tracking-widest">Trainer Requests</p>
          <p class="text-xl font-black text-gray-800"><?= (int) $pendingRequestCount ?></p>
          <p class="text-[10px] font-bold text-orange-500">Pending</p>
        </div>
      </a>
    <?php elseif ($canManageUsers): ?>
      <a href="<?= url('/account/people') ?>" class="srms-stat-card cursor-pointer hover:shadow-md transition">
        <div class="srms-stat-icon purple"><?= icon('users') ?></div>
        <div>
          <p class="text-[11px] font-bold text-gray-500 uppercase tracking-widest">People</p>
          <p class="text-xl font-black text-gray-800"><?= count($managedUsers ?? []) ?></p>
          <p class="text-[10px] font-bold text-purple-500">Managed</p>
        </div>
      </a>
    <?php elseif (!empty($canViewCourses)): ?>
      <a href="<?= url('/account/courses') ?>" class="srms-stat-card cursor-pointer hover:shadow-md transition">
        <div class="srms-stat-icon purple"><?= icon('book') ?></div>
        <div>
          <p class="text-[11px] font-bold text-gray-500 uppercase tracking-widest">Courses</p>
          <p class="text-lg font-black text-gray-800"><?= !empty($isStudent) ? 'Catalogue' : (!empty($canCreateCourses) ? 'Teach' : 'Review') ?></p>
          <p class="text-[10px] font-bold text-purple-500">Access granted</p>
        </div>
      </a>
    <?php else: ?>
      <div class="srms-stat-card">
        <div class="srms-stat-icon purple"><?= icon('check') ?></div>
        <div>
          <p class="text-[11px] font-bold text-gray-500 uppercase tracking-widest">Workspace</p>
          <p class="text-xl font-black text-gray-800">Ready</p>
          <p class="text-[10px] font-bold text-purple-500">Provisioned</p>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
      
      <?php if (($currentUser['role_slug'] ?? '') === 'organisation_admin'): ?>
        <?php require __DIR__ . '/partials/trainer-requests.php'; ?>
      <?php endif; ?>

      <?php if (!empty($isStudent)): ?>
        <section class="srms-card border-l-4" style="border-color:var(--ke-green)">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h2 class="font-bold text-gray-800 text-lg">Your course categories</h2>
              <p class="mt-1 text-xs text-gray-500">The categories you selected. Approved ones decide which courses you see.</p>
            </div>
            <a href="<?= url('/account/course-organisations') ?>" class="bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold py-2 px-4 rounded text-xs transition shrink-0">Change</a>
          </div>
          <?php if (empty($studentCategories)): ?>
            <p class="mt-4 rounded-lg border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">
              You haven't selected any categories yet. Choose an organisation providing courses and the categories you want.
            </p>
          <?php else: ?>
            <div class="mt-4 space-y-3">
              <?php foreach ($studentCategories as $group): ?>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                  <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-black text-gray-800">
                      <?= e($group['organisation_name']) ?><?= !empty($group['branch_title']) ? ' · ' . e($group['branch_title']) : '' ?>
                    </p>
                    <span class="text-[10px] font-black uppercase" style="color:<?= $group['status'] === 'approved' ? 'var(--ke-green)' : 'var(--ke-red)' ?>">
                      <?= $group['status'] === 'approved' ? 'Approved' : 'Awaiting approval' ?>
                    </span>
                  </div>
                  <div class="mt-2 flex flex-wrap gap-2">
                    <?php if (!$group['categories']): ?>
                      <span class="text-xs font-semibold text-gray-500">All categories</span>
                    <?php endif; ?>
                    <?php foreach ($group['categories'] as $categoryName): ?>
                      <span class="rounded-full border px-2 py-1 text-[11px] font-bold" style="border-color:var(--ke-green);color:var(--ke-green)"><?= e($categoryName) ?></span>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <?php if (($currentUser['role_slug'] ?? '') === 'attachment_trainer' && ($audienceCount ?? null) === 0): ?>
        <section class="bg-red-50 text-red-800 border-l-4 border-red-500 p-4 rounded flex justify-between items-center gap-3">
          <div>
            <p class="text-sm font-black">Students can't see you yet</p>
            <p class="mt-1 text-xs font-semibold">Choose the organisations providing courses and the course categories whose students you accept. You appear to their enrolled students straight away.</p>
          </div>
          <a href="<?= url('/account/attachment-audience') ?>" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded text-xs transition shrink-0">Choose</a>
        </section>
      <?php endif; ?>

      <?php if (!empty($isStudent) && !empty($learnerCourses)): ?>
      <div class="srms-card">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-bold text-gray-800 text-lg">Recent Courses</h2>
          <a href="<?= url('/account/courses') ?>" class="text-blue-500 text-sm font-semibold hover:underline">View All</a>
        </div>
        <div class="overflow-x-auto">
          <table class="srms-table w-full text-sm">
            <thead>
              <tr>
                <th>Subject</th>
                <th>Provider</th>
                <th>Type</th>
                <th>Fee</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($learnerCourses as $course): ?>
                <tr>
                  <td class="font-semibold text-gray-800">
                    <div class="flex items-center gap-3">
                      <?php if (!empty($course['cover_image'])): ?>
                        <img src="<?= e(imageUrl($course['cover_image'])) ?>" alt="" loading="lazy" class="h-10 w-16 shrink-0 rounded object-cover">
                      <?php else: ?>
                        <span class="flex h-10 w-16 shrink-0 items-center justify-center rounded text-sm font-black text-white" style="background:var(--ke-green)" aria-hidden="true"><?= e(mb_strtoupper(mb_substr(trim((string) $course['title']), 0, 1))) ?></span>
                      <?php endif; ?>
                      <span><?= e($course['title']) ?></span>
                    </div>
                  </td>
                  <td class="text-gray-600"><?= e($course['organisation_name'] ?? '-') ?></td>
                  <td>
                    <span class="inline-block px-2 py-1 text-[10px] font-bold rounded-full <?= ($course['visibility'] ?? 'strict') === 'global' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' ?>">
                      <?= ($course['visibility'] ?? 'strict') === 'global' ? 'Global' : 'Internal' ?>
                    </span>
                  </td>
                  <td class="text-green-600 font-bold">Ksh <?= number_format((float) ($course['enrollment_fee_ksh'] ?? 0), 2) ?></td>
                  <td>
                    <?php if (empty($course['is_enrolled'])): ?>
                      <?php if ($paymentsEnabled && (float) ($course['enrollment_fee_ksh'] ?? 0) > 0): ?>
                        <a href="<?= url('/account/courses/' . (int) $course['id'] . '/checkout') ?>" class="text-blue-600 font-bold hover:underline">Enroll</a>
                      <?php else: ?>
                        <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/enroll') ?>">
                          <?= csrfField() ?>
                          <button type="submit" class="text-blue-600 font-bold hover:underline">Enroll</button>
                        </form>
                      <?php endif; ?>
                    <?php else: ?>
                      <a href="<?= url('/account/courses/' . (int) $course['id']) ?>" class="text-green-600 font-bold hover:underline">Continue</a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($isOrgAdmin)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <section class="srms-card border-l-4 border-blue-500">
            <h2 class="font-bold text-gray-800 text-lg">Course categories</h2>
            <p class="mt-1 text-xs text-gray-500 mb-4 h-10">List the subjects your organisation offers.</p>
            <a href="<?= url('/account/categories') ?>" class="bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold py-2 px-4 rounded text-xs transition block text-center">Manage categories</a>
          </section>
          <section class="srms-card border-l-4 border-blue-500">
            <h2 class="font-bold text-gray-800 text-lg">Branches</h2>
            <p class="mt-1 text-xs text-gray-500 mb-4 h-10">List your campuses or locations.</p>
            <a href="<?= url('/account/branches') ?>" class="bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold py-2 px-4 rounded text-xs transition block text-center">Manage branches</a>
          </section>
        </div>
      <?php endif; ?>

      <?php if (($currentUser['role_slug'] ?? '') === 'attachment_trainer'): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <section class="srms-card border-l-4 border-blue-500">
            <h2 class="font-bold text-gray-800 text-lg"><?= empty($currentUser['organisation_id']) ? 'Register your organisation' : 'Your organisation' ?></h2>
            <p class="mt-1 text-xs text-gray-500 mb-4 h-10"><?= empty($currentUser['organisation_id']) ? 'Register once, then add branches.' : 'Update your organisation details.' ?></p>
            <a href="<?= url('/account/organisation') ?>" class="bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold py-2 px-4 rounded text-xs transition block text-center"><?= empty($currentUser['organisation_id']) ? 'Register organisation' : 'Manage organisation' ?></a>
          </section>
          <section class="srms-card border-l-4 border-blue-500">
            <h2 class="font-bold text-gray-800 text-lg">Branches &amp; admins</h2>
            <p class="mt-1 text-xs text-gray-500 mb-4 h-10">Add branches and branch admins.</p>
            <a href="<?= url('/account/branches') ?>" class="bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold py-2 px-4 rounded text-xs transition block text-center">Manage branches</a>
          </section>
        </div>
      <?php endif; ?>

      <?php if (!empty($isStudent)): ?>
        <section class="srms-card border-l-4 border-blue-500 flex items-center justify-between">
          <div>
            <h2 class="font-bold text-gray-800 text-lg">Attachment</h2>
            <p class="mt-1 text-xs text-gray-500">Choose a provider and branch for your attachment.</p>
          </div>
          <a href="<?= url('/account/attachment-providers') ?>" class="bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold py-2 px-4 rounded text-xs transition">Open</a>
        </section>
      <?php endif; ?>
      
      <?php if (!empty($canViewCourses) && empty($isStudent)): ?>
        <section class="srms-card flex items-center justify-between">
          <div>
            <h2 class="font-bold text-gray-800 text-lg">Courses</h2>
            <p class="mt-1 text-xs text-gray-500"><?= !empty($canCreateCourses) ? 'Create and manage courses.' : 'Review courses tutors have created.' ?></p>
          </div>
          <a href="<?= url(!empty($canCreateCourses) ? '/account/courses/create' : '/account/courses') ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-xs transition">
            <?= !empty($canCreateCourses) ? 'Create Course' : 'View Courses' ?>
          </a>
        </section>
      <?php endif; ?>
      
      <?php if (!empty($isStudent) && !empty($completedCourses)): ?>
        <section class="srms-card border-l-4 border-green-500 flex items-center justify-between">
          <div>
            <h2 class="font-bold text-gray-800 text-lg">Skills Certificate</h2>
            <p class="mt-1 text-xs text-gray-500">View your cumulative skills certificate.</p>
          </div>
          <a href="<?= url('/account/certificate') ?>" class="bg-green-50 text-green-700 hover:bg-green-100 font-bold py-2 px-4 rounded text-xs transition">Open</a>
        </section>
      <?php endif; ?>

    </div>

    <div class="space-y-6">
      <div class="srms-card">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-bold text-gray-800 text-sm">Quick Access</h2>
        </div>
        <div class="srms-quick-grid">
          <a href="<?= url('/account/profile') ?>" class="srms-quick-item">
            <span class="text-blue-500 text-2xl bg-blue-50 w-10 h-10 flex items-center justify-center rounded-lg"><?= icon('user') ?></span>
            <span>Profile</span>
          </a>
          <a href="<?= url('/account/courses') ?>" class="srms-quick-item">
            <span class="text-green-500 text-2xl bg-green-50 w-10 h-10 flex items-center justify-center rounded-lg"><?= icon('book') ?></span>
            <span>Courses</span>
          </a>
          <?php if ($canManageUsers): ?>
          <a href="<?= url('/account/people') ?>" class="srms-quick-item">
            <span class="text-purple-500 text-2xl bg-purple-50 w-10 h-10 flex items-center justify-center rounded-lg"><?= icon('users') ?></span>
            <span>People</span>
          </a>
          <?php endif; ?>
          <a href="#" class="srms-quick-item">
            <span class="text-orange-500 text-2xl bg-orange-50 w-10 h-10 flex items-center justify-center rounded-lg"><?= icon('clock') ?></span>
            <span>Calendar</span>
          </a>
          <a href="#" class="srms-quick-item">
            <span class="text-red-500 text-2xl bg-red-50 w-10 h-10 flex items-center justify-center rounded-lg"><?= icon('inbox') ?></span>
            <span>Feedback</span>
          </a>
        </div>
      </div>

      <div class="srms-card">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-bold text-gray-800 text-sm">Assigned Forms</h2>
          <a href="<?= url('/account/profile') ?>" class="text-blue-500 text-xs font-semibold hover:underline">View All</a>
        </div>
        <div class="space-y-3">
          <?php if (!$forms): ?>
            <p class="text-xs text-gray-500 text-center py-2">No forms assigned.</p>
          <?php endif; ?>
          <?php foreach ($forms as $form): ?>
            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
              <div class="text-blue-500 bg-blue-100 p-2 rounded"><?= icon('file') ?></div>
              <div>
                <p class="text-sm font-bold text-gray-800"><?= e($form['title']) ?></p>
                <p class="text-[10px] text-gray-500 mt-1"><?= e($form['description'] ?: 'Fill this on your profile page.') ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      
      <?php if (!empty($memberships) || ($currentUser['role_slug'] ?? '') === 'trainer'): ?>
      <div class="srms-card">
        <h2 class="font-bold text-gray-800 text-sm mb-4">Your Organisations</h2>
        <?php if (!empty($memberships)): ?>
          <div class="space-y-2">
            <?php foreach ($memberships as $membership):
              $status = $membership['status'] ?? 'pending';
              $statusLabel = $status === 'approved' ? 'Approved' : ($status === 'rejected' ? 'Rejected' : 'Pending');
              $statusColor = $status === 'approved' ? 'text-green-600 bg-green-100' : ($status === 'rejected' ? 'text-red-600 bg-red-100' : 'text-orange-600 bg-orange-100');
            ?>
              <div class="flex items-center justify-between p-2 rounded hover:bg-gray-50">
                <p class="text-sm font-bold text-gray-700"><?= e($membership['organisation_name'] ?? 'Organisation') ?></p>
                <span class="text-[10px] font-bold uppercase px-2 py-1 rounded <?= $statusColor ?>"><?= e($statusLabel) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-xs text-gray-500 mb-3">Open your profile form to pick organisations to teach for.</p>
          <a href="<?= url('/account/profile') ?>" class="text-blue-600 font-bold text-xs hover:underline">Open profile form</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($canManageUsers): ?>
      <div class="srms-card">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-bold text-gray-800 text-sm">People</h2>
          <a href="<?= url('/account/people/create') ?>" class="text-blue-500 text-xs font-semibold hover:underline">+ Add</a>
        </div>
        <div class="space-y-2">
          <?php if (empty($recentManaged)): ?>
            <p class="text-xs text-gray-500 text-center py-2">No people found.</p>
          <?php endif; ?>
          <?php foreach ($recentManaged as $person): ?>
            <div class="flex items-center justify-between p-2 rounded hover:bg-gray-50 border border-transparent hover:border-gray-100 transition cursor-pointer" onclick="window.location.href='<?= url('/account/people/' . (int) $person['id']) ?>'">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600">
                  <?= e(strtoupper(substr((string) ($person['first_name'] ?? 'U'), 0, 1))) ?>
                </div>
                <div>
                  <p class="text-xs font-bold text-gray-800"><?= e(userDisplayName($person)) ?></p>
                  <p class="text-[9px] text-gray-500 uppercase"><?= e($person['role_name']) ?></p>
                </div>
              </div>
              <span class="text-gray-400">›</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
