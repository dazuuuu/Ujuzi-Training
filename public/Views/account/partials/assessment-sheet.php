<?php
/**
 * An attachee's assessment: the attachment organisation's own form, the
 * daily log and the closing remark. Editable when $canFill, otherwise shown
 * read-only (the organisation viewing a branch's attachee).
 * Requires $application, $assessment (or null), $form (AttachmentAssessment::formFor), $canFill.
 * Optional $sheetPage = 'assessment' when on the assessment-only page.
 */
use App\Models\AttachmentAssessment as Sheet;

$assessment = $assessment ?? null;
$form = $form ?? [];
$canFill = !empty($canFill);
[$logDays, , $oldCriteria] = Sheet::parts($assessment);
$sheetDone = !empty($assessment['assessed_at']);
$inputCls = 'w-full rounded border border-neutral-300 p-1.5 text-sm';
$ro = $canFill ? '' : ' disabled';
$num = static fn($v): string => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
?>
<section id="assessment" class="rounded-xl border bg-white p-5 shadow-sm space-y-5" style="border-color:<?= $sheetDone ? 'var(--ke-line)' : '#f59e0b' ?>;scroll-margin-top:80px">
  <div class="flex flex-wrap items-start justify-between gap-3">
    <div>
      <h2 class="font-serif-heading text-lg font-bold">Attachment assessment</h2>
      <p class="text-sm font-medium text-neutral-600">
        <?= $canFill
            ? 'Answer the assessment form, add a day each time the attachee comes, and write the closing remark when the attachment ends.'
            : 'Filled by the branch admin where the student is attached.' ?>
        It is sent to the organisation that runs <?= e($application['course_title'] ?? 'the course') ?> when the attachment is completed.
      </p>
    </div>
    <?php if ($sheetDone): ?>
      <span class="rounded-full px-3 py-1 text-xs font-black" style="background:#ecf7f0;color:var(--ke-green)">Grade <?= Sheet::grade((float) $assessment['percent']) ?> · <?= $num($assessment['percent']) ?>% · <?= !empty($assessment['shared_at']) ? 'sent to the course organisation' : 'not sent yet' ?></span>
    <?php else: ?>
      <span class="rounded-full px-3 py-1 text-xs font-black" style="background:#fffbeb;color:#92400e">Not closed yet</span>
    <?php endif; ?>
  </div>

  <form method="post" action="<?= url('/account/attachment-requests/' . (int) $application['id'] . '/assessment') ?>" class="space-y-5" id="assessment-form">
    <?= csrfField() ?>
    <?php if (($sheetPage ?? '') === 'assessment'): ?><input type="hidden" name="page" value="assessment"><?php endif; ?>

    <div class="space-y-2">
      <h3 class="text-sm font-black uppercase tracking-wide text-neutral-600">Assessment form</h3>
      <?php if (!$form): ?>
        <p class="rounded-lg border border-dashed p-3 text-sm font-semibold" style="border-color:var(--ke-line);color:var(--ke-muted)">
          <?= ($currentUser['role_slug'] ?? '') === 'attachment_trainer'
              ? 'You have no assessment form yet. <a class="underline font-bold" href="' . url('/account/assessment-template') . '">Create your assessment form</a> — your branch admins then fill it for every attachee.'
              : 'Your organisation has not made its assessment form yet. Use the daily log below for now.' ?>
        </p>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="excel-sheet w-full">
            <thead><tr><th style="width:2.5rem">#</th><th>Question</th><th style="width:11rem">Answer</th></tr></thead>
            <tbody>
              <?php foreach ($form as $i => $q): $v = $q['value'] ?? null; ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td style="white-space:normal"><?= e($q['question']) ?><?= $q['kind'] === 'score' ? ' <span class="text-neutral-500">(out of ' . (int) $q['max'] . ')</span>' : '' ?></td>
                  <td>
                    <?php if ($q['kind'] === 'score'): ?>
                      <input type="number" min="0" max="<?= (int) $q['max'] ?>" step="0.5" name="answers[<?= $i ?>]" value="<?= $v === null ? '' : e($num($v)) ?>" class="<?= $inputCls ?>" data-form-score data-max="<?= (int) $q['max'] ?>"<?= $ro ?>>
                    <?php elseif ($q['kind'] === 'yesno'): ?>
                      <select name="answers[<?= $i ?>]" class="<?= $inputCls ?>"<?= $ro ?>><option value=""></option><option value="yes" <?= $v === 'yes' ? 'selected' : '' ?>>Yes</option><option value="no" <?= $v === 'no' ? 'selected' : '' ?>>No</option></select>
                    <?php else: ?>
                      <textarea name="answers[<?= $i ?>]" rows="1" class="<?= $inputCls ?>"<?= $ro ?>><?= e((string) $v) ?></textarea>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <p class="text-right text-sm font-black" id="form-total"></p>
      <?php endif; ?>
    </div>

    <div class="space-y-2">
      <h3 class="text-sm font-black uppercase tracking-wide text-neutral-600">Daily log</h3>
      <div class="overflow-x-auto">
        <table class="excel-sheet w-full" style="min-width:620px">
          <thead><tr><th style="width:9rem">Date</th><th>What they did</th><th style="width:5.5rem">Score /<?= Sheet::DAY_MAX ?></th><th>Remarks</th><?php if ($canFill): ?><th style="width:2rem"></th><?php endif; ?></tr></thead>
          <tbody id="assessment-rows">
            <?php foreach ($logDays as $i => $day): ?>
              <tr>
                <td><input type="date" name="items[<?= $i ?>][date]" value="<?= e($day['date']) ?>" class="<?= $inputCls ?>" required<?= $ro ?>></td>
                <td><textarea name="items[<?= $i ?>][activities]" rows="1" class="<?= $inputCls ?>"<?= $ro ?>><?= e($day['activities'] ?? '') ?></textarea></td>
                <td><input type="number" min="0" max="<?= Sheet::DAY_MAX ?>" step="0.5" name="items[<?= $i ?>][score]" value="<?= e($num($day['score'])) ?>" class="<?= $inputCls ?>" data-score required<?= $ro ?>></td>
                <td><input name="items[<?= $i ?>][remarks]" value="<?= e($day['remarks'] ?? '') ?>" class="<?= $inputCls ?>"<?= $ro ?>></td>
                <?php if ($canFill): ?><td><button type="button" class="text-neutral-400 hover:text-red-600" data-remove aria-label="Remove">✕</button></td><?php endif; ?>
              </tr>
            <?php endforeach; ?>
            <?php if (!$logDays && !$canFill): ?><tr><td colspan="4" class="text-center text-neutral-500">No days recorded yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <?php if ($canFill): ?><button type="button" class="btn-secondary" id="assessment-add" style="padding:.3rem .7rem;font-size:.75rem">+ Add today</button><?php endif; ?>
        <span class="ml-auto text-sm font-black" id="assessment-total"></span>
      </div>
    </div>

    <?php if ($oldCriteria): ?>
      <details class="text-sm"><summary class="font-bold">Earlier criteria sheet</summary>
        <ul class="mt-2 list-disc pl-5"><?php foreach ($oldCriteria as $c): ?><li><?= e($c['criterion']) ?>: <?= ($c['score'] ?? null) === null ? '—' : e($num($c['score'])) ?> / <?= e($num($c['max'] ?? 0)) ?></li><?php endforeach; ?></ul>
      </details>
    <?php endif; ?>

    <label class="block text-[11px] font-bold uppercase text-neutral-600">Closing remark (when the attachment ends)
      <textarea name="remarks" rows="3" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case" placeholder="Overall conduct, skills shown, recommendation…"<?= $ro ?>><?= e($assessment['remarks'] ?? '') ?></textarea></label>
    <?php if ($canFill): ?><button type="submit" class="btn-primary">Save assessment</button><?php endif; ?>
  </form>
  <script>
  (function () {
    var body = document.getElementById('assessment-rows'), total = document.getElementById('assessment-total'), formTotal = document.getElementById('form-total'), next = body.rows.length, MAX = <?= Sheet::DAY_MAX ?>;
    function grade(p) { return p >= 80 ? 'A' : p >= 65 ? 'B' : p >= 50 ? 'C' : p >= 40 ? 'D' : 'E'; }
    function sum() {
      var got = 0, days = 0;
      body.querySelectorAll('[data-score]').forEach(function (i) { days++; got += +(i.value || 0); });
      var pct = days ? Math.round(got * 1000 / (days * MAX)) / 10 : 0;
      total.textContent = days ? days + ' day' + (days === 1 ? '' : 's') + ' · average ' + pct + '%' : '';
      if (formTotal) {
        var fg = 0, fm = 0;
        document.querySelectorAll('[data-form-score]').forEach(function (i) { if (i.value !== '') { fg += +i.value; fm += +i.dataset.max; } });
        formTotal.textContent = fm ? 'Form: ' + fg + ' / ' + fm + ' · ' + Math.round(fg * 1000 / fm) / 10 + '% · grade ' + grade(fg * 100 / fm) : '';
      }
    }
    document.getElementById('assessment-form').addEventListener('input', sum);
    <?php if ($canFill): ?>
    body.addEventListener('click', function (e) { if (e.target.closest('[data-remove]') && confirm('Remove this day?')) { e.target.closest('tr').remove(); sum(); } });
    document.getElementById('assessment-add').addEventListener('click', function () {
      var i = next++, tr = document.createElement('tr'), d = new Date(), today = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'), c = '<?= $inputCls ?>';
      tr.innerHTML = '<td><input type="date" name="items[' + i + '][date]" value="' + today + '" class="' + c + '" required></td>'
        + '<td><textarea name="items[' + i + '][activities]" rows="1" class="' + c + '" placeholder="What they did today"></textarea></td>'
        + '<td><input type="number" min="0" max="' + MAX + '" step="0.5" name="items[' + i + '][score]" class="' + c + '" data-score required></td>'
        + '<td><input name="items[' + i + '][remarks]" class="' + c + '"></td>'
        + '<td><button type="button" class="text-neutral-400 hover:text-red-600" data-remove aria-label="Remove">✕</button></td>';
      body.appendChild(tr); tr.querySelector('textarea').focus(); sum();
    });
    <?php endif; ?>
    sum();
  })();
  </script>
</section>
