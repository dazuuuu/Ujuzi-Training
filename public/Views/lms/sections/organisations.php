<?php
// Live: organisations on the platform right now.
$kind = $c['kind'] ?? 'courses';
$limit = (int) ($c['limit'] ?? 12) ?: 12;
try {
    if ($kind === 'attachment') {
        $orgs = \App\Core\Database::connection()->query(
            "SELECT DISTINCT o.* FROM organisations o INNER JOIN users u ON u.organisation_id = o.id
             INNER JOIN roles r ON r.id = u.role_id AND r.slug = 'attachment_trainer'
             WHERE o.is_active = 1 AND u.is_active = 1 ORDER BY o.name"
        )->fetchAll();
    } else {
        $orgs = \App\Models\Organisation::providingCourses();
    }
} catch (\Throwable $e) {
    $orgs = [];
}
$orgs = array_slice($orgs, 0, $limit);
?>
<?php require __DIR__ . '/_head.php'; ?>
<?php if (!$orgs): ?>
  <p class="pb-text">Organisations appear here as they join.</p>
<?php else: ?>
  <div class="pb-grid" style="--pb-cols:<?= (int) ($c['columns'] ?? 3) ?: 3 ?>">
    <?php foreach ($orgs as $org): ?>
      <div class="pb-card" style="align-items:flex-start">
        <?php if (!empty($org['logo_path'])): ?>
          <img src="<?= e(imageUrl($org['logo_path'])) ?>" alt="" style="height:44px;width:auto;max-width:140px;object-fit:contain">
        <?php else: ?>
          <span class="pb-card-icon" aria-hidden="true" style="font-weight:900;color:var(--pb-accent)"><?= e(mb_strtoupper(mb_substr(trim((string) $org['name']), 0, 1))) ?></span>
        <?php endif; ?>
        <h3><?= e($org['name']) ?></h3>
        <?php if (!empty($org['location'])): ?><p>📍 <?= e($org['location']) ?></p><?php endif; ?>
        <?php if (!empty($org['description'])): ?><p><?= e(mb_strimwidth((string) $org['description'], 0, 160, '…')) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
