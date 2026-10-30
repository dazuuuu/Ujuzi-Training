<?php
/** Requires $questions (the provider's template). */
require __DIR__ . '/../layout-header.php';
use App\Models\AttachmentAssessment as Sheet;
$rows = $questions ?: [['question' => '', 'kind' => 'score', 'max' => 10]];
$cls = 'w-full rounded border border-neutral-300 p-1.5 text-sm';
?>
<div class="space-y-4">
  <section>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Attachment</p>
    <h1 class="mt-2 font-serif-heading text-2xl font-bold">Assessment form</h1>
    <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">Write your organisation's own assessment questions. Your branch admins answer them for every student attached to them, alongside the daily log and a closing remark. Marked questions make up the attachment grade sent to the organisation running the course.</p>
  </section>
  <form method="post" action="<?= url('/account/assessment-template') ?>" class="space-y-3 rounded-xl border bg-white p-4 shadow-sm" style="border-color:var(--ke-line)">
    <?= csrfField() ?>
    <div class="overflow-x-auto">
      <table class="excel-sheet w-full" style="min-width:560px">
        <thead><tr><th style="width:2.5rem">#</th><th>Question</th><th style="width:10rem">Answer type</th><th style="width:6rem">Out of</th><th style="width:2rem"></th></tr></thead>
        <tbody id="tpl-rows">
          <?php foreach ($rows as $i => $q): ?>
            <tr>
              <td data-n><?= $i + 1 ?></td>
              <td><input name="questions[<?= $i ?>][question]" value="<?= e($q['question']) ?>" class="<?= $cls ?>" placeholder="e.g. Punctuality and attendance"></td>
              <td><select name="questions[<?= $i ?>][kind]" class="<?= $cls ?>" data-kind><?php foreach (Sheet::KINDS as $k => $label): ?><option value="<?= $k ?>" <?= $q['kind'] === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></td>
              <td><input type="number" min="1" name="questions[<?= $i ?>][max]" value="<?= (int) ($q['max'] ?? 10) ?: 10 ?>" class="<?= $cls ?>" data-max <?= $q['kind'] !== 'score' ? 'hidden' : '' ?>></td>
              <td><button type="button" class="text-neutral-400 hover:text-red-600" data-remove aria-label="Remove">✕</button></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="flex flex-wrap gap-2">
      <button type="button" class="btn-secondary" id="tpl-add">+ Add question</button>
      <button type="submit" class="btn-primary ml-auto">Save assessment form</button>
    </div>
  </form>
</div>
<template id="tpl-row">
  <tr>
    <td data-n></td>
    <td><input name="questions[__][question]" class="<?= $cls ?>" placeholder="Question"></td>
    <td><select name="questions[__][kind]" class="<?= $cls ?>" data-kind><?php foreach (Sheet::KINDS as $k => $label): ?><option value="<?= $k ?>"><?= e($label) ?></option><?php endforeach; ?></select></td>
    <td><input type="number" min="1" name="questions[__][max]" value="10" class="<?= $cls ?>" data-max></td>
    <td><button type="button" class="text-neutral-400 hover:text-red-600" data-remove aria-label="Remove">✕</button></td>
  </tr>
</template>
<script>
(function () {
  var body = document.getElementById('tpl-rows'), next = body.rows.length;
  function renumber() { body.querySelectorAll('[data-n]').forEach(function (td, i) { td.textContent = i + 1; }); }
  document.getElementById('tpl-add').addEventListener('click', function () {
    var html = document.getElementById('tpl-row').innerHTML.replace(/__/g, String(next++));
    body.insertAdjacentHTML('beforeend', html); renumber(); body.lastElementChild.querySelector('input').focus();
  });
  body.addEventListener('click', function (e) { if (e.target.closest('[data-remove]')) { e.target.closest('tr').remove(); renumber(); } });
  body.addEventListener('change', function (e) { if (e.target.matches('[data-kind]')) { e.target.closest('tr').querySelector('[data-max]').hidden = e.target.value !== 'score'; } });
})();
</script>
<?php require __DIR__ . '/../layout-footer.php'; ?>
