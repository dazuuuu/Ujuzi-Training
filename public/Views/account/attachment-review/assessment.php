<?php
/** The assessment on its own page. Requires $application, $assessment, $form, $canFill, $backUrl. */
require __DIR__ . '/../layout-header.php';
$studentName = trim($application['first_name'] . ' ' . $application['last_name']) ?: (string) $application['email'];
?>
<div class="space-y-4">
  <div class="flex flex-wrap items-center justify-between gap-2">
    <a href="<?= url($backUrl) ?>" class="text-xs font-bold uppercase tracking-widest" style="color:var(--ke-green)">← All attachees</a>
    <span class="flex gap-2 print:hidden">
      <button type="button" class="btn-secondary" onclick="window.print()">Print</button>
      <a href="<?= url('/account/attachment-requests/' . (int) $application['id']) ?>" class="btn-secondary">Open request</a>
    </span>
  </div>
  <section>
    <h1 class="font-serif-heading text-2xl font-bold"><?= e($studentName) ?></h1>
    <p class="text-sm font-semibold text-neutral-600"><?= e(implode(' · ', array_filter([$application['registration_number'] ?? '', $application['course_title'] ?? '', $application['branch_title'] ?? '', $application['phone'] ?? '']))) ?></p>
  </section>
  <?php $sheetPage = 'assessment'; require __DIR__ . '/../partials/assessment-sheet.php'; ?>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
