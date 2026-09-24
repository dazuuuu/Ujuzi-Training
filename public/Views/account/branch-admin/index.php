<?php
/** Requires $branches, $applications in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Branch admin</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold">Attachees</h1>
      <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">
        Accept attachees for your branch<?= count($branches) > 1 ? 'es' : '' ?>, then mark their attachment complete once it's finished. The attachment owner certifies the student afterwards.
      </p>
    </div>
  </section>

  <?php if (!$branches): ?>
    <section class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">
      No branch has been assigned to you yet. Ask the attachment provider to assign you as a branch admin.
    </section>
  <?php elseif (!$applications): ?>
    <section class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">
      No attachees for your branch<?= count($branches) > 1 ? 'es' : '' ?> yet.
    </section>
  <?php else: ?>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
      <table class="excel-table admin-data-table">
        <thead>
          <tr>
            <th>Student</th>
            <th>Branch</th>
            <th>Requested</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($applications as $application):
            $status = (string) ($application['status'] ?? 'pending');
            $studentName = trim((string) ($application['first_name'] ?? '') . ' ' . (string) ($application['last_name'] ?? '')) ?: (string) ($application['email'] ?? 'Student');
            $statusColors = ['pending' => 'var(--ke-red)', 'accepted' => 'var(--ke-green)', 'recommended' => 'var(--ke-green)'];
          ?>
            <tr>
              <td class="font-black"><?= e($studentName) ?></td>
              <td><?= e($application['branch_title'] ?? '—') ?></td>
              <td><?= !empty($application['selected_at']) ? e(date('j M Y', strtotime((string) $application['selected_at']))) : '—' ?></td>
              <td><span class="text-[11px] font-black uppercase" style="color:<?= $statusColors[$status] ?? 'var(--ke-muted)' ?>"><?= e($status) ?></span></td>
              <td>
                <?php if ($status === 'pending'): ?>
                  <form method="post" action="<?= url('/account/branch-admin/' . (int) $application['id'] . '/accept') ?>"><?= csrfField() ?><button class="btn-primary" style="padding:0.25rem 0.5rem;font-size:0.7rem;">Accept</button></form>
                <?php elseif ($status === 'accepted'): ?>
                  <form method="post" action="<?= url('/account/branch-admin/' . (int) $application['id'] . '/complete') ?>" onsubmit="return confirm('Mark this attachment complete? This generates their recommendation letter right away.');"><?= csrfField() ?><button class="btn-primary" style="padding:0.25rem 0.5rem;font-size:0.7rem;">Mark complete</button></form>
                <?php elseif ($status === 'recommended'): ?>
                  <span class="text-xs font-bold" style="color:var(--ke-green)">Letter ready.</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
