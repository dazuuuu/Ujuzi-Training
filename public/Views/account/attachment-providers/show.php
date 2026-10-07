<?php
/**
 * One provider's page for one of the student's courses.
 * Requires $provider (with matching_categories and attachment_branches),
 * $course, $application (the student's request for this course, or null) and
 * $declined (provider ids that declined this course's request).
 */
require __DIR__ . '/../layout-header.php';

$providerId = (int) $provider['id'];
$name = $provider['organisation_name'] ?: ($provider['listing_name'] ?: userDisplayName($provider));
$branches = $provider['attachment_branches'];
$categories = $provider['matching_categories'];
$belowMinimum = !empty($feeStanding['below_minimum']);
$locked = $application && $application['status'] !== \App\Models\AttachmentApplication::STATUS_REJECTED;
$wasDeclined = in_array($providerId, $declined ?? [], true);
$canRequest = !$locked && !$wasDeclined && !$belowMinimum;
$chosenBranchId = $locked && (int) $application['provider_user_id'] === $providerId ? (int) ($application['branch_id'] ?? 0) : null;
?>

<div class="space-y-6">
  <a href="<?= url('/account/attachment-providers#course-' . (int) $course['id']) ?>" class="text-xs font-bold uppercase tracking-widest" style="color:var(--ke-green)">← Attachment for <?= e($course['title']) ?></a>

  <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-3">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <p class="text-[11px] font-black uppercase tracking-widest text-gray-500">Attachment for <?= e($course['title']) ?></p>
        <h1 class="font-serif-heading text-3xl font-bold"><?= e($name) ?></h1>
      </div>
      <?php if (!empty($provider['organisation_id'])): ?>
        <a href="<?= url('/account/organisations/' . (int) $provider['organisation_id']) ?>" class="btn-secondary"><?= icon('building', 'inline h-4 w-4 align-[-2px]') ?> Organisation details</a>
      <?php endif; ?>
    </div>
    <?php if (!empty($provider['listing_offered'])): ?>
      <p class="text-sm font-semibold text-gray-700"><?= e($provider['listing_offered']) ?></p>
    <?php endif; ?>
    <?php if (!empty($provider['listing_location'])): ?>
      <p class="text-xs font-bold text-gray-500">📍 <?= e($provider['listing_location']) ?></p>
    <?php endif; ?>
    <?php if (!empty($provider['phone']) || !empty($provider['email'])): ?>
      <p class="text-xs font-semibold text-gray-600">
        <?php if (!empty($provider['phone'])): ?>📞 <a class="underline" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $provider['phone'])) ?>"><?= e($provider['phone']) ?></a><?php endif; ?>
        <?php if (!empty($provider['email'])): ?>&nbsp; ✉️ <a class="underline" href="mailto:<?= e($provider['email']) ?>"><?= e($provider['email']) ?></a><?php endif; ?>
      </p>
    <?php endif; ?>
  </section>

  <?php if ($belowMinimum): ?>
    <p class="rounded-xl border-l-4 bg-white p-4 text-sm font-semibold text-neutral-700 shadow-sm" role="alert" style="border-color:#f59e0b">
      You have paid <?= (int) floor($feeStanding['paid_ratio'] * 100) ?>% of the <?= e($course['title']) ?> fee (Ksh <?= number_format($feeStanding['paid'], 2) ?> of Ksh <?= number_format($feeStanding['fee'], 2) ?>). Pay at least <?= \App\Services\WalletService::minPaymentPercent() ?>% of this course's fee to send its request.
      <a href="<?= url('/account/courses/' . (int) $course['id'] . '/checkout') ?>" class="font-bold underline" style="color:var(--ke-green)">Pay for this course</a>
    </p>
  <?php elseif ($wasDeclined): ?>
    <p class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-red)">
      This organisation already declined your request for <?= e($course['title']) ?>. You can't send it here again — choose another organisation.
    </p>
  <?php elseif ($locked): ?>
    <p class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">
      You already sent your request for <?= e($course['title']) ?> to <?= e($application['organisation_name'] ?: 'an organisation') ?>. You make one attachment request per course.
    </p>
  <?php endif; ?>

  <form method="post" action="<?= url('/account/attachments/select') ?>" class="space-y-6">
    <?= csrfField() ?>
    <input type="hidden" name="provider_id" value="<?= $providerId ?>">
    <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">

    <section class="space-y-3">
      <h2 class="font-serif-heading text-lg font-bold">1. Category <span class="text-sm font-semibold" style="color:var(--ke-red)">*</span></h2>
      <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
        <?php $first = true; foreach ($categories as $categoryId => $categoryName): ?>
          <label class="branch-pick">
            <input type="radio" name="category_id" value="<?= (int) $categoryId ?>" <?= $first ? 'required' : '' ?> <?= count($categories) === 1 ? 'checked' : '' ?> <?= $canRequest ? '' : 'disabled' ?>>
            <span class="text-sm font-bold text-gray-800"><?= e($categoryName) ?></span>
          </label>
        <?php $first = false; endforeach; ?>
      </div>
    </section>

    <section class="space-y-3">
      <h2 class="font-serif-heading text-lg font-bold"><?= $branches ? '2. Branch' : '2. Send your request' ?><?php if ($branches): ?> <span class="text-sm font-semibold" style="color:var(--ke-red)">*</span><?php endif; ?></h2>
      <?php if ($branches): ?>
        <p class="text-xs font-semibold" style="color:var(--ke-muted)">Pick one branch. Its branch admin reviews your request — their contacts are on each card.</p>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <?php $first = true; foreach ($branches as $branch): $branchId = (int) $branch['id']; ?>
            <label class="course-card flex cursor-pointer flex-col gap-1 <?= $canRequest || $chosenBranchId === $branchId ? '' : 'opacity-50' ?>" style="<?= $chosenBranchId === $branchId ? 'border-color:var(--ke-green)' : '' ?>">
              <span class="flex items-start gap-2">
                <input type="radio" name="branch_id" value="<?= $branchId ?>" class="mt-1.5" <?= $first ? 'required' : '' ?> <?= $chosenBranchId === $branchId ? 'checked' : '' ?> <?= $canRequest ? '' : 'disabled' ?>>
                <span class="min-w-0 flex-1"><?php $headingTag = 'span'; require __DIR__ . '/../partials/branch-card.php'; ?></span>
              </span>
              <?php if ($chosenBranchId === $branchId): ?><span class="text-xs font-bold" style="color:var(--ke-green)">✓ Your chosen branch</span><?php endif; ?>
            </label>
          <?php $first = false; endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-sm font-medium text-neutral-700">This organisation has no separate branches — its own admin reviews your request.</p>
      <?php endif; ?>
    </section>

    <?php if ($canRequest): ?>
      <button type="submit" class="btn-primary"><?= $branches ? 'Send request to this branch' : 'Send request' ?></button>
    <?php endif; ?>
  </form>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
