<?php
/** Role picker for LMS logins, laid out by Super Admin → Pages → Sign in page. Requires $roles in scope. */
require __DIR__ . '/layout-header.php';
?>

<div class="mx-auto max-w-4xl">
  <?php require __DIR__ . '/../lms/partials/pb-styles.php'; ?>
  <?php $pbPage = 'login'; require __DIR__ . '/../lms/partials/pb-sections.php'; ?>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
