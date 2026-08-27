<?php
/** Requires $template, $isPdf, $isImage in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="max-w-3xl space-y-6">
  <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
    <div>
      <h2 class="font-serif-heading text-lg font-bold">Certificate template</h2>
      <p class="mt-1 text-sm font-medium text-neutral-600">Upload a PDF or image of how the certificate should look. Student certificates use this design and write the learner’s name plus every skill (completed course) they have earned onto it. One certificate lists all skills.</p>
    </div>

    <?php if ($template): ?>
      <div class="rounded-lg border p-4 space-y-3" style="border-color:var(--ke-line)">
        <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Current template</p>
        <?php if (!empty($isImage)): ?>
          <img src="<?= e(imageUrl($template)) ?>" alt="Certificate template" class="max-h-80 w-full rounded object-contain border border-neutral-200 bg-neutral-50" />
        <?php else: ?>
          <iframe src="<?= e(imageUrl($template)) ?>" title="Certificate template" class="h-80 w-full rounded border border-neutral-200"></iframe>
        <?php endif; ?>
        <p class="text-xs font-semibold text-neutral-600">Leave space in the middle of the design for the name and skills list.</p>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= url('/admin/certificate') ?>" enctype="multipart/form-data" class="space-y-4">
      <?= csrfField() ?>
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">PDF or image</label>
        <input type="file" name="template" accept=".pdf,application/pdf,image/*" required class="mt-2 block w-full text-sm" />
      </div>
      <button type="submit" class="btn-primary">Save template</button>
    </form>

    <?php if ($template): ?>
      <form method="post" action="<?= url('/admin/certificate') ?>" onsubmit="return confirm('Remove the certificate template?');">
        <?= csrfField() ?>
        <input type="hidden" name="remove_template" value="1" />
        <button type="submit" class="btn-danger">Remove template</button>
      </form>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
