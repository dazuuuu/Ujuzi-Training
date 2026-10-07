<?php
/** Requires $application (detailed), $fees, $messages, $actions (action => label) and $backUrl. */
use App\Models\AttachmentApplication;

require __DIR__ . '/../layout-header.php';

$status = (string) $application['status'];
$studentName = trim($application['first_name'] . ' ' . $application['last_name']) ?: (string) $application['email'];
$statusColor = in_array($status, ['rejected', 'paused'], true) ? 'var(--ke-red)' : 'var(--ke-green)';
$when = static fn(?string $at): string => $at ? date('j M Y', strtotime($at)) : '—';
?>

<div class="space-y-6">
  <a href="<?= url($backUrl) ?>" class="text-xs font-bold uppercase tracking-widest" style="color:var(--ke-green)">← All attachees</a>

  <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <p class="text-xs font-black uppercase tracking-widest text-neutral-500">Attachment request</p>
        <h1 class="mt-1 font-serif-heading text-3xl font-bold"><?= e($studentName) ?></h1>
        <p class="mt-1 text-sm font-semibold text-neutral-600">
          <?php if (!empty($application['registration_number'])): ?><span class="font-black"><?= e($application['registration_number']) ?></span> · <?php endif; ?>
          <?= e($application['email'] ?: '') ?><?= !empty($application['phone']) ? ' · ' . e($application['phone']) : '' ?>
        </p>
      </div>
      <span class="rounded-full border px-3 py-1 text-xs font-black uppercase" style="border-color:<?= $statusColor ?>;color:<?= $statusColor ?>">
        <?= e(AttachmentApplication::statusLabel($status)) ?>
      </span>
    </div>
    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
      <div><dt class="text-[11px] font-black uppercase text-neutral-500">Course</dt><dd class="font-semibold"><?= e($application['course_title'] ?? '—') ?><?= !empty($application['course_organisation_name']) ? ' · ' . e($application['course_organisation_name']) : '' ?><?= !empty($application['student_branch_title']) ? ' · ' . e($application['student_branch_title']) . ' branch' : '' ?></dd></div>
      <div><dt class="text-[11px] font-black uppercase text-neutral-500">Phone</dt><dd class="font-semibold"><?php if (!empty($application['phone'])): ?><a class="underline" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $application['phone'])) ?>"><?= e($application['phone']) ?></a><?php else: ?>—<?php endif; ?></dd></div>
      <div><dt class="text-[11px] font-black uppercase text-neutral-500">Course category</dt><dd class="font-semibold"><?= e($application['category_name'] ?? '—') ?><?= !empty($application['category_organisation_name']) ? ' · ' . e($application['category_organisation_name']) : '' ?></dd></div>
      <div><dt class="text-[11px] font-black uppercase text-neutral-500">Branch</dt><dd class="font-semibold"><?= e($application['branch_title'] ?? 'Organisation (no branch)') ?></dd></div>
      <div><dt class="text-[11px] font-black uppercase text-neutral-500">Requested</dt><dd class="font-semibold"><?= e($when($application['selected_at'] ?? null)) ?></dd></div>
      <div><dt class="text-[11px] font-black uppercase text-neutral-500"><?= $status === 'recommended' ? 'Letter last sent' : 'Accepted' ?></dt><dd class="font-semibold"><?= e($when($status === 'recommended' ? ($application['letter_sent_at'] ?? $application['recommended_at']) : ($application['accepted_at'] ?? null))) ?></dd></div>
    </dl>
  </section>

  <div class="grid gap-6 lg:grid-cols-3">
    <section class="lg:col-span-1 rounded-xl border border-neutral-200 bg-white p-5 shadow-sm space-y-3">
      <h2 class="font-serif-heading text-lg font-bold">Fees &amp; course progress</h2>
      <?php if (!empty($requestFees)): ?>
        <div class="rounded-lg border p-3" style="border-color:var(--ke-line)">
          <p class="text-[11px] font-black uppercase text-neutral-500">This request's course</p>
          <?php require __DIR__ . '/../partials/request-fees.php'; ?>
        </div>
      <?php endif; ?>
      <?php require __DIR__ . '/../partials/student-standing.php'; ?>
    </section>

    <section class="lg:col-span-2 space-y-4">
      <h2 class="font-serif-heading text-lg font-bold">Notes with the student</h2>
      <?php $viewerSide = 'reviewer'; require __DIR__ . '/../partials/attachment-thread.php'; ?>

      <?php if ($actions || !in_array($status, ['rejected'], true)): ?>
        <form method="post" action="<?= url('/account/attachment-requests/' . (int) $application['id'] . '/respond') ?>" class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm space-y-3">
          <?= csrfField() ?>
          <label class="block text-xs font-bold uppercase text-neutral-600" for="review-body">Note to the student</label>
          <textarea id="review-body" name="body" rows="4" maxlength="4000" class="w-full rounded-lg border border-neutral-300 p-3 text-sm"
                    placeholder="e.g. Please confirm you have completed the safety module and bring your ID on your first day."></textarea>

          <?php if ($actions): ?>
            <fieldset>
              <legend class="text-xs font-bold uppercase text-neutral-600">Status</legend>
              <div class="mt-2 flex flex-wrap gap-2">
                <label class="flex items-center gap-2 rounded-lg border border-neutral-300 px-3 py-2 text-sm font-semibold">
                  <input type="radio" name="action" value="" checked> Keep as <?= e(AttachmentApplication::statusLabel($status)) ?>
                </label>
                <?php foreach ($actions as $value => $label):
                  $blocked = false; // with a balance, the server refuses and shows the amount owed
                ?>
                  <label class="flex items-center gap-2 rounded-lg border border-neutral-300 px-3 py-2 text-sm font-semibold <?= $blocked ? 'opacity-50' : '' ?>" <?= $blocked ? 'title="Clear the course-fee balance first"' : '' ?>>
                    <input type="radio" name="action" value="<?= e($value) ?>" <?= $blocked ? 'disabled' : '' ?>> <?= e($label) ?>
                  </label>
                <?php endforeach; ?>
              </div>
              <p class="mt-2 text-xs font-semibold text-neutral-500">You can send a note without changing the status. Put on hold while you wait for the student — for example, to confirm they meet your requirements.</p>
            </fieldset>
          <?php endif; ?>

          <button type="submit" class="btn-primary">Send</button>
        </form>
      <?php endif; ?>

      <?php if ($status === 'recommended'): ?>
        <form method="post" action="<?= url('/account/attachment-requests/' . (int) $application['id'] . '/resend-letter') ?>" class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm space-y-3" onsubmit="return confirm('Send the recommendation letter to this student again?');">
          <?= csrfField() ?>
          <h3 class="font-bold text-gray-800">Resend recommendation letter</h3>
          <p class="text-xs font-semibold text-neutral-500">The student gets the letter link in their portal and, when email is set up, by email.</p>
          <textarea name="body" rows="2" maxlength="4000" class="w-full rounded-lg border border-neutral-300 p-3 text-sm" placeholder="Optional message to go with it"></textarea>
          <button type="submit" class="btn-secondary">Resend letter</button>
        </form>
      <?php endif; ?>
    </section>
  </div>

  <?php
    $assessment = $assessment ?? null;
    $sheetRows = $assessment['items'] ?? array_map(static fn(array $c): array => $c + ['score' => null, 'comment' => ''], $criteria ?? []);
    $sheetDone = !empty($assessment['assessed_at']);
  ?>
  <section id="assessment" class="rounded-xl border bg-white p-5 shadow-sm space-y-4" style="border-color:<?= $sheetDone ? 'var(--ke-line)' : '#f59e0b' ?>;scroll-margin-top:80px">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="font-serif-heading text-lg font-bold">Assessment sheet</h2>
        <p class="text-sm font-medium text-neutral-600">Mark the attachee against each criterion. It must be marked before you complete the attachment, and it is sent to the organisation that runs <?= e($application['course_title'] ?? 'the course') ?> — their grades count with the final exam.</p>
      </div>
      <?php if ($sheetDone): ?>
        <span class="rounded-full px-3 py-1 text-xs font-black" style="background:#ecf7f0;color:var(--ke-green)"><?= rtrim(rtrim(number_format((float) $assessment['percent'], 1), '0'), '.') ?>% · <?= !empty($assessment['shared_at']) ? 'sent to the course organisation' : 'not sent yet' ?></span>
      <?php else: ?>
        <span class="rounded-full px-3 py-1 text-xs font-black" style="background:#fffbeb;color:#92400e">Not marked yet</span>
      <?php endif; ?>
    </div>
    <form method="post" action="<?= url('/account/attachment-requests/' . (int) $application['id'] . '/assessment') ?>" class="space-y-3" id="assessment-form">
      <?= csrfField() ?>
      <div class="overflow-x-auto rounded-lg border" style="border-color:var(--ke-line)">
        <table class="w-full text-left text-sm">
          <thead class="bg-neutral-50 text-[11px] font-black uppercase text-neutral-500"><tr><th class="p-2">Criterion</th><th class="p-2 w-20">Out of</th><th class="p-2 w-24">Mark</th><th class="p-2">Comment</th><th class="p-2 w-8"></th></tr></thead>
          <tbody id="assessment-rows">
            <?php foreach ($sheetRows as $i => $row): ?>
              <tr>
                <td class="p-1"><input name="items[<?= $i ?>][criterion]" value="<?= e($row['criterion']) ?>" class="w-full rounded border border-neutral-300 p-1.5 text-sm"></td>
                <td class="p-1"><input type="number" min="1" step="any" name="items[<?= $i ?>][max]" value="<?= e((string) (float) $row['max']) ?>" class="w-full rounded border border-neutral-300 p-1.5 text-sm" data-max></td>
                <td class="p-1"><input type="number" min="0" step="any" name="items[<?= $i ?>][score]" value="<?= $row['score'] === null ? '' : e((string) (float) $row['score']) ?>" class="w-full rounded border border-neutral-300 p-1.5 text-sm" data-score></td>
                <td class="p-1"><input name="items[<?= $i ?>][comment]" value="<?= e($row['comment'] ?? '') ?>" class="w-full rounded border border-neutral-300 p-1.5 text-sm"></td>
                <td class="p-1"><button type="button" class="text-neutral-400 hover:text-red-600" data-remove aria-label="Remove">✕</button></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <button type="button" class="btn-secondary" id="assessment-add" style="padding:.3rem .7rem;font-size:.75rem">+ Add criterion</button>
        <button type="submit" name="generate" value="1" class="btn-secondary" style="padding:.3rem .7rem;font-size:.75rem" onclick="return confirm('Replace the criteria with the standard sheet?');">Generate standard criteria</button>
        <span class="ml-auto text-sm font-black" id="assessment-total"></span>
      </div>
      <label class="block text-[11px] font-bold uppercase text-neutral-600">Overall remarks
        <textarea name="remarks" rows="3" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"><?= e($assessment['remarks'] ?? '') ?></textarea></label>
      <button type="submit" class="btn-primary">Save assessment</button>
    </form>
    <script>
    (function () {
      var body = document.getElementById('assessment-rows'), total = document.getElementById('assessment-total'), next = body.rows.length;
      function sum() {
        var got = 0, max = 0;
        body.querySelectorAll('tr').forEach(function (tr) { max += +(tr.querySelector('[data-max]').value || 0); got += +(tr.querySelector('[data-score]').value || 0); });
        total.textContent = 'Total ' + got + ' / ' + max + (max ? ' (' + Math.round(got * 1000 / max) / 10 + '%)' : '');
      }
      body.addEventListener('input', sum);
      body.addEventListener('click', function (e) { if (e.target.closest('[data-remove]')) { e.target.closest('tr').remove(); sum(); } });
      document.getElementById('assessment-add').addEventListener('click', function () {
        var i = next++, tr = document.createElement('tr');
        tr.innerHTML = '<td class="p-1"><input name="items[' + i + '][criterion]" class="w-full rounded border border-neutral-300 p-1.5 text-sm" placeholder="e.g. Customer care"></td>'
          + '<td class="p-1"><input type="number" min="1" step="any" name="items[' + i + '][max]" value="10" class="w-full rounded border border-neutral-300 p-1.5 text-sm" data-max></td>'
          + '<td class="p-1"><input type="number" min="0" step="any" name="items[' + i + '][score]" class="w-full rounded border border-neutral-300 p-1.5 text-sm" data-score></td>'
          + '<td class="p-1"><input name="items[' + i + '][comment]" class="w-full rounded border border-neutral-300 p-1.5 text-sm"></td>'
          + '<td class="p-1"><button type="button" class="text-neutral-400 hover:text-red-600" data-remove aria-label="Remove">✕</button></td>';
        body.appendChild(tr); tr.querySelector('input').focus(); sum();
      });
      sum();
    })();
    </script>
  </section>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
