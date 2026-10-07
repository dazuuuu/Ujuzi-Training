<?php
/** Waiting for Super Admin: new courses and branch deletions. Requires $courses and $deletions. */
require __DIR__ . '/../layout-header.php';
?>
<div class="max-w-5xl space-y-5">
  <section>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Courses</p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold">Approvals</h1>
    <p class="mt-1 text-sm font-medium text-neutral-600">Every new course waits here. Students only see a course once you approve it. Send one back with a note to tell the tutor what to change.</p>
  </section>
  <?php if (!$courses): ?>
    <p class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No courses are waiting. You're all caught up.</p>
  <?php endif; ?>
  <?php foreach ($courses as $course): $fee = (float) ($course['enrollment_fee_ksh'] ?? 0); ?>
    <article class="rounded-xl border bg-white p-5 shadow-sm space-y-3" style="border-color:var(--ke-line)">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
          <h2 class="text-lg font-black text-gray-900"><?= e($course['title']) ?></h2>
          <p class="text-xs font-bold text-neutral-500"><?= e($course['organisation_name']) ?> · Tutor: <?= e(trim($course['first_name'] . ' ' . $course['last_name']) ?: $course['email']) ?> · <?= $fee > 0 ? 'Ksh ' . number_format($fee) : 'Free' ?> · submitted <?= e(date('j M Y', strtotime((string) $course['created_at']))) ?></p>
        </div>
        <a href="<?= url('/admin/course-approvals/' . (int) $course['id'] . '/preview') ?>" class="btn-secondary" target="_blank" rel="noopener">Preview</a>
      </div>
      <?php if (trim((string) ($course['description'] ?? '')) !== ''): ?>
        <p class="text-sm text-neutral-700"><?= nl2br(e(mb_strimwidth((string) $course['description'], 0, 600, '…'))) ?></p>
      <?php endif; ?>
      <form method="post" action="<?= url('/admin/course-approvals/' . (int) $course['id']) ?>" class="flex flex-wrap items-end gap-2">
        <?= csrfField() ?>
        <label class="min-w-0 flex-1 text-[11px] font-bold uppercase text-neutral-600">Note to the tutor (needed when sending back)
          <input type="text" name="note" maxlength="500" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case">
        </label>
        <button type="submit" name="decision" value="approved" class="btn-primary">Approve</button>
        <button type="submit" name="decision" value="rejected" class="btn-danger" onclick="var n=this.form.note; if(!n.value.trim()){n.focus();alert('Add a note so the tutor knows what to change.');return false;}">Send back</button>
      </form>
    </article>
  <?php endforeach; ?>

  <section class="space-y-3 pt-4" id="branch-deletions">
    <h2 class="font-serif-heading text-xl font-bold">Branch deletions (<?= count($deletions) ?>)</h2>
    <p class="text-sm font-medium text-neutral-600">Organisation heads ask to delete a branch; it is only deleted once you approve.</p>
    <?php if (!$deletions): ?><p class="rounded-xl border border-dashed p-5 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No branch deletions waiting.</p><?php endif; ?>
    <?php foreach ($deletions as $d): ?>
      <article class="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-white p-4 shadow-sm" style="border-color:var(--ke-line)">
        <div class="min-w-0">
          <p class="font-black"><?= e($d['branch_title']) ?><?= !empty($d['branch_location']) ? ' · ' . e($d['branch_location']) : '' ?></p>
          <p class="text-xs font-semibold text-neutral-500"><?= e($d['organisation_name'] ?? '') ?> · asked by <?= e(trim($d['first_name'] . ' ' . $d['last_name']) ?: $d['email']) ?> on <?= e(date('j M Y', strtotime((string) $d['created_at']))) ?> (organisation head approved)</p>
          <?php if (!empty($d['reason'])): ?><p class="mt-1 text-sm text-neutral-700">“<?= e($d['reason']) ?>”</p><?php endif; ?>
        </div>
        <form method="post" action="<?= url('/admin/branch-deletions/' . (int) $d['id']) ?>" class="flex gap-2">
          <?= csrfField() ?>
          <button type="submit" name="decision" value="approved" class="btn-danger" onclick="return confirm('Delete this branch for good?');">Approve deletion</button>
          <button type="submit" name="decision" value="rejected" class="btn-secondary">Decline</button>
        </form>
      </article>
    <?php endforeach; ?>
  </section>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
