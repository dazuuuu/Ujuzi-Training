<?php
/**
 * Upload a document design and drag the student's details into place.
 * Shared by Super Admin (Documents) and organisations providing courses
 * (Certificates). Requires $edType, $edTitle, $edHelp, $edTemplate,
 * $edIsPdf, $edLayout, $edUploadUrl (POST template / remove_template) and
 * $edLayoutUrl (POST layout / reset). Optional $edNote.
 */
$type = $edType;
$template = $edTemplate;
$isPdf = $edIsPdf;
?>
    <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
      <div>
        <h2 class="font-serif-heading text-lg font-bold"><?= e($edTitle) ?></h2>
        <p class="mt-1 text-sm font-medium text-neutral-600"><?= e($edHelp) ?></p>
        <?php if (!empty($edNote)): ?><p class="mt-2 rounded-lg bg-neutral-50 px-3 py-2 text-xs font-bold text-neutral-600"><?= e($edNote) ?></p><?php endif; ?>
      </div>

      <?php if ($template): $layout = $edLayout; ?>
        <div class="space-y-3 rounded-lg border p-4" id="layout-<?= e($type) ?>" style="border-color:var(--ke-line)">
          <div>
            <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Place the details</p>
            <p class="mt-1 text-sm font-medium text-neutral-600">Drag each box to where it belongs on your design. Printed documents look exactly like the design, with the student's own details written in these places.</p>
          </div>
          <?php
            $docTemplate = $template;
            $docIsPdf = $isPdf;
            $docLayout = $layout;
            $docValues = array_map(static fn(array $f): string => $f[1], \App\Services\DocumentLayout::FIELDS[$type]);
            $docEditable = true;
            $docType = $type;
            require __DIR__ . '/document-stage.php';
          ?>
          <form method="post" action="<?= e($edLayoutUrl) ?>" class="space-y-3" data-layout-form="<?= e($type) ?>">
            <?= csrfField() ?>
            <input type="hidden" name="layout" value="<?= e(json_encode($layout)) ?>">
            <div class="overflow-x-auto">
              <table class="w-full text-left text-sm">
                <thead class="text-[11px] font-black uppercase text-neutral-500"><tr><th class="py-1 pr-2">Detail</th><th class="py-1 pr-2">Show</th><th class="py-1 pr-2">Size</th><th class="py-1 pr-2">Bold</th><th class="py-1">Colour</th></tr></thead>
                <tbody>
                  <?php foreach (\App\Services\DocumentLayout::FIELDS[$type] as $field => [$label]): $pos = $layout[$field]; ?>
                    <tr data-field-row="<?= e($field) ?>">
                      <td class="py-1 pr-2 font-semibold"><?= e($label) ?></td>
                      <td class="py-1 pr-2"><input type="checkbox" data-prop="show" <?= $pos['show'] ? 'checked' : '' ?> aria-label="Show <?= e($label) ?>"></td>
                      <td class="py-1 pr-2"><input type="range" min="0.6" max="8" step="0.1" value="<?= $pos['size'] ?>" data-prop="size" aria-label="Size of <?= e($label) ?>"></td>
                      <td class="py-1 pr-2"><input type="checkbox" data-prop="bold" <?= $pos['bold'] ? 'checked' : '' ?> aria-label="Bold <?= e($label) ?>"></td>
                      <td class="py-1"><input type="color" value="<?= e($pos['color']) ?>" data-prop="color" aria-label="Colour of <?= e($label) ?>"></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="flex flex-wrap gap-2">
              <button type="submit" class="btn-primary">Save positions</button>
              <button type="submit" name="reset" value="1" class="btn-secondary" onclick="return confirm('Put every detail back to its default place?');">Reset</button>
            </div>
          </form>
        </div>
      <?php endif; ?>

      <form method="post" action="<?= e($edUploadUrl) ?>" enctype="multipart/form-data" class="space-y-4">
        <?= csrfField() ?>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600">PDF or image</label>
          <input type="file" name="template" accept=".pdf,application/pdf,image/*" required class="mt-2 block w-full text-sm" />
        </div>
        <button type="submit" class="btn-primary">Save template</button>
      </form>

      <?php if ($template): ?>
        <form method="post" action="<?= e($edUploadUrl) ?>" onsubmit="return confirm('Remove this template?');">
          <?= csrfField() ?>
          <input type="hidden" name="remove_template" value="1" />
          <button type="submit" class="btn-danger">Remove template</button>
        </form>
      <?php endif; ?>
    </section>

<script>
(function () {
  document.querySelectorAll('[data-layout-form]:not([data-bound])').forEach(function (form) {
    form.setAttribute('data-bound', '1');
    var type = form.getAttribute('data-layout-form');
    var stage = document.querySelector('[data-doc-editor="' + type + '"]');
    var input = form.querySelector('input[name="layout"]');
    var layout = JSON.parse(input.value);
    function sync() { input.value = JSON.stringify(layout); }
    function paint(field) {
      var el = stage.querySelector('[data-field="' + field + '"]');
      var p = layout[field];
      el.style.left = p.x + '%'; el.style.top = p.y + '%';
      el.style.fontSize = p.size + 'cqw'; el.style.fontWeight = p.bold ? 800 : 500; el.style.color = p.color;
      el.classList.toggle('is-hidden', !p.show);
    }
    form.querySelectorAll('[data-field-row]').forEach(function (row) {
      var field = row.getAttribute('data-field-row');
      row.querySelectorAll('[data-prop]').forEach(function (control) {
        control.addEventListener('input', function () {
          var prop = control.getAttribute('data-prop');
          layout[field][prop] = control.type === 'checkbox' ? control.checked : (prop === 'size' ? parseFloat(control.value) : control.value);
          paint(field); sync();
        });
      });
    });
    stage.querySelectorAll('.doc-field').forEach(function (el) {
      var field = el.getAttribute('data-field');
      el.addEventListener('pointerdown', function (event) {
        event.preventDefault();
        el.setPointerCapture(event.pointerId);
        stage.querySelectorAll('.doc-field').forEach(function (o) { o.classList.toggle('is-active', o === el); });
        function move(e) {
          var box = stage.getBoundingClientRect();
          layout[field].x = Math.max(0, Math.min(100, (e.clientX - box.left) / box.width * 100));
          layout[field].y = Math.max(0, Math.min(100, (e.clientY - box.top) / box.height * 100));
          paint(field);
        }
        function up() { el.removeEventListener('pointermove', move); el.removeEventListener('pointerup', up); sync(); }
        el.addEventListener('pointermove', move);
        el.addEventListener('pointerup', up);
      });
    });
  });
})();
</script>
