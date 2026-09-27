<?php
/**
 * Requires $provider (with matching_categories and attachment_branches),
 * $takenCategories (category id => the student's live request in it) and
 * $chosenBranch (branch already chosen at this provider: id, 0 for the
 * organisation itself, or null).
 */
require __DIR__ . '/../layout-header.php';

$providerId = (int) $provider['id'];
$name = $provider['organisation_name'] ?: ($provider['listing_name'] ?: userDisplayName($provider));
$branches = $provider['attachment_branches'];

// Categories still free for a request; the rest already have one (here or elsewhere).
$openCategories = array_diff_key($provider['matching_categories'], $takenCategories);
$categoryLabel = static fn(array $c): string => $c['name'] . ($c['courses'] ? ' — ' . implode(', ', $c['courses']) : '');
$belowMinimum = !empty($feeStanding['below_minimum']);
?>

<div class="space-y-6">
  <a href="<?= url('/account/attachment-providers') ?>" class="text-xs font-bold uppercase tracking-widest" style="color:var(--ke-green)">← All organisations providing attachment</a>

  <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-3">
    <h1 class="font-serif-heading text-3xl font-bold"><?= e($name) ?></h1>
    <?php if (!empty($provider['listing_offered'])): ?>
      <p class="text-sm font-semibold text-gray-700"><?= e($provider['listing_offered']) ?></p>
    <?php endif; ?>
    <?php if (!empty($provider['listing_location'])): ?>
      <p class="text-xs font-bold text-gray-500">📍 <?= e($provider['listing_location']) ?></p>
    <?php endif; ?>
    <div>
      <p class="text-[11px] font-black uppercase tracking-widest text-gray-500">Takes students from your courses in</p>
      <div class="mt-2 flex flex-wrap gap-1.5">
        <?php foreach ($provider['matching_categories'] as $categoryId => $category):
          $taken = $takenCategories[$categoryId] ?? null;
        ?>
          <span class="rounded-full border px-2 py-0.5 text-[11px] font-bold <?= $taken ? 'opacity-50' : '' ?>" style="border-color:<?= $taken ? 'var(--ke-line)' : 'var(--ke-green)' ?>;color:<?= $taken ? 'var(--ke-muted)' : 'var(--ke-green)' ?>">
            <?= e($categoryLabel($category)) ?><?= $taken ? ' · requested at ' . e($taken['organisation_name'] ?: 'another organisation') : '' ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php if ($belowMinimum): ?>
    <p class="rounded-xl border-l-4 bg-white p-4 text-sm font-semibold text-neutral-700 shadow-sm" role="alert" style="border-color:#f59e0b">
      You have paid <?= (int) floor($feeStanding['paid_ratio'] * 100) ?>% of your course fees. Pay at least <?= \App\Services\WalletService::minPaymentPercent() ?>% to send a request.
      <a href="<?= url('/account/wallet') ?>" class="font-bold underline" style="color:var(--ke-green)">Go to wallet</a>
    </p>
  <?php endif; ?>

  <?php if (!$openCategories): ?>
    <p class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">
      You have already sent a request for every course category this organisation takes. You can make one attachment request per course category.
    </p>
  <?php endif; ?>

  <section class="space-y-3">
    <h2 class="font-serif-heading text-lg font-bold"><?= $branches ? 'Branches' : 'Send your request' ?></h2>
    <?php if ($branches && $chosenBranch === null): ?>
      <p class="text-xs font-semibold" style="color:var(--ke-muted)">Pick one branch. Its branch admin reviews your request. You can choose only one branch at this organisation.</p>
    <?php elseif ($branches): ?>
      <p class="text-xs font-semibold" style="color:var(--ke-muted)">You already chose a branch here, so the others are greyed out.</p>
    <?php endif; ?>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($branches ?: [null] as $branch):
        $branchId = $branch ? (int) $branch['id'] : 0;
        $isChosen = $chosenBranch !== null && $chosenBranch === $branchId;
        $otherChosen = $chosenBranch !== null && !$isChosen;
        $canRequest = $openCategories && !$otherChosen && !$belowMinimum;
      ?>
        <article class="course-card flex flex-col <?= $canRequest || $isChosen ? '' : 'opacity-50' ?>" <?= $canRequest ? '' : 'aria-disabled="true"' ?>>
          <?php if ($branch): ?>
            <?php $headingTag = 'h3'; require __DIR__ . '/../partials/branch-card.php'; ?>
          <?php else: ?>
            <h3 class="font-serif-heading text-lg font-bold"><?= e($name) ?></h3>
            <p class="text-sm font-medium text-neutral-700">This organisation has no separate branches — its own admin reviews your request.</p>
          <?php endif; ?>

          <div class="mt-auto pt-3">
            <?php if ($isChosen): ?>
              <p class="mb-2 text-xs font-bold" style="color:var(--ke-green)">✓ Your chosen <?= $branch ? 'branch' : 'organisation' ?></p>
            <?php endif; ?>

            <?php if ($canRequest): ?>
              <form method="post" action="<?= url('/account/attachments/select') ?>" class="space-y-2">
                <?= csrfField() ?>
                <input type="hidden" name="provider_id" value="<?= $providerId ?>">
                <?php if ($branchId): ?><input type="hidden" name="branch_id" value="<?= $branchId ?>"><?php endif; ?>
                <?php if (count($openCategories) === 1): $only = array_key_first($openCategories); ?>
                  <input type="hidden" name="category_id" value="<?= (int) $only ?>">
                  <p class="text-xs font-semibold text-gray-600">For <strong><?= e($categoryLabel($openCategories[$only])) ?></strong></p>
                <?php else: ?>
                  <label class="block text-[11px] font-bold uppercase text-gray-500">Course you are requesting for
                    <select name="category_id" required class="mt-1 w-full rounded bg-white border border-gray-200 p-2 text-xs normal-case">
                      <option value="">Choose course category</option>
                      <?php foreach ($openCategories as $id => $category): ?>
                        <option value="<?= (int) $id ?>"><?= e($categoryLabel($category)) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                <?php endif; ?>
                <button type="submit" class="btn-primary w-full" style="padding:0.45rem 0.8rem;">
                  <?= $branch ? 'Send request to this branch' : 'Send request' ?>
                </button>
              </form>
            <?php else: ?>
              <button type="button" class="btn-secondary w-full" style="padding:0.45rem 0.8rem;cursor:not-allowed;" disabled>
                <?= $belowMinimum ? 'Pay ' . \App\Services\WalletService::minPaymentPercent() . '% of your fees first' : ($otherChosen ? 'You chose another branch' : 'Already requested') ?>
              </button>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
