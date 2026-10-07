<?php require __DIR__ . '/_head.php'; ?>
<div class="pb-faq">
  <?php foreach ($c['items'] ?? [] as $item): if (($item['question'] ?? '') === '') { continue; } ?>
    <details class="pb-faq-item">
      <summary><?= e($item['question']) ?></summary>
      <p class="pb-text"><?= e($item['answer'] ?? '') ?></p>
    </details>
  <?php endforeach; ?>
</div>
