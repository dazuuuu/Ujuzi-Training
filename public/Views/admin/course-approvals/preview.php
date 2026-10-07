<?php
/** A course, read-only, for Super Admin to check before approving. Requires $course and $modules. */
require __DIR__ . '/../layout-header.php';
$final = $course['final_exam_questions'] ?? [];
?>
<div class="max-w-4xl space-y-5">
  <a href="<?= url('/admin/course-approvals') ?>" class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">← Course approvals</a>
  <section class="rounded-xl border bg-white p-6 shadow-sm space-y-3" style="border-color:var(--ke-line)">
    <h1 class="font-serif-heading text-2xl font-bold"><?= e($course['title']) ?></h1>
    <p class="text-xs font-bold text-neutral-500"><?= e($course['organisation_name'] ?? '') ?> · <?= e($course['category_name'] ?? '') ?> · <?= (float) ($course['enrollment_fee_ksh'] ?? 0) > 0 ? 'Ksh ' . number_format((float) $course['enrollment_fee_ksh']) : 'Free' ?> · Certificate: <?= !empty($course['certificate_enabled']) ? 'yes' : 'no' ?> · Attachment: <?= !empty($course['requires_attachment']) ? 'required' : 'no' ?></p>
    <?php if (!empty($course['cover_image'])): ?><img src="<?= e(imageUrl($course['cover_image'])) ?>" alt="" class="max-h-64 rounded-lg object-cover"><?php endif; ?>
    <?php if (!empty($course['introduction_description'])): ?><div class="text-sm text-neutral-700"><?= nl2br(e($course['introduction_description'])) ?></div><?php endif; ?>
    <?php if (!empty($course['description'])): ?><div class="text-sm text-neutral-700"><?= nl2br(e($course['description'])) ?></div><?php endif; ?>
  </section>
  <section class="rounded-xl border bg-white p-6 shadow-sm space-y-3" style="border-color:var(--ke-line)">
    <h2 class="font-serif-heading text-lg font-bold">Modules (<?= count($modules) ?>)</h2>
    <?php foreach ($modules as $i => $m): ?>
      <div class="rounded-lg border p-3" style="border-color:var(--ke-line)">
        <p class="font-black"><?= $i + 1 ?>. <?= e($m['title']) ?></p>
        <p class="text-xs font-semibold text-neutral-500"><?= e(\App\Models\CourseModule::formatDuration((int) $m['duration_minutes'])) ?> · <?= $m['quiz_questions'] ? count($m['quiz_questions']) . ' quiz questions' : 'no quiz' ?> · <?= !empty($m['video_url']) || !empty($m['video_path']) ? 'video' : 'no video' ?></p>
        <?php if (!empty($m['summary'])): ?><p class="mt-1 text-sm text-neutral-700"><?= nl2br(e($m['summary'])) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if (!$modules): ?><p class="text-sm font-bold text-neutral-500">No modules yet.</p><?php endif; ?>
    <p class="text-sm font-bold">Final exam: <?= $final ? count($final) . ' questions, pass mark ' . (int) ($course['final_pass_percent'] ?? 80) . '%' : 'none' ?></p>
  </section>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
