<?php
/** Requires $applications (the student's requests), $courses (each with application, locked, declined and providers) and $isEnrolled. */
require __DIR__ . '/../layout-header.php';

$isEnrolled = !empty($isEnrolled);
$courses = $courses ?? [];
$statusLabel = static fn(string $s): string => \App\Models\AttachmentApplication::statusLabel($s);
$progressFor = ['pending' => 10, 'paused' => 30, 'accepted' => 50, 'completed' => 100, 'recommended' => 100, 'rejected' => 0];
$unreadNotes = $unreadNotes ?? [];
$latestNotes = $latestNotes ?? [];

?>

<div class="att-page space-y-4">
  <p class="max-w-2xl text-sm text-neutral-600">One attachment per course: open an organisation, pick a branch, send your request. Once sent, the other organisations for that course are greyed out.</p>

  <?php if (!$isEnrolled): ?>
    <section class="flex flex-wrap items-center justify-between gap-2 rounded-lg border-l-4 bg-white px-3 py-2.5" style="border-color:var(--ke-green)">
      <p class="text-sm"><b>Enrol for a course first.</b> Pay at least <?= \App\Services\WalletService::minPaymentPercent() ?>% of its fee and attachment opens here.</p>
      <a href="<?= url('/account/courses') ?>" class="btn-primary" style="padding:.35rem .75rem">Browse courses</a>
    </section>
  <?php endif; ?>

  <?php if ($applications): ?>
    <section>
      <h2 class="att-h">My attachments</h2>
      <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($applications as $application):
          $status = (string) ($application['status'] ?? 'pending');
          $appId = (int) $application['id'];
          $latest = $latestNotes[$appId] ?? null;
          $newCount = (int) ($unreadNotes[$appId] ?? 0);
          $bad = in_array($status, ['rejected', 'paused'], true);
        ?>
          <article class="att-card">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <h3 class="truncate text-sm font-bold"><?= e($application['organisation_name'] ?: trim(($application['first_name'] ?? '') . ' ' . ($application['last_name'] ?? ''))) ?></h3>
                <p class="truncate text-xs text-neutral-500"><?= e($application['course_title'] ?? ($application['category_name'] ?? 'General')) ?><?= !empty($application['branch_title']) ? ' · ' . e($application['branch_title']) : '' ?></p>
              </div>
              <span class="att-pill <?= $bad ? 'is-bad' : '' ?>"><?= e($statusLabel($status)) ?></span>
            </div>
            <?php if ($status === 'rejected'): ?>
              <p class="text-xs" style="color:var(--ke-red)">Declined<?= !empty($application['provider_note']) ? ': ' . e($application['provider_note']) : '' ?>. Choose another organisation below.</p>
            <?php else: ?>
              <div class="h-1 overflow-hidden rounded-full bg-neutral-200"><div class="h-full" style="width:<?= (int) ($progressFor[$status] ?? 0) ?>%;background:<?= $status === 'paused' ? 'var(--ke-red)' : 'var(--ke-green)' ?>"></div></div>
            <?php endif; ?>
            <?php if ($latest && trim((string) $latest['body']) !== ''): ?>
              <p class="truncate text-xs text-neutral-600" title="<?= e((string) $latest['body']) ?>"><b><?= $latest['sender_side'] === 'student' ? 'You' : 'Branch' ?>:</b> <?= e(preg_replace('/\s+/', ' ', (string) $latest['body'])) ?></p>
            <?php endif; ?>
            <div class="flex flex-wrap items-center gap-1.5">
              <a href="<?= url('/account/attachments/' . $appId) ?>" class="att-btn">Notes<?= $newCount ? ' · ' . $newCount . ' new' : '' ?></a>
              <?php if ($status === 'recommended'): ?>
                <a href="<?= url('/account/recommendation-letter/' . $appId) ?>" class="att-btn is-primary">Recommendation letter</a>
              <?php elseif ($status === 'pending'): ?>
                <form method="post" action="<?= url('/account/attachments/' . $appId . '/cancel') ?>" onsubmit="return confirm('Cancel this request? You can then send a new one for this course.');">
                  <?= csrfField() ?>
                  <button type="submit" class="att-btn">Cancel request</button>
                </form>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($isEnrolled && !$courses): ?>
    <p class="text-sm text-neutral-500">None of your courses belongs to a category yet, so no organisation providing attachment shows here.</p>
  <?php endif; ?>

  <?php foreach ($courses as $course):
    $courseId = (int) $course['id'];
    $application = $course['application'];
    $courseFees = $course['fees'];
  ?>
    <section id="course-<?= $courseId ?>">
      <div class="flex flex-wrap items-baseline justify-between gap-x-3">
        <h2 class="att-h"><?= e($course['title']) ?> <span class="font-normal normal-case text-neutral-500">· <?= e($course['organisation_name']) ?><?= $course['requires_attachment'] ? ' · required' : '' ?></span></h2>
        <?php if ($course['locked']): ?>
          <span class="text-xs font-bold" style="color:var(--ke-green)"><?= e($statusLabel($application['status'])) ?> at <?= e($application['organisation_name'] ?: 'an organisation') ?></span>
        <?php endif; ?>
      </div>
      <?php if ($courseFees['fee'] > 0 && $courseFees['below_minimum']): ?>
        <p class="mb-1 text-xs" style="color:#92400e">Paid Ksh <?= number_format($courseFees['paid']) ?> of <?= number_format($courseFees['fee']) ?> — pay at least <?= \App\Services\WalletService::minPaymentPercent() ?>% to send a request. <a href="<?= url('/account/courses/' . $courseId . '/checkout') ?>" class="underline">Pay</a></p>
      <?php endif; ?>
      <?php if (!$course['providers']): ?>
        <p class="text-xs text-neutral-500">No organisation providing attachment takes students from this course yet.</p>
      <?php else: ?>
        <div class="att-grid">
          <?php foreach ($course['providers'] as $provider):
            $providerId = (int) $provider['id'];
            $branchCount = count($provider['attachment_branches']);
            $name = $provider['organisation_name'] ?: ($provider['listing_name'] ?: userDisplayName($provider));
            $isChosen = $course['locked'] && (int) $application['provider_user_id'] === $providerId;
            $wasDeclined = in_array($providerId, $course['declined'], true);
            $isOpen = !$course['locked'] && !$wasDeclined;
            $meta = array_filter([$provider['listing_location'] ?? '', $branchCount ? $branchCount . ' branch' . ($branchCount === 1 ? '' : 'es') : 'No branches']);
          ?>
            <<?= $isOpen ? 'a href="' . url('/account/attachment-providers/' . $providerId . '?course=' . $courseId) . '"' : 'div aria-disabled="true"' ?> class="att-card att-provider <?= $isOpen ? 'is-open' : ($isChosen ? 'is-chosen' : 'is-off') ?>" title="<?= e(trim(($provider['listing_offered'] ?? '') . ' ' . implode(', ', $provider['matching_categories']))) ?>">
              <div class="flex items-center justify-between gap-2">
                <h3 class="truncate text-sm font-bold"><?= e($name) ?></h3>
                <?php if (!empty($provider['is_internal'])): ?><span class="att-pill">Internal</span><?php endif; ?>
              </div>
              <p class="truncate text-xs text-neutral-500"><?= e(implode(' · ', $meta)) ?></p>
              <p class="text-xs font-bold" style="color:<?= $wasDeclined ? 'var(--ke-red)' : ($isOpen || $isChosen ? 'var(--ke-green)' : 'var(--ke-muted)') ?>"><?= $isOpen ? 'View branches →' : ($isChosen ? '✓ Your choice' : ($wasDeclined ? 'Declined you' : 'Not available')) ?></p>
            </<?= $isOpen ? 'a' : 'div' ?>>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
