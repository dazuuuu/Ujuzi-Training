<?php
$limit = (int) ($c['limit'] ?? 8) ?: 8;
$list = ($c['source'] ?? 'featured') === 'featured' ? \App\Services\HomepageContent::featuredCourses($limit) : [];
if (!$list) {
    try {
        $list = array_slice(\App\Models\Course::publicListing(), 0, $limit);
    } catch (\Throwable $e) {
        $list = [];
    }
}
$cols = (int) ($c['columns'] ?? 4) ?: 4;
?>
<div class="pb-courses-head">
  <?php require __DIR__ . '/_head.php'; ?>
  <?php if (($c['button_label'] ?? '') !== '' && ($c['button_url'] ?? '') !== ''): ?>
    <a href="<?= $pbHref($c['button_url']) ?>" class="pb-link"><?= e($c['button_label']) ?> →</a>
  <?php endif; ?>
</div>
<?php if (!$list): ?>
  <p class="pb-text">Courses will show here once organisations publish them.</p>
<?php else: ?>
  <div class="pb-courses pb-size-<?= e($c['card_size'] ?? 'md') ?><?= ($c['layout'] ?? 'swipe') === 'swipe' ? ' is-swipe' : '' ?>" style="--pb-cols:<?= $cols ?>">
    <?php foreach ($list as $course):
      $cover = (string) ($course['cover_image'] ?? '');
      $hasCover = $cover !== '' && preg_match('/\.(jpe?g|png|webp|gif)$/i', $cover);
      $fee = (float) ($course['enrollment_fee_ksh'] ?? 0);
    ?>
      <a class="pb-course" href="<?= url('/account/courses/' . (int) $course['id']) ?>">
        <?php if ((strtotime((string) ($course['created_at'] ?? '')) ?: 0) > strtotime('-14 days')): ?><span class="pb-course-new">New</span><?php endif; ?>
        <?php if ($hasCover): ?>
          <img class="pb-course-cover" src="<?= e(imageUrl($cover)) ?>" alt="" loading="lazy">
        <?php else: ?>
          <div class="pb-course-fallback" aria-hidden="true"><?= e(mb_strtoupper(mb_substr(trim((string) $course['title']), 0, 1))) ?></div>
        <?php endif; ?>
        <div class="pb-course-body">
          <?php if (!empty($course['category_name'])): ?><div class="pb-course-cat"><?= e($course['category_name']) ?></div><?php endif; ?>
          <div class="pb-course-title"><?= e($course['title']) ?></div>
          <div class="pb-course-org"><?= e($course['organisation_name'] ?? '') ?></div>
          <div class="pb-course-foot"><span><?= $fee > 0 ? 'Ksh ' . number_format($fee) : 'Free' ?></span><span aria-hidden="true">→</span></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
