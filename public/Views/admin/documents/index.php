<?php
/** Requires $certificateTemplate/$certificateIsPdf/$certificateIsImage (for $orgId, 0 = platform-wide), $organisations, $orgName and $letterTemplate/$letterIsPdf/$letterIsImage. */
require __DIR__ . '/../layout-header.php';

$card = static function (string $type, string $title, string $help, ?string $template, bool $isPdf, bool $isImage, ?int $orgId = null, ?string $note = null): void {
    $edType = $type; $edTitle = $title; $edHelp = $help; $edTemplate = $template; $edIsPdf = $isPdf; $edNote = $note;
    $edLayout = \App\Services\DocumentLayout::get($type, $orgId);
    $qs = $orgId ? '?org=' . $orgId : '';
    $edUploadUrl = url('/admin/documents/' . $type) . $qs;
    $edLayoutUrl = url('/admin/documents/' . $type . '/layout') . $qs;
    require __DIR__ . '/../../partials/document-editor.php';
};
?>

<div class="max-w-5xl space-y-6">
  <section>
    <h1 class="font-serif-heading text-2xl font-bold">Documents</h1>
    <p class="mt-1 text-sm font-medium text-neutral-600">Upload the layouts used for auto-generated student documents — the certificate and the attachment recommendation letter.</p>
  </section>

  <form method="get" action="<?= url('/admin/documents') ?>" class="flex flex-wrap items-end gap-2 rounded-xl border border-neutral-200 bg-white p-4 shadow-sm">
    <label class="text-xs font-bold uppercase text-neutral-600">Certificate design for
      <select name="org" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm normal-case" onchange="this.form.submit()">
        <option value="">Platform-wide (used when an organisation has no design of its own)</option>
        <?php foreach ($organisations as $org): ?>
          <option value="<?= (int) $org['id'] ?>" <?= (int) $org['id'] === (int) $orgId ? 'selected' : '' ?>><?= e($org['name']) ?><?= \App\Services\CertificateService::ownTemplatePath((int) $org['id']) ? ' · own design' : '' ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <noscript><button type="submit" class="btn-secondary">Open</button></noscript>
  </form>

  <?php $card(
      'certificate',
      $orgId ? 'Certificate template — ' . $orgName : 'Certificate template (platform-wide)',
      'Upload the certificate design (PDF or image), then drag the student\'s name, course, organisation, registration number and date to where they go.',
      $certificateTemplate,
      $certificateIsPdf,
      $certificateIsImage,
      $orgId ?: null,
      $orgId && !$certificateTemplate ? 'This organisation has no design of its own yet, so its certificates use the platform-wide design. Upload one to give it its own.' : null
  ); ?>

  <?php $card(
      'recommendation_letter',
      'Recommendation letter template',
      'Upload the letter design (PDF or image), then drag the student\'s name, course, the organisation and branch they were attached at, registration number and date into place. Issued when a branch marks the attachment completed.',
      $letterTemplate,
      $letterIsPdf,
      $letterIsImage
  ); ?>
</div>


<?php require __DIR__ . '/../layout-footer.php'; ?>
