<?php
/** Requires $number, $record (StudentLookup::find or null) and $formAction. */
require __DIR__ . '/../layout-header.php';
?>
<div class="max-w-5xl space-y-5">
  <?php require __DIR__ . '/../../partials/student-lookup-form.php'; ?>
  <?php if ($record) { require __DIR__ . '/../../partials/student-record.php'; } ?>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
