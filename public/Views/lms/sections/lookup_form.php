<?php
/** Public student lookup: the search box, then what was found. Uses $lookupNumber and $lookupRecord when the page sets them. */
$lookupNumber = $lookupNumber ?? '';
$lookupRecord = $lookupRecord ?? null;
?>
<div class="pb-lookup">
  <?php require __DIR__ . '/_head.php'; ?>
  <form method="get" action="<?= url('/student-lookup') ?>" class="pb-lookup-form" role="search">
    <input type="search" name="reg" value="<?= e($lookupNumber) ?>" required maxlength="60" placeholder="<?= e(($c['placeholder'] ?? '') ?: 'Reg. no., certificate no., ID, passport or birth cert. no.') ?>" aria-label="Registration, certificate, ID, passport or birth certificate number">
    <button type="submit" class="pb-btn pb-btn-main">Look up</button>
  </form>

  <?php if ($lookupNumber !== '' && !$lookupRecord): ?>
    <div class="pb-lookup-result is-bad" role="status">
      <strong>✗ Not found</strong>
      <p>No student, certificate, ID, passport or birth certificate has the number <b><?= e(strtoupper($lookupNumber)) ?></b>. Check it is typed exactly as on the certificate. If it still isn't found, the certificate was not issued by <?= e(appName()) ?>.</p>
    </div>
  <?php elseif ($lookupRecord): $lookupR = $lookupRecord; ?>
    <div class="pb-lookup-result is-good" role="status">
      <div class="pb-lookup-who">
        <?php if ($lookupR['photo']): ?><img src="<?= e(imageUrl($lookupR['photo'])) ?>" alt="" class="pb-lookup-photo"><?php else: ?><span class="pb-lookup-photo is-initial"><?= e(mb_strtoupper(mb_substr($lookupR['name'], 0, 1))) ?></span><?php endif; ?>
        <div>
          <span class="pb-lookup-badge">✓ Verified<?= $lookupR['kind'] === 'student' ? ' student' : ' certificate' ?></span>
          <h3><?= e($lookupR['name']) ?></h3>
          <p><?= $lookupR['kind'] === 'student' ? 'Reg. No.' : 'Certificate No.' ?> <b><?= e($lookupR['number']) ?></b><?= !empty($lookupR['registration_number']) ? ' · Reg. No. ' . e($lookupR['registration_number']) : '' ?><?= $lookupR['since'] !== '' ? ' · student since ' . e($lookupR['since']) : '' ?></p>
        </div>
      </div>
      <?php if ($lookupR['certificates']): ?>
        <h4>Certificates</h4>
        <ul><?php foreach ($lookupR['certificates'] as $cert): ?><li><b><?= e($cert['title']) ?></b><?= $cert['organisation'] !== '' ? ' — ' . e($cert['organisation']) : '' ?><?= $cert['date'] !== '' ? ' <span>· ' . e($cert['date']) . '</span>' : '' ?><?= ($cert['number'] ?? '') !== '' ? ' <span>· No. ' . e($cert['number']) . '</span>' : '' ?></li><?php endforeach; ?></ul>
      <?php elseif ($lookupR['kind'] === 'student'): ?>
        <p class="pb-lookup-none">No certificate issued yet.</p>
      <?php endif; ?>
      <?php if ($lookupR['studying']): ?>
        <h4>Currently studying</h4>
        <ul><?php foreach ($lookupR['studying'] as $study): ?><li><?= e($study['title']) ?><?= $study['organisation'] !== '' ? ' — ' . e($study['organisation']) : '' ?></li><?php endforeach; ?></ul>
      <?php endif; ?>
      <?php if ($lookupR['attachments']): ?>
        <h4>Attachment completed</h4>
        <ul><?php foreach ($lookupR['attachments'] as $att): ?><li><?= e($att['organisation']) ?><?= $att['course'] !== '' ? ' — for ' . e($att['course']) : '' ?></li><?php endforeach; ?></ul>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
