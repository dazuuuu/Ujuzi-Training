<?php
/**
 * The student's certificates: each finished course has its own. Without a
 * chosen course ($singleCourse null) this lists them by course name; with
 * one, it shows that course's certificate. Requires $completedCourses.
 */
$payload = $payload ?? [];
$skills = $payload['skills'] ?? [];
$template = $payload['template'] ?? null;
$isPdf = !empty($payload['is_pdf']);
$isImage = !empty($payload['is_image']);
require __DIR__ . '/../layout-header.php';
?>

<?php if (empty($singleCourse)): ?>
  <div class="mx-auto max-w-2xl space-y-4">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Certificates</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold">My certificates</h1>
      <p class="mt-1 text-sm font-medium text-neutral-600">Each course you finish has its own certificate. Open one to print or save it as a PDF.</p>
    </div>
    <ul class="divide-y rounded-xl border bg-white" style="border-color:var(--ke-line)">
      <?php foreach ($completedCourses as $done): ?>
        <li>
          <a href="<?= url('/account/certificate?course=' . (int) $done['id']) ?>" class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-neutral-50">
            <span class="min-w-0 text-sm font-bold text-gray-900"><?= e($done['title']) ?></span>
            <span class="shrink-0 text-xs font-black" style="color:var(--ke-green)">View certificate →</span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php require __DIR__ . '/../layout-footer.php'; return; ?>
<?php endif; ?>

<div class="space-y-4 print:hidden">
  <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Certificate</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold"><?= e($singleCourse['title']) ?></h1>
      <p class="mt-1 text-sm font-medium text-neutral-600"><?= !empty($issuedTo) ? 'Issued to ' . e(userFullName($issuedTo)) . '.' : 'Your certificate for this course.' ?> Print or save as PDF to keep a copy that matches the official design.</p>
      <?php if (!empty($issuedTo)): ?>
        <a href="<?= url('/account/certificates') ?>" class="mt-2 inline-block text-xs font-black underline" style="color:var(--ke-green)">← All certificates</a>
      <?php elseif (count($completedCourses ?? []) > 1): ?>
        <a href="<?= url('/account/certificate') ?>" class="mt-2 inline-block text-xs font-black underline" style="color:var(--ke-green)">← All my certificates</a>
      <?php endif; ?>
    </div>
    <button type="button" class="btn-primary" onclick="window.print()">Download / print (save as PDF)</button>
  </div>
</div>

<?php require __DIR__ . '/../../partials/certificate-render.php'; ?>

<?php require __DIR__ . '/../layout-footer.php'; ?>
