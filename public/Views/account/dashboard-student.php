<?php
/**
 * Student dashboard: registration number, small summary cards (money
 * blurred until tapped), courses in progress, new courses and popular ones.
 * Requires $registrationNumber, $walletShort, $wallet, $fees (a
 * WalletService::feeSummaries() entry or null), $applications, $unreadNotes,
 * $certificateReady, $needsProfile, $minPaymentPercent, $newCourses and
 * $popularCourses.
 */
use App\Models\AttachmentApplication;

require __DIR__ . '/layout-header.php';

$ksh = static fn(float $n): string => 'Ksh ' . number_format($n, 2);
$courses = $fees['items'] ?? [];
$ongoing = array_values(array_filter($courses, static fn(array $c): bool => empty($c['final_passed']) || (int) $c['progress'] < 100));
// Fees are per course: never one total across courses.
$owing = array_values(array_filter($courses, static fn(array $c): bool => (float) $c['balance'] > 0));
$owed = count($owing);
$firstName = $currentUser['first_name'] ?: userDisplayName($currentUser);
$liveAttachment = null;
foreach ($applications as $application) {
    if ($application['status'] !== 'rejected') { $liveAttachment = $application; break; }
}
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

/** A small course card for the sliding rows. */
$miniCourse = static function (array $course, string $meta): void {
    $cover = (string) ($course['cover_image'] ?? '');
    $hasCover = $cover !== '' && preg_match('/\.(jpe?g|png|webp|gif)$/i', $cover);
    $isNew = (strtotime((string) ($course['created_at'] ?? '')) ?: 0) > strtotime('-14 days');
    $fee = (float) ($course['enrollment_fee_ksh'] ?? 0);
    ?>
    <a href="<?= url('/account/courses/' . (int) $course['id']) ?>" class="mini-course app-press">
      <span class="mini-course-cover">
        <?php if ($hasCover): ?><img src="<?= e(imageUrl($cover)) ?>" alt="" loading="lazy"><?php else: ?><?= e(mb_strtoupper(mb_substr(trim((string) $course['title']), 0, 1))) ?><?php endif; ?>
        <?php if ($isNew): ?><span class="course-new-ribbon">New</span><?php endif; ?>
      </span>
      <span class="mini-course-body">
        <span class="mini-course-title"><?= e($course['title']) ?></span>
        <span class="mini-course-meta block"><?= e($meta) ?></span>
        <span class="mt-1 block text-xs font-black" style="color:var(--ke-green)"><?= $fee > 0 ? 'Ksh ' . number_format($fee) : 'Free' ?></span>
      </span>
    </a>
    <?php
};
?>

