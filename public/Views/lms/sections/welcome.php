<?php
/** Picture beside a welcome text, numbers (some counted live) and a button. */
$liveCount = static function (string $what): string {
    $sql = [
        'students' => "SELECT COUNT(*) FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE r.slug = 'student'",
        'courses' => 'SELECT COUNT(*) FROM courses c WHERE c.is_published = 1' . \App\Models\Course::approvedSql(),
        'certificates' => 'SELECT COUNT(*) FROM course_completions',
    ][$what] ?? null;
    try {
        $n = $what === 'organisations' ? count(\App\Models\Organisation::providingCourses()) : (int) \App\Core\Database::connection()->query((string) $sql)->fetchColumn();
    } catch (\Throwable $e) {
        $n = 0;
    }
    return number_format($n) . ($n >= 10 ? '+' : '');
};
?>
<div class="pbx-welcome">
  <div class="pbx-welcome-pic">
    <?php if (($c['image'] ?? '') !== ''): ?><img src="<?= e(imageUrl($c['image'])) ?>" alt="" loading="lazy"><?php else: ?><div class="pbx-pic-empty"><?= icon('graduation', 'h-14 w-14') ?></div><?php endif; ?>
  </div>
  <div>
    <?php if (($c['label'] ?? '') !== ''): ?><p class="pbx-label"><?= e($c['label']) ?></p><?php endif; ?>
    <?php if (($c['title'] ?? '') !== ''): ?><h2 class="pbx-title"><?= e($c['title']) ?></h2><?php endif; ?>
    <?php if (($c['text'] ?? '') !== ''): ?><p class="pbx-text"><?= e($c['text']) ?></p><?php endif; ?>
    <?php if (!empty($c['stats'])): ?>
      <div class="pbx-stats">
        <?php foreach ($c['stats'] as $stat): if (($stat['value'] ?? '') === '') { continue; }
          $value = preg_replace_callback('/\{(students|courses|organisations|certificates)\}/', static fn(array $m): string => $liveCount($m[1]), (string) $stat['value']);
        ?>
          <div><strong><?= e($value) ?></strong><span><?= e($stat['label'] ?? '') ?></span></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if (($c['button_label'] ?? '') !== ''): ?><a href="<?= $pbHref($c['button_url'] ?? '/') ?>" class="pbx-btn is-dark"><?= e($c['button_label']) ?> <?php require __DIR__ . '/_arrow.php'; ?></a><?php endif; ?>
  </div>
</div>
