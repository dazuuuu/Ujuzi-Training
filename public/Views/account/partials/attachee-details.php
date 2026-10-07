<?php
/** Contact, course and branch lines of an attachee card. Requires $application. */
$courseMeta = array_filter([
    $application['category_name'] ?? '',
    $application['course_organisation_name'] ?? ($application['category_organisation_name'] ?? ''),
    !empty($application['student_branch_title']) ? 'their branch: ' . $application['student_branch_title'] : '',
]);
?>
<dl class="attachee-facts">
  <div>
    <dt>Contact</dt>
    <dd>
      <?php if (!empty($application['phone'])): ?><a class="font-bold underline" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $application['phone'])) ?>"><?= e($application['phone']) ?></a><?php endif; ?>
      <?php if (!empty($application['email'])): ?><a class="block break-all underline" href="mailto:<?= e($application['email']) ?>"><?= e($application['email']) ?></a><?php endif; ?>
      <?= empty($application['phone']) && empty($application['email']) ? '—' : '' ?>
    </dd>
  </div>
  <div>
    <dt>Course</dt>
    <dd><span class="font-bold"><?= e($application['course_title'] ?? ($application['category_name'] ?? '—')) ?></span><?php if ($courseMeta): ?><span class="block text-[11px] text-neutral-500"><?= e(implode(' · ', $courseMeta)) ?></span><?php endif; ?></dd>
  </div>
  <div><dt>Attachment branch</dt><dd><?= e($application['branch_title'] ?? '—') ?></dd></div>
</dl>
<?php $requestFees = $application['request_fees'] ?? null; require __DIR__ . '/request-fees.php'; ?>
