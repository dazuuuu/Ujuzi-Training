<?php
/** Role picker for LMS sign up, laid out by Super Admin → Pages → Sign up page. */
require __DIR__ . '/layout-header.php';
?>

<div class="mx-auto max-w-4xl">
  <?php require __DIR__ . '/../lms/partials/pb-styles.php'; ?>
  <?php $pbPage = 'signup'; require __DIR__ . '/../lms/partials/pb-sections.php'; ?>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
