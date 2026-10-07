<?php $videoId = \App\Models\CourseModule::youtubeId($c['youtube'] ?? ''); ?>
<?php require __DIR__ . '/_head.php'; ?>
<?php if ($videoId): ?>
  <div class="pb-video"><iframe src="https://www.youtube-nocookie.com/embed/<?= e($videoId) ?>?rel=0" title="<?= e($c['title'] ?? 'Video') ?>" allow="accelerometer; encrypted-media; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>
<?php else: ?>
  <p class="pb-text">Add a YouTube link to show a video here.</p>
<?php endif; ?>
