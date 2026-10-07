<?php
$pendingTrainerRequests = is_array($pendingTrainerRequests ?? null) ? $pendingTrainerRequests : [];
?>
<section class="space-y-3">
  <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
    <div>
      <h2 class="font-serif-heading text-lg font-bold">Trainer requests</h2>
      <p class="mt-1 text-xs font-semibold" style="color:var(--ke-muted)">Approve trainers, tutors, or teachers who selected your organisation. Once approved, they can create courses for your organisation.</p>
    </div>
    <?php if ($pendingTrainerRequests): ?>
      <span class="rounded-full px-2 py-1 text-[10px] font-black uppercase text-white shrink-0" style="background:var(--ke-red)"><?= count($pendingTrainerRequests) ?> pending</span>
    <?php endif; ?>
  </div>

  <?php if (!$pendingTrainerRequests): ?>
    <p class="rounded-xl border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No pending trainer requests.</p>
  <?php else: ?>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
      <table class="excel-table admin-data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Role</th>
            <th>Contact</th>
            <th>Categories</th>
            <th>Profile details</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pendingTrainerRequests as $request):
            $requestCategoryIds = [];
            if (!empty($request['category_ids'])) {
              $decoded = is_string($request['category_ids']) ? json_decode($request['category_ids'], true) : $request['category_ids'];
              $requestCategoryIds = is_array($decoded) ? array_map('intval', $decoded) : [];
            }
            $requestCategoryNames = $requestCategoryIds ? \App\Models\OrganisationCategory::namesByIds($requestCategoryIds) : [];
          ?>
            <tr>
              <td class="font-black"><?= e(userDisplayName($request)) ?></td>
              <td><?= e($request['role_name'] ?? 'Trainer') ?></td>
              <td><?= e(implode(' · ', array_filter([$request['phone'] ?? '', $request['email'] ?? ''])) ?: '—') ?></td>
              <td>
                <?php if ($requestCategoryNames): ?>
                  <?php foreach ($requestCategoryNames as $name): ?>
                    <span class="rounded-full bg-blue-50 text-blue-700 px-2 py-0.5 text-[10px] font-bold whitespace-nowrap"><?= e($name) ?></span>
                  <?php endforeach; ?>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($request['profile_details'])): ?>
                  <?php foreach ($request['profile_details'] as $detail): ?>
                    <p class="text-xs"><span class="font-bold"><?= e($detail['label'] ?? 'Detail') ?>:</span> <?= e($detail['value'] ?? '') ?></p>
                  <?php endforeach; ?>
                <?php else: ?>
                  <span class="text-xs font-bold text-neutral-500">Not submitted yet.</span>
                <?php endif; ?>
              </td>
              <td><span class="text-[11px] font-black uppercase" style="color:var(--ke-red)">Pending</span></td>
              <td>
                <div class="excel-row-actions-visible">
                  <form method="post" action="<?= url('/account/trainer-requests/' . (int) $request['id'] . '/approve') ?>">
                    <?= csrfField() ?>
                    <button type="submit" class="btn-primary" style="padding:0.25rem 0.5rem;font-size:0.7rem;">Approve</button>
                  </form>
                  <form method="post" action="<?= url('/account/trainer-requests/' . (int) $request['id'] . '/reject') ?>">
                    <?= csrfField() ?>
                    <button type="submit" class="btn-danger" style="padding:0.25rem 0.5rem;font-size:0.7rem;">Reject</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
