<?php
// Live: counted from the database every time the page loads.
$show = [
    'students' => !empty($c['show_students']), 'courses' => !empty($c['show_courses']),
    'organisations' => !empty($c['show_organisations']), 'certificates' => !empty($c['show_certificates']),
];
if (!array_filter($show)) {
    $show = array_fill_keys(array_keys($show), true);
}
$count = static function (string $sql): int {
    try { return (int) \App\Core\Database::connection()->query($sql)->fetchColumn(); } catch (\Throwable $e) { return 0; }
};
$numbers = [];
if ($show['students']) { $numbers[] = [$count("SELECT COUNT(*) FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE r.slug = 'student'"), 'Students']; }
if ($show['courses']) { $numbers[] = [$count('SELECT COUNT(*) FROM courses c WHERE c.is_published = 1' . \App\Models\Course::approvedSql()), 'Courses']; }
if ($show['organisations']) { $numbers[] = [count(\App\Models\Organisation::providingCourses()), 'Organisations']; }
if ($show['certificates']) { $numbers[] = [$count('SELECT COUNT(*) FROM course_completions'), 'Courses completed']; }
?>
<?php if (($c['title'] ?? '') !== ''): ?><h2 class="pb-title" style="margin-bottom:1em"><?= e($c['title']) ?></h2><?php endif; ?>
<div class="pb-stats">
  <?php foreach ($numbers as [$n, $label]): ?>
    <div class="pb-stat"><strong><?= number_format($n) ?></strong><span><?= e($label) ?></span></div>
  <?php endforeach; ?>
</div>
