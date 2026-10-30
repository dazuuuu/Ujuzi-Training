<?php
$branches = $branches ?? [];
$tutorsByBranch = $tutorsByBranch ?? [];
$canAddTutors = !empty($canAddTutors);
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Organisation</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold">Branches</h1>
      <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">Create and manage branches for your account. A branch can be created independently without selecting an organisation.</p>
    </div>
    <a href="<?= url('/account/branches/create') ?>" class="btn-primary">Add branch</a>
  </section>

  <?php if (!$branches): ?>
    <div class="rounded-xl border border-dashed p-8 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No branches yet.</div>
  <?php else: ?>
    <div class="course-grid">
      <?php foreach ($branches as $branch): ?>
        <article class="course-card">
          <?php $headingTag = 'h2'; require __DIR__ . '/../partials/branch-card.php'; ?>
          <div class="flex items-center gap-2">
            <a href="<?= url('/account/branches/' . (int) $branch['id'] . '/edit') ?>" class="btn-secondary" style="padding:0.35rem 0.65rem;">Edit</a>
            <?php $drType = 'branch'; $drId = (int) $branch['id']; $drAction = url('/account/branches/' . (int) $branch['id'] . '/delete'); $drWhat = 'this branch'; require __DIR__ . '/../partials/delete-request.php'; ?>
          </div>
          <?php if ($canAddTutors): $branchTutors = $tutorsByBranch[(int) $branch['id']] ?? []; ?>
            <section id="branch-<?= (int) $branch['id'] ?>" class="mt-3 space-y-2 border-t pt-3" style="border-color:var(--ke-line)">
              <h3 class="text-xs font-black uppercase tracking-widest text-gray-800">Branch tutors</h3>
              <?php if ($branchTutors): ?>
                <ul class="space-y-1">
                  <?php foreach ($branchTutors as $tutor): ?>
                    <li class="flex flex-wrap items-center justify-between gap-2 text-sm">
                      <span class="min-w-0"><span class="font-bold"><?= e(trim($tutor['first_name'] . ' ' . $tutor['last_name'])) ?></span> <span class="block truncate text-xs font-semibold text-neutral-500"><?= e($tutor['email']) ?></span></span>
                      <form method="post" action="<?= url('/account/branches/' . (int) $branch['id'] . '/tutors/' . (int) $tutor['id'] . '/remove') ?>" onsubmit="return confirm('Remove this tutor from the branch? They stay a tutor of your organisation.');">
                        <?= csrfField() ?>
                        <button type="submit" class="btn-secondary" style="padding:0.25rem 0.5rem;font-size:0.7rem;">Remove</button>
                      </form>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <p class="text-xs font-semibold text-neutral-500">No tutors at this branch yet.</p>
              <?php endif; ?>
              <details>
                <summary class="cursor-pointer text-sm font-black" style="color:var(--ke-green)">+ Add tutor</summary>
                <form method="post" action="<?= url('/account/branches/' . (int) $branch['id'] . '/tutors') ?>" class="mt-2 space-y-2">
                  <?= csrfField() ?>
                  <input type="text" name="name" placeholder="Full name" class="w-full rounded-lg border border-neutral-300 p-2 text-sm">
                  <input type="email" name="email" required placeholder="Email" class="w-full rounded-lg border border-neutral-300 p-2 text-sm">
                  <input type="tel" name="phone" placeholder="Phone (optional)" class="w-full rounded-lg border border-neutral-300 p-2 text-sm">
                  <p class="text-xs font-semibold text-neutral-500">A new email gets a tutor account with a default password (they set their own on first login). An existing tutor's email adds them to this branch.</p>
                  <button type="submit" class="btn-primary">Add tutor</button>
                </form>
              </details>
            </section>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
