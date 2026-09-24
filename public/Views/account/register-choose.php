<?php
/** Role picker for LMS sign up. Requires $roles in scope. */
require __DIR__ . '/layout-header.php';

$options = [
    [
        'slug' => 'student',
        'name' => 'Student',
        'description' => 'Enroll in courses, track your progress, and earn certificates.',
        'path' => '/account/register',
    ],
    [
        'slug' => 'trainer',
        'name' => 'Tutor',
        'description' => 'Create and teach courses once an organisation approves you.',
        'path' => '/account/register/trainer',
    ],
    [
        'slug' => 'attachment_trainer',
        'name' => 'Organisation providing Attachment',
        'description' => 'Register your organisation to host students for attachment after they finish a course.',
        'path' => '/account/register/attachment-trainer',
    ],
];
?>

<div class="max-w-3xl mx-auto">
  <div class="text-center mb-8">
    <span class="text-xs font-bold uppercase tracking-widest block mb-1" style="color:var(--ke-green)">Choose your role</span>
    <h1 class="font-serif-heading text-3xl font-bold text-[#0a0a0a]">Create your account</h1>
    <p class="text-sm text-neutral-500 mt-2">Sign up for the role that fits you. After you register you complete your profile and land on your dashboard.</p>
  </div>

  <div class="grid gap-4 sm:grid-cols-2">
    <?php foreach ($options as $role): ?>
      <a href="<?= url($role['path']) ?>" class="rounded-xl bg-white p-5 no-underline transition-shadow hover:shadow-md" style="border:2px solid var(--ke-green)">
        <p class="text-[11px] font-black uppercase tracking-widest" style="color:var(--ke-green)">Under organisation</p>
        <h2 class="mt-2 text-lg font-black text-black"><?= e($role['name']) ?></h2>
        <p class="mt-2 text-sm font-medium" style="color:var(--ke-muted)"><?= e($role['description']) ?></p>
        <p class="mt-4 text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Sign up as <?= e($role['name']) ?> &rarr;</p>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="mt-8 rounded-xl border border-neutral-200 bg-neutral-50 p-5 text-center">
    <p class="text-[11px] font-black uppercase tracking-widest text-neutral-600">Organisation admin</p>
    <p class="mt-2 text-sm font-semibold text-neutral-700">Organisation admins register only through an invite link sent by Super Admin.</p>
  </div>

  <p class="mt-6 text-center text-sm font-semibold">Already have an account? <a href="<?= url('/account/login') ?>" style="color:var(--ke-green)">Sign in</a></p>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
