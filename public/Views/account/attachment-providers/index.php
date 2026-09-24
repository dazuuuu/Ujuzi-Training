<?php
/** Requires $application (student's own request or null) and $providers (every provider with a branch). */
require __DIR__ . '/../layout-header.php';

$status = $application ? (string) ($application['status'] ?? '') : '';
$canChoose = !$application || in_array($status, ['pending', 'rejected'], true);
$steps = ['pending' => 'Requested', 'accepted' => 'Accepted', 'recommended' => 'Completed'];
$stepOrder = ['pending' => 0, 'accepted' => 1, 'completed' => 2, 'recommended' => 2];
$currentStep = $stepOrder[$status] ?? 0;
$progress = $status === 'recommended' ? 100 : ($status === 'accepted' ? 50 : ($status === 'pending' ? 10 : 0));
?>

<div class="space-y-6">
  <section>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Attachment</p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold">Organisation providing attachment</h1>
    <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">
      Choose the organisation you want to do your attachment with, and the branch you want to go to if it has branches. The branch admin (or the organisation's admin, if it has no branches) reviews and accepts you and marks your attachment completed — your recommendation letter then appears here.
    </p>
  </section>

  <?php if ($application): ?>
    <section class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm space-y-4">
      <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="font-bold text-gray-800">My attachment</h2>
        <span class="text-[11px] font-black uppercase" style="color:<?= $status === 'rejected' ? 'var(--ke-red)' : 'var(--ke-green)' ?>">
          <?= e($status === 'recommended' ? 'Completed' : ucfirst($status)) ?>
        </span>
      </div>

      <div class="text-sm font-semibold text-neutral-700">
        <?= e($application['organisation_name'] ?: trim(($application['first_name'] ?? '') . ' ' . ($application['last_name'] ?? ''))) ?>
        <?php if (!empty($application['branch_title'])): ?>
          · <?= e($application['branch_title']) ?><?= !empty($application['branch_location']) ? ' — ' . e($application['branch_location']) : '' ?>
        <?php endif; ?>
      </div>

      <?php if ($status !== 'rejected'): ?>
        <div>
          <div class="h-2 overflow-hidden rounded-full bg-neutral-200">
            <div class="h-full rounded-full" style="width: <?= $progress ?>%;background:var(--ke-green)"></div>
          </div>
          <div class="mt-2 grid grid-cols-3 text-[11px] font-bold uppercase text-neutral-500">
            <?php foreach (array_values($steps) as $i => $label): ?>
              <span class="<?= $i === 1 ? 'text-center' : ($i === 2 ? 'text-right' : '') ?>" style="<?= $currentStep >= $i ? 'color:var(--ke-green)' : '' ?>"><?= e($label) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <p class="text-xs font-bold" style="color:var(--ke-red)">The branch declined this request<?= !empty($application['provider_note']) ? ': ' . e($application['provider_note']) : '' ?>. Choose another below.</p>
      <?php endif; ?>

      <?php if ($status === 'recommended'): ?>
        <div class="text-xs font-semibold text-neutral-600">
          Attachment finished<?= !empty($application['recommended_at']) ? ' on ' . e(date('j M Y', strtotime((string) $application['recommended_at']))) : '' ?>.
        </div>
        <a href="<?= url('/account/recommendation-letter/' . (int) $application['id']) ?>" class="btn-primary inline-block" style="padding:0.5rem 0.9rem;">View document</a>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php if ($canChoose): ?>
    <section class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm space-y-4">
      <h2 class="font-bold text-gray-800"><?= $application ? 'Change where you want to go' : 'Choose where you want to go' ?></h2>

      <?php if (!$providers): ?>
        <p class="text-sm font-bold text-neutral-500">No organisation providing attachment is registered yet. Check back soon.</p>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($providers as $provider): ?>
            <details class="rounded-xl border border-gray-200 bg-gray-50">
              <summary class="cursor-pointer list-none p-4 flex items-center justify-between gap-3">
                <div>
                  <h3 class="font-bold text-gray-800"><?= e($provider['organisation_name'] ?: ($provider['listing_name'] ?: userDisplayName($provider))) ?></h3>
                  <?php if (!empty($provider['listing_offered'])): ?>
                    <p class="text-xs font-semibold text-gray-700 mt-1"><?= e($provider['listing_offered']) ?></p>
                  <?php endif; ?>
                  <?php if (!empty($provider['listing_location'])): ?>
                    <p class="text-[11px] font-bold text-gray-500 mt-1">📍 <?= e($provider['listing_location']) ?></p>
                  <?php endif; ?>
                  <p class="text-[10px] text-gray-500 uppercase tracking-widest mt-1"><?= $provider['attachment_branches'] ? count($provider['attachment_branches']) . ' branch' . (count($provider['attachment_branches']) === 1 ? '' : 'es') : 'No branches' ?></p>
                </div>
                <span class="text-xs font-bold text-gray-500 shrink-0"><?= $provider['attachment_branches'] ? 'Select branch ▾' : 'Request ▾' ?></span>
              </summary>
              <div class="border-t border-gray-200 p-4">
                <form method="post" action="<?= url('/account/attachments/select') ?>" class="space-y-2">
                  <?= csrfField() ?>
                  <input type="hidden" name="provider_id" value="<?= (int) $provider['id'] ?>" />
                  <?php if ($provider['attachment_branches']): ?>
                    <label class="text-[11px] font-bold uppercase text-gray-500">Branch you want to go to</label>
                    <select name="branch_id" class="w-full rounded bg-white border border-gray-200 p-2 text-xs" required>
                      <option value="">Choose branch</option>
                      <?php foreach ($provider['attachment_branches'] as $branch): ?>
                        <option value="<?= (int) $branch['id'] ?>" <?= $application && (int) $application['branch_id'] === (int) $branch['id'] ? 'selected' : '' ?>><?= e($branch['title']) ?><?= !empty($branch['location']) ? ' — ' . e($branch['location']) : '' ?></option>
                      <?php endforeach; ?>
                    </select>
                  <?php else: ?>
                    <p class="text-xs font-semibold text-gray-500">This organisation has no separate branches — its own admin will review your request.</p>
                  <?php endif; ?>
                  <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded text-xs transition mt-2">Send request</button>
                </form>
              </div>
            </details>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
