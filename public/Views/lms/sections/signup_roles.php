<?php
$signupRoles = [
    [$c['student_title'] ?? 'Student', $c['student_text'] ?? '', '/account/register', 'graduation'],
    [$c['provider_title'] ?? 'Organisation providing attachment', $c['provider_text'] ?? '', '/account/register/attachment-trainer', 'briefcase'],
];
?>
<div class="pb-roles">
  <?php foreach ($signupRoles as [$roleTitle, $roleText, $rolePath, $roleIcon]): ?>
    <a href="<?= url($rolePath) ?>" class="pb-role app-press">
      <span class="app-tile-icon"><?= icon($roleIcon) ?></span>
      <h2><?= e($roleTitle) ?></h2>
      <p><?= e($roleText) ?></p>
      <span class="pb-role-go">Sign up →</span>
    </a>
  <?php endforeach; ?>
</div>
<?php if (($c['note'] ?? '') !== ''): ?><div class="pb-box"><?= nl2br(e($c['note'])) ?></div><?php endif; ?>
<p class="pb-note" style="text-align:center;opacity:1;font-weight:600">Already have an account? <a href="<?= url('/account/login') ?>" style="color:var(--pb-accent)">Sign in</a></p>
