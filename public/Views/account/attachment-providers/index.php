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

<div class="space-y-6">
  <section>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Attachment</p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold">Organisations providing attachment</h1>
    <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">
      Each course you take has its own attachment. For each course, open an organisation to see its branches and their contacts, then send your request to one branch.
      You make one request per course — once you have, the other organisations for that course are greyed out.
    </p>
  </section>

  <?php if (!$isEnrolled): ?>
    <section class="rounded-xl border-l-4 bg-white p-5 shadow-sm space-y-3" style="border-color:var(--ke-green)">
      <h2 class="font-bold text-gray-800">Enroll for a course first</h2>
      <p class="text-sm font-medium text-neutral-600">
        Attachment is for students who are taking a course. Enroll for a course — paying at least <?= \App\Services\WalletService::minPaymentPercent() ?>% of its fee — and the organisations providing attachment open here.
      </p>
      <a href="<?= url('/account/courses') ?>" class="btn-primary inline-block" style="padding:0.5rem 0.9rem;">Browse courses</a>
    </section>
  <?php endif; ?>


  <?php if ($applications): ?>
    <section class="space-y-3">
      <h2 class="font-serif-heading text-lg font-bold">My attachments</h2>
      <div class="grid gap-4 md:grid-cols-2">
        <?php foreach ($applications as $application):
          $status = (string) ($application['status'] ?? 'pending');
        ?>
          <article class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm space-y-3">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-[11px] font-black uppercase tracking-widest text-gray-500"><?= e($application['course_title'] ?? ($application['category_name'] ?? 'General')) ?></p>
                <h3 class="font-bold text-gray-800">
                  <?= e($application['organisation_name'] ?: trim(($application['first_name'] ?? '') . ' ' . ($application['last_name'] ?? ''))) ?>
                </h3>
                <?php if (!empty($application['branch_title'])): ?>
                  <p class="text-xs font-semibold text-gray-600"><?= e($application['branch_title']) ?><?= !empty($application['branch_location']) ? ' — ' . e($application['branch_location']) : '' ?></p>
                <?php endif; ?>
              </div>
              <span class="text-[11px] font-black uppercase shrink-0" style="color:<?= in_array($status, ['rejected', 'paused'], true) ? 'var(--ke-red)' : 'var(--ke-green)' ?>"><?= e($statusLabel($status)) ?></span>
            </div>

            <?php if ($status === 'rejected'): ?>
              <p class="text-xs font-bold" style="color:var(--ke-red)">The branch declined this request<?= !empty($application['provider_note']) ? ': ' . e($application['provider_note']) : '' ?>. Choose another organisation for this course below.</p>
            <?php else: ?>
              <div>
                <div class="h-2 overflow-hidden rounded-full bg-neutral-200">
                  <div class="h-full rounded-full" style="width: <?= (int) ($progressFor[$status] ?? 0) ?>%;background:var(--ke-green)"></div>
                </div>
                <div class="mt-2 grid grid-cols-3 text-[11px] font-bold uppercase text-neutral-500">
                  <span style="color:var(--ke-green)">Requested</span>
                  <span class="text-center" style="<?= in_array($status, ['accepted', 'completed', 'recommended'], true) ? 'color:var(--ke-green)' : ($status === 'paused' ? 'color:var(--ke-red)' : '') ?>"><?= $status === 'paused' ? 'On hold' : 'Accepted' ?></span>
                  <span class="text-right" style="<?= in_array($status, ['completed', 'recommended'], true) ? 'color:var(--ke-green)' : '' ?>">Completed</span>
                </div>
              </div>
            <?php endif; ?>

            <?php
              $appId = (int) $application['id'];
              $latest = $latestNotes[$appId] ?? null;
              $newCount = (int) ($unreadNotes[$appId] ?? 0);
            ?>
            <?php if ($latest && trim((string) $latest['body']) !== ''): ?>
              <p class="rounded-lg border px-3 py-2 text-xs font-semibold text-neutral-700 <?= $newCount ? '' : 'bg-gray-50' ?>" style="border-color:<?= $newCount ? '#f59e0b' : 'var(--ke-line)' ?>">
                <span class="font-black"><?= $latest['sender_side'] === 'student' ? 'You' : 'Branch' ?>:</span>
                <?= e(mb_strimwidth(preg_replace('/\s+/', ' ', (string) $latest['body']), 0, 140, '…')) ?>
              </p>
            <?php endif; ?>

            <div class="flex flex-wrap items-center gap-2">
              <a href="<?= url('/account/attachments/' . $appId) ?>" class="btn-secondary" style="padding:0.35rem 0.75rem;font-size:0.75rem;">
                Notes<?= $newCount ? ' · ' . $newCount . ' new' : '' ?>
              </a>
              <?php if ($status === 'recommended'): ?>
                <a href="<?= url('/account/recommendation-letter/' . $appId) ?>" class="btn-primary inline-block" style="padding:0.4rem 0.8rem;">View recommendation letter</a>
              <?php elseif ($status === 'pending'): ?>
                <form method="post" action="<?= url('/account/attachments/' . $appId . '/cancel') ?>" onsubmit="return confirm('Cancel this request? You can then send a new one for this course.');">
                  <?= csrfField() ?>
                  <button type="submit" class="btn-secondary" style="padding:0.35rem 0.75rem;font-size:0.75rem;">Cancel request</button>
                </form>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($isEnrolled && !$courses): ?>
    <p class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">
      None of your courses belongs to a category yet, so no organisation providing attachment can show here. Check back soon.
    </p>
  <?php endif; ?>

  <?php foreach ($courses as $course):
    $courseId = (int) $course['id'];
    $application = $course['application'];
  ?>
    <section class="space-y-3" id="course-<?= $courseId ?>">
      <div class="flex flex-wrap items-baseline justify-between gap-2">
        <div>
          <p class="text-[11px] font-black uppercase tracking-widest text-gray-500"><?= e($course['organisation_name']) ?><?= $course['requires_attachment'] ? ' · attachment required' : '' ?></p>
          <h2 class="font-serif-heading text-lg font-bold">Attachment for <?= e($course['title']) ?></h2>
        </div>
        <?php if ($course['locked']): ?>
          <span class="text-xs font-bold" style="color:var(--ke-green)">Requested at <?= e($application['organisation_name'] ?: 'an organisation') ?> · <?= e($statusLabel($application['status'])) ?></span>
        <?php endif; ?>
      </div>
      <?php $courseFees = $course['fees']; ?>
      <?php if ($courseFees['fee'] > 0): ?>
        <p class="text-xs font-bold" style="color:<?= $courseFees['below_minimum'] ? '#92400e' : 'var(--ke-muted)' ?>">
          This course: paid Ksh <?= number_format($courseFees['paid'], 2) ?> of Ksh <?= number_format($courseFees['fee'], 2) ?>
          <?php if ($courseFees['below_minimum']): ?>
            — pay at least <?= \App\Services\WalletService::minPaymentPercent() ?>% of this course's fee to send its request. <a href="<?= url('/account/courses/' . $courseId . '/checkout') ?>" class="underline">Pay</a>
          <?php endif; ?>
        </p>
      <?php endif; ?>
      <?php if (!$course['providers']): ?>
        <p class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">
          No organisation providing attachment takes students from this course yet. Check back soon.
        </p>
      <?php else: ?>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <?php foreach ($course['providers'] as $provider):
            $providerId = (int) $provider['id'];
            $branchCount = count($provider['attachment_branches']);
            $name = $provider['organisation_name'] ?: ($provider['listing_name'] ?: userDisplayName($provider));
            $isChosen = $course['locked'] && (int) $application['provider_user_id'] === $providerId;
            $wasDeclined = in_array($providerId, $course['declined'], true);
            $isOpen = !$course['locked'] && !$wasDeclined;
          ?>
            <<?= $isOpen ? 'a href="' . url('/account/attachment-providers/' . $providerId . '?course=' . $courseId) . '"' : 'div aria-disabled="true"' ?> class="group flex flex-col rounded-xl border bg-white p-5 shadow-sm transition <?= $isOpen ? 'border-neutral-200 hover:shadow-md hover:border-neutral-300' : ($isChosen ? '' : 'border-neutral-200 opacity-50') ?>" style="<?= $isChosen ? 'border-color:var(--ke-green)' : '' ?>">
              <?php if (!empty($provider['is_internal'])): ?>
                <span class="mb-1 self-start rounded-full px-2 py-0.5 text-[10px] font-black uppercase" style="background:#ecf7f0;color:var(--ke-green)">Internal</span>
              <?php endif; ?>
              <h3 class="font-serif-heading text-lg font-bold text-gray-800"><?= e($name) ?></h3>
              <?php if (!empty($provider['listing_offered'])): ?>
                <p class="mt-1 text-xs font-semibold text-gray-700"><?= e($provider['listing_offered']) ?></p>
              <?php endif; ?>
              <?php if (!empty($provider['listing_location'])): ?>
                <p class="mt-1 text-[11px] font-bold text-gray-500">📍 <?= e($provider['listing_location']) ?></p>
              <?php endif; ?>
              <div class="mt-3 flex flex-wrap gap-1.5">
                <?php foreach ($provider['matching_categories'] as $categoryName): ?>
                  <span class="rounded-full border px-2 py-0.5 text-[10px] font-black uppercase" style="border-color:var(--ke-green);color:var(--ke-green)"><?= e($categoryName) ?></span>
                <?php endforeach; ?>
              </div>
              <div class="mt-auto flex items-center justify-between pt-4">
                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500"><?= $branchCount ? $branchCount . ' branch' . ($branchCount === 1 ? '' : 'es') : 'No branches' ?></span>
                <?php if ($isOpen): ?>
                  <span class="btn-primary" style="padding:0.35rem 0.8rem;">View branches</span>
                <?php elseif ($isChosen): ?>
                  <span class="text-[11px] font-bold" style="color:var(--ke-green)">✓ Your choice</span>
                <?php elseif ($wasDeclined): ?>
                  <span class="text-[11px] font-bold" style="color:var(--ke-red)">Declined you</span>
                <?php else: ?>
                  <span class="text-[11px] font-bold" style="color:var(--ke-muted)">Not available</span>
                <?php endif; ?>
              </div>
            </<?= $isOpen ? 'a' : 'div' ?>>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
