<?php
/**
 * Student dashboard. Requires $wallet (balance, deposited, spent), $fees (one
 * WalletService::feeSummaries() entry or null), $approvedGroups / $pendingGroups
 * (organisation, branch, categories), $applications, $unreadNotes,
 * $certificateReady, $needsProfile and $minPaymentPercent.
 */
use App\Models\AttachmentApplication;

require __DIR__ . '/layout-header.php';

$ksh = static fn(float $n): string => 'Ksh ' . number_format($n, 2);
$courses = $fees['items'] ?? [];
$owed = (float) ($fees['balance'] ?? 0);
$firstName = $currentUser['first_name'] ?: userDisplayName($currentUser);
$liveAttachment = null;
foreach ($applications as $application) {
    if ($application['status'] !== 'rejected') { $liveAttachment = $application; break; }
}
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>

<div class="space-y-6">
  <?php if (!empty($needsProfile)): ?>
    <section class="flex items-center justify-between gap-3 rounded-xl border-l-4 border-red-500 bg-red-50 p-4 text-red-800">
      <div>
        <p class="text-sm font-black">Complete your profile</p>
        <p class="mt-1 text-xs font-semibold">Finish your registration details so your account is fully set up.</p>
      </div>
      <a href="<?= url('/account/profile') ?>" class="btn-primary shrink-0" style="padding:0.45rem 0.9rem;">Open profile</a>
    </section>
  <?php endif; ?>

  <section>
    <p class="text-sm font-semibold text-neutral-500"><?= e($greeting) ?>,</p>
    <h1 class="font-serif-heading text-3xl font-bold"><?= e($firstName) ?></h1>
  </section>

  <!-- Wallet and fees -->
  <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Summary">
    <a href="<?= url('/account/wallet') ?>" class="app-tile app-press">
      <span class="app-tile-icon"><?= icon('wallet') ?></span>
      <span class="app-tile-label">Wallet balance</span>
      <span class="app-tile-value"><?= e($ksh($wallet['balance'])) ?></span>
      <span class="app-tile-sub"><?= e($ksh($wallet['deposited'])) ?> deposited</span>
    </a>
    <a href="<?= url('/account/wallet') ?>" class="app-tile app-press">
      <span class="app-tile-icon" style="<?= $owed > 0 ? 'background:#fef2f2;color:var(--ke-red)' : '' ?>"><?= icon('coins') ?></span>
      <span class="app-tile-label">Fees balance</span>
      <span class="app-tile-value" style="<?= $owed > 0 ? 'color:var(--ke-red)' : '' ?>"><?= e($ksh($owed)) ?></span>
      <span class="app-tile-sub"><?= $fees && $fees['courses'] ? e($ksh($fees['paid'])) . ' paid of ' . e($ksh($fees['fee'])) : 'No course fees yet' ?></span>
    </a>
    <a href="<?= url('/account/courses') ?>" class="app-tile app-press">
      <span class="app-tile-icon"><?= icon('book') ?></span>
      <span class="app-tile-label">My courses</span>
      <span class="app-tile-value"><?= count($courses) ?></span>
      <span class="app-tile-sub"><?= count($courses) === 1 ? 'course enrolled' : 'courses enrolled' ?></span>
    </a>
    <a href="<?= url($liveAttachment ? '/account/attachments/' . (int) $liveAttachment['id'] : '/account/attachment-providers') ?>" class="app-tile app-press">
      <span class="app-tile-icon"><?= icon('briefcase') ?></span>
      <span class="app-tile-label">Attachment</span>
      <span class="app-tile-value text-lg"><?= e($liveAttachment ? AttachmentApplication::statusLabel($liveAttachment['status']) : 'Not requested') ?></span>
      <span class="app-tile-sub"><?= $unreadNotes ? (int) $unreadNotes . ' new note' . ($unreadNotes === 1 ? '' : 's') : ($liveAttachment ? e($liveAttachment['organisation_name'] ?: 'View request') : 'Find a provider') ?></span>
    </a>
  </section>

  <?php if ($fees && !empty($fees['below_minimum'])): ?>
    <section class="flex items-start gap-3 rounded-xl border p-4 text-sm font-semibold" role="alert" style="border-color:#f59e0b;background:#fffbeb;color:#92400e">
      <?= icon('alert', 'h-5 w-5 shrink-0') ?>
      <p>You have paid <?= (int) floor($fees['paid_ratio'] * 100) ?>% of your course fees. Pay at least <?= (int) $minPaymentPercent ?>% to send an attachment request.
        <a href="<?= url('/account/wallet') ?>" class="font-black underline">Go to My wallet</a></p>
    </section>
  <?php endif; ?>

  <div class="grid gap-6 lg:grid-cols-3">
    <!-- Course progress -->
    <section class="min-w-0 space-y-3 lg:col-span-2">
      <div class="flex items-center justify-between">
        <h2 class="font-serif-heading text-lg font-bold">My course progress</h2>
        <a href="<?= url('/account/courses') ?>" class="text-xs font-black" style="color:var(--ke-green)">All courses</a>
      </div>
      <?php if (!$courses): ?>
        <div class="rounded-xl border border-dashed p-6 text-center" style="border-color:var(--ke-line)">
          <p class="text-sm font-bold" style="color:var(--ke-muted)">You haven't enrolled for a course yet.</p>
          <a href="<?= url('/account/courses') ?>" class="btn-primary mt-3 inline-block" style="padding:0.45rem 0.9rem;">Browse courses</a>
        </div>
      <?php else: ?>
        <ul class="space-y-3">
          <?php foreach ($courses as $course):
            $cover = $course['cover_image'] ?? '';
            $hasCover = $cover !== '' && preg_match('/\.(jpe?g|png|webp|gif)$/i', $cover);
          ?>
            <li>
              <a href="<?= url('/account/courses/' . (int) $course['course_id']) ?>" class="app-press flex items-center gap-4 rounded-xl border bg-white p-3 shadow-sm" style="border-color:var(--ke-line)">
                <?php if ($hasCover): ?>
                  <img src="<?= e(imageUrl($cover)) ?>" alt="" loading="lazy" class="h-16 w-24 shrink-0 rounded-lg object-cover">
                <?php else: ?>
                  <span class="flex h-16 w-24 shrink-0 items-center justify-center rounded-lg text-xl font-black text-white" style="background:var(--ke-green)" aria-hidden="true"><?= e(mb_strtoupper(mb_substr(trim($course['title']), 0, 1))) ?></span>
                <?php endif; ?>
                <span class="min-w-0 flex-1">
                  <span class="block truncate font-bold text-gray-800"><?= e($course['title']) ?></span>
                  <span class="mt-2 flex items-center gap-2">
                    <span class="app-progressbar flex-1" role="progressbar" aria-valuenow="<?= (int) $course['progress'] ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= e($course['title']) ?> progress">
                      <span style="width:<?= (int) $course['progress'] ?>%"></span>
                    </span>
                    <span class="w-10 text-right text-xs font-black text-neutral-700"><?= (int) $course['progress'] ?>%</span>
                  </span>
                  <span class="mt-1 block text-xs font-semibold text-neutral-500">
                    <?= (int) $course['modules_passed'] ?> of <?= (int) $course['modules_total'] ?> modules<?= $course['final_passed'] ? ' · final exam passed' : '' ?>
                    · <?= $course['balance'] > 0 ? '<span style="color:var(--ke-red)">owes ' . e($ksh($course['balance'])) . '</span>' : 'fully paid' ?>
                  </span>
                </span>
                <span class="hidden text-neutral-400 sm:block"><?= icon('arrow-right') ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <!-- Organisation that approved them -->
    <aside class="min-w-0 space-y-3">
      <h2 class="font-serif-heading text-lg font-bold">My organisation</h2>
      <?php if (!$approvedGroups && !$pendingGroups): ?>
        <div class="rounded-xl border border-dashed p-5" style="border-color:var(--ke-line)">
          <p class="text-sm font-bold" style="color:var(--ke-muted)">You haven't joined an organisation providing courses yet.</p>
          <a href="<?= url('/account/course-organisations') ?>" class="btn-primary mt-3 inline-block" style="padding:0.45rem 0.9rem;">Choose an organisation</a>
        </div>
      <?php endif; ?>

      <?php foreach ($approvedGroups as $group): ?>
        <div class="rounded-xl border bg-white p-4 shadow-sm" style="border-color:var(--ke-line)">
          <div class="flex items-start gap-3">
            <span class="app-tile-icon shrink-0"><?= icon('building') ?></span>
            <div class="min-w-0">
              <p class="text-[11px] font-black uppercase tracking-wider" style="color:var(--ke-green)">Approved you</p>
              <p class="font-bold text-gray-800"><?= e($group['organisation_name']) ?></p>
              <?php if (!empty($group['branch_title'])): ?>
                <p class="text-xs font-semibold text-neutral-500"><?= e($group['branch_title']) ?></p>
              <?php endif; ?>
            </div>
          </div>
          <p class="mt-3 text-[11px] font-black uppercase tracking-wider text-neutral-500">Your categories</p>
          <div class="mt-1 flex flex-wrap gap-1.5">
            <?php if (!$group['categories']): ?>
              <span class="text-xs font-semibold text-neutral-500">All categories</span>
            <?php endif; ?>
            <?php foreach ($group['categories'] as $categoryName): ?>
              <span class="rounded-full border px-2 py-0.5 text-[11px] font-bold" style="border-color:var(--ke-green);color:var(--ke-green)"><?= e($categoryName) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>

      <?php foreach ($pendingGroups as $group): ?>
        <div class="rounded-xl border bg-white p-4" style="border-color:var(--ke-line)">
          <p class="text-[11px] font-black uppercase tracking-wider" style="color:var(--ke-red)">Waiting for approval</p>
          <p class="font-bold text-gray-800"><?= e($group['organisation_name']) ?></p>
          <?php if ($group['categories']): ?>
            <p class="mt-1 text-xs font-semibold text-neutral-500">Asked for: <?= e(implode(', ', $group['categories'])) ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <?php if ($certificateReady): ?>
        <a href="<?= url('/account/certificate') ?>" class="app-tile app-press">
          <span class="app-tile-icon"><?= icon('award') ?></span>
          <span class="font-bold text-gray-800">My Certificates</span>
          <span class="app-tile-sub">Your skills certificate is ready to view and print.</span>
        </a>
      <?php endif; ?>
    </aside>
  </div>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
