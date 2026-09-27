<?php
/** Requires $applications (the student's requests), $providers (visible to them, each with open_category_ids), $takenCategories and $isEnrolled. */
require __DIR__ . '/../layout-header.php';

$isEnrolled = !empty($isEnrolled);
$takenCategories = $takenCategories ?? [];
$belowMinimum = !empty($feeStanding['below_minimum']);
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
      These organisations take on students from the courses you enrolled for. Open one to see its branches and send a request to one branch.
      You can make one attachment request per course category — once you have, the other organisations for that category are greyed out.
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

  <?php if ($isEnrolled && $belowMinimum): ?>
    <section class="rounded-xl border-l-4 bg-white p-5 shadow-sm space-y-2" role="alert" style="border-color:#f59e0b">
      <h2 class="font-bold text-gray-800">Pay at least <?= \App\Services\WalletService::minPaymentPercent() ?>% of your fees to send a request</h2>
      <p class="text-sm font-medium text-neutral-600">
        You have paid Ksh <?= number_format($feeStanding['paid'], 2) ?> of Ksh <?= number_format($feeStanding['fee'], 2) ?>
        (<?= (int) floor($feeStanding['paid_ratio'] * 100) ?>%). You can look at the organisations below, but you can send a request only once you've paid at least <?= \App\Services\WalletService::minPaymentPercent() ?>%.
      </p>
      <a href="<?= url('/account/wallet') ?>" class="btn-primary inline-block" style="padding:0.5rem 0.9rem;">Go to wallet</a>
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
                <p class="text-[11px] font-black uppercase tracking-widest text-gray-500"><?= e($application['category_name'] ?? 'General') ?></p>
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
              <p class="text-xs font-bold" style="color:var(--ke-red)">The branch declined this request<?= !empty($application['provider_note']) ? ': ' . e($application['provider_note']) : '' ?>. Choose another provider below.</p>
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
                <form method="post" action="<?= url('/account/attachments/' . $appId . '/cancel') ?>" onsubmit="return confirm('Cancel this request? You can then send a new one for this course category.');">
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

  <?php if ($isEnrolled): ?>
    <section class="space-y-3">
      <h2 class="font-serif-heading text-lg font-bold">Choose where you want to go</h2>
      <?php if (!$providers): ?>
        <p class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">
          No organisation providing attachment takes students from your courses' categories yet. Check back soon.
        </p>
      <?php else: ?>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <?php foreach ($providers as $provider):
            $providerId = (int) $provider['id'];
            $branchCount = count($provider['attachment_branches']);
            $name = $provider['organisation_name'] ?: ($provider['listing_name'] ?: userDisplayName($provider));
          ?>
            <?php $isOpen = !empty($provider['open_category_ids']); ?>
            <<?= $isOpen ? 'a href="' . url('/account/attachment-providers/' . $providerId) . '"' : 'div aria-disabled="true"' ?> class="group flex flex-col rounded-xl border border-neutral-200 bg-white p-5 shadow-sm transition <?= $isOpen ? 'hover:shadow-md hover:border-neutral-300' : 'opacity-50' ?>">
              <h3 class="font-serif-heading text-lg font-bold text-gray-800"><?= e($name) ?></h3>
              <?php if (!empty($provider['listing_offered'])): ?>
                <p class="mt-1 text-xs font-semibold text-gray-700"><?= e($provider['listing_offered']) ?></p>
              <?php endif; ?>
              <?php if (!empty($provider['listing_location'])): ?>
                <p class="mt-1 text-[11px] font-bold text-gray-500">📍 <?= e($provider['listing_location']) ?></p>
              <?php endif; ?>
              <div class="mt-3 flex flex-wrap gap-1.5">
                <?php foreach ($provider['matching_categories'] as $categoryId => $category):
                  $taken = $takenCategories[$categoryId] ?? null;
                  $here = $taken && (int) $taken['provider_user_id'] === $providerId;
                ?>
                  <span class="rounded-full border px-2 py-0.5 text-[10px] font-black uppercase" style="border-color:<?= $taken && !$here ? 'var(--ke-line)' : 'var(--ke-green)' ?>;color:<?= $taken && !$here ? 'var(--ke-muted)' : 'var(--ke-green)' ?>">
                    <?= e($category['name']) ?><?= $here ? ' · ' . e($statusLabel($taken['status'])) : ($taken ? ' · requested elsewhere' : '') ?>
                  </span>
                <?php endforeach; ?>
              </div>
              <div class="mt-auto flex items-center justify-between pt-4">
                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500"><?= $branchCount ? $branchCount . ' branch' . ($branchCount === 1 ? '' : 'es') : 'No branches' ?></span>
                <?php if ($isOpen): ?>
                  <span class="btn-primary" style="padding:0.35rem 0.8rem;">View</span>
                <?php else: ?>
                  <span class="text-[11px] font-bold" style="color:var(--ke-muted)">Already requested</span>
                <?php endif; ?>
              </div>
            </<?= $isOpen ? 'a' : 'div' ?>>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