<div class="space-y-6">
  <section class="flex flex-wrap items-center justify-between gap-3">
    <div class="min-w-0">
      <p class="text-xs font-semibold text-neutral-500"><?= e($greeting) ?>,</p>
      <h1 class="truncate text-2xl font-black text-gray-900"><?= e($firstName) ?></h1>
    </div>
    <?php if (!empty($registrationNumber)): ?>
      <p class="rounded-lg border bg-white px-3 py-1.5 text-right" style="border-color:var(--ke-line)">
        <span class="block text-[10px] font-black uppercase tracking-widest text-neutral-500">Registration no.</span>
        <span class="block text-sm font-black tracking-wide text-gray-800"><?= e($registrationNumber) ?></span>
      </p>
    <?php endif; ?>
  </section>

  <?php if (!empty($needsProfile)): ?>
    <a href="<?= url('/account/profile') ?>" class="flex items-center justify-between gap-3 rounded-xl border-l-4 border-red-500 bg-red-50 p-3 text-sm text-red-800 app-press">
      <span><strong class="font-black">Complete your profile</strong> — finish your registration details.</span>
      <?= icon('arrow-right', 'h-4 w-4 shrink-0') ?>
    </a>
  <?php endif; ?>

  <?php if (!empty($walletShort)): ?>
    <section class="flex flex-wrap items-start gap-3 rounded-xl border p-3 text-sm font-semibold" role="alert" style="border-color:#f59e0b;background:#fffbeb;color:#92400e">
      <?= icon('alert', 'h-5 w-5 shrink-0') ?>
      <div class="min-w-0 flex-1">
        <p class="font-black">Your wallet is running out of coins</p>
        <ul class="mt-1 space-y-0.5">
          <?php foreach ($walletShort as $short): ?>
            <li><?= e($short['title']) ?> — the next module needs <?= \App\Services\ModuleAccess::coins($short['needs']) ?> coins.</li>
          <?php endforeach; ?>
        </ul>
      </div>
      <a href="<?= url('/account/wallet') ?>" class="btn-primary shrink-0">Deposit</a>
    </section>
  <?php endif; ?>

  <!-- Small summary cards; money stays blurred until "See" -->
  <section class="mini-grid" aria-label="Summary">
    <div class="mini-card">
      <span class="mini-card-icon"><?= icon('wallet') ?></span>
      <span class="min-w-0 flex-1">
        <span class="mini-card-label">Wallet balance</span>
        <span class="mini-card-value is-private" id="wallet-amount"><?= e($ksh($wallet['balance'])) ?></span>
      </span>
      <button type="button" class="outline-btn" data-reveal="wallet-amount" aria-label="Show wallet balance for 10 seconds"><?= icon('eye', 'h-3.5 w-3.5') ?>&nbsp;See</button>
    </div>
    <div class="mini-card">
      <span class="mini-card-icon" style="<?= $owed > 0 ? 'background:#fef2f2;color:var(--ke-red)' : '' ?>"><?= icon('coins') ?></span>
      <span class="min-w-0 flex-1">
        <span class="mini-card-label">Course fees</span>
        <span class="mini-card-value" style="<?= $owed > 0 ? 'color:var(--ke-red)' : '' ?>"><?= $owed > 0 ? $owed . ' course' . ($owed === 1 ? '' : 's') . ' with a balance' : 'All cleared' ?></span>
      </span>
      <a href="#fees-by-course" class="outline-btn">See</a>
    </div>
    <a href="<?= url('/account/courses') ?>" class="mini-card app-press">
      <span class="mini-card-icon"><?= icon('book') ?></span>
      <span class="min-w-0">
        <span class="mini-card-label">My courses</span>
        <span class="mini-card-value"><?= count($courses) ?> enrolled</span>
      </span>
    </a>
    <a href="<?= url($liveAttachment ? '/account/attachments/' . (int) $liveAttachment['id'] : '/account/attachment-providers') ?>" class="mini-card app-press">
      <span class="mini-card-icon"><?= icon('briefcase') ?></span>
      <span class="min-w-0">
        <span class="mini-card-label">Attachment<?= $unreadNotes ? ' · ' . (int) $unreadNotes . ' new' : '' ?></span>
        <span class="mini-card-value"><?= e($liveAttachment ? AttachmentApplication::statusLabel($liveAttachment['status']) : 'Not requested') ?></span>
      </span>
    </a>
  </section>

  <?php $depositReturn = '/account/dashboard'; require __DIR__ . '/partials/deposit-form.php'; ?>

  <?php $paidCourses = array_values(array_filter($courses, static fn(array $c): bool => (float) $c['fee'] > 0)); ?>
  <?php if ($paidCourses): ?>
    <section class="space-y-2" id="fees-by-course">
      <div class="section-head"><h2>Fees by course</h2><a href="<?= url('/account/wallet') ?>">My wallet</a></div>
      <ul class="grid gap-2 sm:grid-cols-2">
        <?php foreach ($paidCourses as $c): ?>
          <li class="rounded-xl border bg-white p-3" style="border-color:<?= !empty($c['below_minimum']) ? '#f59e0b' : 'var(--ke-line)' ?>">
            <p class="truncate text-sm font-black text-gray-800" title="<?= e($c['title']) ?>"><?= e($c['title']) ?></p>
            <p class="mt-1 text-xs font-semibold text-neutral-600">Paid <?= e($ksh((float) $c['paid'])) ?> of <?= e($ksh((float) $c['fee'])) ?></p>
            <?php if ((float) $c['balance'] > 0): ?>
              <p class="mt-1 flex flex-wrap items-center justify-between gap-2 text-xs font-black" style="color:var(--ke-red)">
                Balance <?= e($ksh((float) $c['balance'])) ?>
                <a href="<?= url('/account/courses/' . (int) $c['course_id'] . '/checkout') ?>" class="btn-primary" style="padding:0.2rem 0.6rem;font-size:0.7rem;">Pay</a>
              </p>
              <?php if (!empty($c['below_minimum'])): ?>
                <p class="mt-1 text-[11px] font-bold" style="color:#92400e">Pay at least <?= (int) $minPaymentPercent ?>% of this course's fee to request its attachment.</p>
              <?php endif; ?>
            <?php else: ?>
              <p class="mt-1 text-xs font-black" style="color:var(--ke-green)">Cleared</p>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

  <!-- Courses in progress -->
  <section class="space-y-2">
    <div class="section-head">
      <h2>Continue learning</h2>
      <a href="<?= url('/account/courses') ?>">All courses</a>
    </div>
    <?php if (!$ongoing): ?>
      <div class="rounded-xl border border-dashed p-5 text-center" style="border-color:var(--ke-line)">
        <p class="text-sm font-bold" style="color:var(--ke-muted)"><?= $courses ? 'You have finished all your courses.' : "You haven't enrolled for a course yet." ?></p>
        <?php if ($certificateReady): ?>
          <a href="<?= url('/account/certificate') ?>" class="btn-primary mt-3 inline-block">My Certificates</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <ul class="grid gap-2 lg:grid-cols-2">
        <?php foreach (array_slice($ongoing, 0, 4) as $course):
          $cover = $course['cover_image'] ?? '';
          $hasCover = $cover !== '' && preg_match('/\.(jpe?g|png|webp|gif)$/i', $cover);
        ?>
          <li>
            <a href="<?= url('/account/courses/' . (int) $course['course_id']) ?>" class="mini-card app-press">
              <?php if ($hasCover): ?>
                <img src="<?= e(imageUrl($cover)) ?>" alt="" loading="lazy" class="h-12 w-16 shrink-0 rounded-lg object-cover">
              <?php else: ?>
                <span class="flex h-12 w-16 shrink-0 items-center justify-center rounded-lg text-lg font-black text-white" style="background:var(--ke-green)" aria-hidden="true"><?= e(mb_strtoupper(mb_substr(trim($course['title']), 0, 1))) ?></span>
              <?php endif; ?>
              <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-bold text-gray-800"><?= e($course['title']) ?></span>
                <span class="mt-1.5 flex items-center gap-2">
                  <span class="app-progressbar flex-1" role="progressbar" aria-valuenow="<?= (int) $course['progress'] ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= e($course['title']) ?> progress"><span style="width:<?= (int) $course['progress'] ?>%"></span></span>
                  <span class="text-xs font-black text-neutral-700"><?= (int) $course['progress'] ?>%</span>
                </span>
                <span class="mt-0.5 block truncate text-[11px] font-semibold text-neutral-500"><?= (int) $course['modules_passed'] ?> of <?= (int) $course['modules_total'] ?> modules</span>
              </span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <?php if ($newCourses): ?>
    <section class="space-y-2">
      <div class="section-head">
        <h2>New courses</h2>
        <a href="<?= url('/account/courses') ?>">See all</a>
      </div>
      <div class="h-scroll">
        <?php foreach ($newCourses as $course) { $miniCourse($course, ($course['organisation_name'] ?? '') . ' · ' . date('j M', strtotime((string) $course['created_at']))); } ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($popularCourses): ?>
    <section class="space-y-2">
      <div class="section-head">
        <h2>Popular courses</h2>
        <a href="<?= url('/account/courses') ?>">See all</a>
      </div>
      <div class="h-scroll">
        <?php foreach ($popularCourses as $course) { $miniCourse($course, $course['enrolled_count'] . ' student' . ($course['enrolled_count'] === 1 ? '' : 's') . ' enrolled'); } ?>
      </div>
    </section>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
