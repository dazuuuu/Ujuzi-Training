<?php
/** Requires $certificateTemplate/$certificateIsPdf/$certificateIsImage and $letterTemplate/$letterIsPdf/$letterIsImage in scope. */
require __DIR__ . '/../layout-header.php';

$card = static function (string $type, string $title, string $help, ?string $template, bool $isPdf, bool $isImage): void {
    ?>
    <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
      <div>
        <h2 class="font-serif-heading text-lg font-bold"><?= e($title) ?></h2>
        <p class="mt-1 text-sm font-medium text-neutral-600"><?= e($help) ?></p>
      </div>

      <?php if ($template): ?>
        <div class="rounded-lg border p-4 space-y-3" style="border-color:var(--ke-line)">
          <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Current template</p>
          <?php if ($isImage): ?>
            <img src="<?= e(imageUrl($template)) ?>" alt="<?= e($title) ?>" class="max-h-80 w-full rounded object-contain border border-neutral-200 bg-neutral-50" />
          <?php else: ?>
            <iframe src="<?= e(imageUrl($template)) ?>" title="<?= e($title) ?>" class="h-80 w-full rounded border border-neutral-200"></iframe>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <form method="post" action="<?= url('/admin/documents/' . $type) ?>" enctype="multipart/form-data" class="space-y-4">
        <?= csrfField() ?>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600">PDF or image</label>
          <input type="file" name="template" accept=".pdf,application/pdf,image/*" required class="mt-2 block w-full text-sm" />
        </div>
        <button type="submit" class="btn-primary">Save template</button>
      </form>

      <?php if ($template): ?>
        <form method="post" action="<?= url('/admin/documents/' . $type) ?>" onsubmit="return confirm('Remove this template?');">
          <?= csrfField() ?>
          <input type="hidden" name="remove_template" value="1" />
          <button type="submit" class="btn-danger">Remove template</button>
        </form>
      <?php endif; ?>
    </section>
    <?php
};
?>

<div class="max-w-3xl space-y-6">
  <section>
    <h1 class="font-serif-heading text-2xl font-bold">Documents</h1>
    <p class="mt-1 text-sm font-medium text-neutral-600">Upload the layouts used for auto-generated student documents — the certificate and the attachment recommendation letter.</p>
  </section>

  <?php $card(
      'certificate',
      'Certificate template',
      'Student certificates use this design and write the learner’s name plus every skill they have earned onto it.',
      $certificateTemplate,
      $certificateIsPdf,
      $certificateIsImage
  ); ?>

  <?php $card(
      'recommendation_letter',
      'Recommendation letter template',
      'Generated automatically once an attachment provider marks a student’s attachment as recommended/completed.',
      $letterTemplate,
      $letterIsPdf,
      $letterIsImage
  ); ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
