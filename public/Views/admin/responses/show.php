<?php
/** Requires $formRecord, $fields, $submissions. */
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Form replies</p>
      <h2 class="mt-2 text-2xl font-black text-black"><?= e($formRecord['title']) ?></h2>
      <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-700">
        <?= ($formRecord['purpose'] ?? 'profile') === 'course'
          ? 'Each card is a course created from this form.'
          : 'Each card is one person’s saved answers on this form.' ?>
      </p>
    </div>
    <a href="<?= url('/admin/responses') ?>" class="btn-secondary">All forms</a>
  </section>

  <?php if (!$submissions): ?>
    <div class="rounded-xl border border-dashed p-8 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">Nobody has filled this form yet.</div>
  <?php endif; ?>

  <?php foreach ($submissions as $submission): ?>
    <section class="rounded-xl border border-neutral-300 bg-white p-6 shadow-sm space-y-4">
      <div class="flex flex-col gap-1 border-b border-neutral-100 pb-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h3 class="font-serif-heading text-xl font-bold"><?= e($submission['user_name']) ?></h3>
          <p class="text-xs font-semibold text-neutral-600">
            <?= e($submission['role_name'] ?: 'User') ?>
            <?= $submission['organisation_name'] ? ' · ' . e($submission['organisation_name']) : '' ?>
            <?= $submission['email'] ? ' · ' . e($submission['email']) : '' ?>
            <?= $submission['phone'] ? ' · ' . e($submission['phone']) : '' ?>
          </p>
          <?php if (!empty($submission['meta'])): ?>
            <p class="mt-1 text-sm font-bold text-black"><?= e($submission['meta']) ?></p>
          <?php endif; ?>
        </div>
        <?php if (!empty($submission['submitted_at'])): ?>
          <p class="text-[11px] font-black uppercase tracking-wider text-neutral-500"><?= e(date('j M Y H:i', strtotime((string) $submission['submitted_at']) ?: time())) ?></p>
        <?php endif; ?>
      </div>
      <dl class="space-y-4">
        <?php foreach ($fields as $field): ?>
          <?php if (\App\Models\FormFieldTypes::isLayout($field['field_type'])) continue; ?>
          <div>
            <dt class="text-[11px] font-bold uppercase text-neutral-600"><?= e($field['label']) ?></dt>
            <dd class="mt-1 text-sm font-semibold text-black">
              <?php
                $raw = $submission['answers'][$field['field_key']] ?? '';
                if (($field['field_type'] ?? '') === 'branches' && is_array($raw) && $raw):
              ?>
                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                  <?php foreach ($raw as $branch):
                    if (!is_array($branch)) continue;
                    $branchCard = [
                        'title' => $branch['name'] ?? $branch['title'] ?? '',
                        'location' => $branch['location'] ?? '',
                        'cover_image' => '',
                        'details' => array_filter([
                            'Details' => $branch['details'] ?? '',
                            'Phone' => $branch['phone'] ?? '',
                            'Contact person' => $branch['contact'] ?? '',
                        ]) + (is_array($branch['extra'] ?? null) ? $branch['extra'] : []),
                    ];
                  ?>
                    <article class="rounded-lg border border-neutral-200 p-3">
                      <?php $headingTag = 'h4'; require dirname(__DIR__, 2) . '/account/partials/branch-card.php'; ?>
                    </article>
                  <?php endforeach; ?>
                </div>
              <?php elseif (\App\Models\FormFieldTypes::isFile($field['field_type']) && $raw): ?>
                <?php $files = is_array($raw) ? $raw : [$raw]; ?>
                <?php foreach ($files as $file): ?>
                  <p><a href="<?= e(imageUrl((string) $file)) ?>" target="_blank" style="color:var(--ke-green)"><?= e((string) $file) ?></a></p>
                <?php endforeach; ?>
              <?php else: ?>
                <?= nl2br(e(formatFormAnswer($field, $raw))) ?>
              <?php endif; ?>
            </dd>
          </div>
        <?php endforeach; ?>
      </dl>
    </section>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
