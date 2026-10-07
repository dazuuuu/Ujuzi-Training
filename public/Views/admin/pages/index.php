<?php
/**
 * The public page builder. Requires $page, $sections, $site, $defaults and
 * $schema (PageBuilder::schema()). Everything happens in the browser: the
 * editor keeps the page as JSON, sends it to /admin/pages/preview on every
 * change so the preview frame (?pb_preview=<token>) shows it, and to /admin/pages/save to publish.
 */
use App\Services\PageBuilder;

$pageUrls = array_map([PageBuilder::class, 'pageUrl'], array_combine(array_keys(PageBuilder::pages()), array_keys(PageBuilder::pages())));
require __DIR__ . '/../layout-header.php';
?>

<style>
  .pbe{display:grid;grid-template-columns:minmax(300px,380px) 1fr;gap:14px;height:calc(100vh - 150px);min-height:560px;}
  .pbe-panel{display:flex;flex-direction:column;min-height:0;border:1px solid #d1d5db;border-radius:12px;background:#fff;overflow:hidden;}
  .pbe-panel-head{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 12px;border-bottom:1px solid #e5e7eb;background:#f9fafb;}
  .pbe-panel-body{flex:1;min-height:0;overflow-y:auto;padding:12px;}
  .pbe-tabs{display:flex;flex-wrap:wrap;gap:6px;}
  .pbe-tab{padding:.4rem .8rem;border:1px solid #d1d5db;border-radius:99px;background:#fff;font-size:.8rem;font-weight:700;color:#374151;cursor:pointer;}
  .pbe-tab.is-on{background:var(--ke-green,#006b3f);border-color:var(--ke-green,#006b3f);color:#fff;}
  .pbe-list{display:flex;flex-direction:column;gap:6px;}
  .pbe-item{display:flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid #e5e7eb;border-radius:10px;background:#fff;cursor:pointer;user-select:none;}
  .pbe-item:hover{border-color:#9ca3af;}
  .pbe-item.is-selected{border-color:#2563eb;box-shadow:0 0 0 1px #2563eb;}
  .pbe-item.is-hidden .pbe-item-name{opacity:.45;text-decoration:line-through;}
  .pbe-item.is-dragging{opacity:.4;}
  .pbe-item.drop-before{box-shadow:0 -3px 0 #2563eb;}
  .pbe-grip{cursor:grab;color:#9ca3af;font-size:1rem;line-height:1;}
  .pbe-item-name{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.84rem;font-weight:700;color:#111827;}
  .pbe-item-name small{display:block;font-weight:600;color:#6b7280;font-size:.72rem;overflow:hidden;text-overflow:ellipsis;}
  .pbe-icon-btn{border:0;background:none;padding:4px;border-radius:6px;cursor:pointer;color:#6b7280;font-size:.85rem;line-height:1;}
  .pbe-icon-btn:hover{background:#f3f4f6;color:#111827;}
  .pbe-field{margin-bottom:12px;}
  .pbe-field > label{display:block;margin-bottom:4px;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#4b5563;}
  .pbe-field input[type=text],.pbe-field input[type=number],.pbe-field select,.pbe-field textarea{width:100%;padding:.5rem .6rem;border:1px solid #d1d5db;border-radius:8px;font-size:.85rem;background:#fff;}
  .pbe-field textarea{min-height:84px;resize:vertical;}
  .pbe-check{display:flex;align-items:center;gap:8px;font-size:.84rem;font-weight:700;color:#374151;}
  .pbe-color{display:flex;gap:6px;align-items:center;}
  .pbe-color input[type=color]{width:42px;height:34px;padding:2px;border:1px solid #d1d5db;border-radius:8px;background:#fff;}
  .pbe-image{display:flex;flex-direction:column;gap:6px;}
  .pbe-image img{max-height:110px;border-radius:8px;object-fit:cover;border:1px solid #e5e7eb;}
  .pbe-items{display:flex;flex-direction:column;gap:8px;}
  .pbe-sub{padding:10px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb;}
  .pbe-sub-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;font-size:.74rem;font-weight:800;color:#374151;}
  .pbe-seg{display:flex;border:1px solid #d1d5db;border-radius:10px;overflow:hidden;margin-bottom:12px;}
  .pbe-seg button{flex:1;padding:.45rem;border:0;background:#fff;font-size:.8rem;font-weight:800;color:#374151;cursor:pointer;}
  .pbe-seg button.is-on{background:#111827;color:#fff;}
  .pbe-add{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:10px;}
  .pbe-add button{padding:.5rem;border:1px dashed #9ca3af;border-radius:8px;background:#fff;font-size:.76rem;font-weight:700;color:#374151;cursor:pointer;text-align:left;}
  .pbe-add button:hover{border-color:var(--ke-green,#006b3f);color:var(--ke-green,#006b3f);}
  .pbe-stage{display:flex;flex-direction:column;min-height:0;border:1px solid #d1d5db;border-radius:12px;background:#e5e7eb;overflow:hidden;}
  .pbe-frame-wrap{flex:1;min-height:0;display:flex;justify-content:center;overflow:auto;padding:0;}
  .pbe-frame-wrap iframe{width:100%;height:100%;border:0;background:#fff;transition:width .2s ease;}
  .pbe-status{font-size:.75rem;font-weight:700;color:#6b7280;}
  @media(max-width:1000px){.pbe{grid-template-columns:1fr;height:auto;}.pbe-stage{height:70vh;}}
</style>

<div class="space-y-3">
  <section class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Website</p>
      <h2 class="mt-1 text-2xl font-black text-black">Public pages</h2>
      <p class="mt-1 text-sm font-medium text-neutral-700">Pick a page, then add, drag, hide and edit its sections. Click any part of the preview to edit it. Changes show in the preview straight away; visitors see them once you publish.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="pbe-status" id="pbe-status">No unpublished changes</span>
      <a href="<?= e(url($pageUrls[$page])) ?>" target="_blank" rel="noopener" class="btn-secondary">View live page</a>
      <button type="button" class="btn-primary" id="pbe-publish">Publish</button>
    </div>
  </section>

  <nav class="pbe-tabs" aria-label="Pages">
    <a href="<?= url('/admin/pages') ?>" class="pbe-tab" data-leave>← All pages</a>
    <?php foreach (PageBuilder::pages() as $key => $label): ?>
      <a href="<?= url('/admin/pages?page=' . $key) ?>" class="pbe-tab <?= $key === $page ? 'is-on' : '' ?>" data-leave><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>

  <div class="pbe">
    <aside class="pbe-panel">
      <div class="pbe-panel-head">
        <div class="pbe-seg" style="margin:0;flex:1" role="tablist">
          <button type="button" data-mode="sections" class="is-on">Sections</button>
          <button type="button" data-mode="site">Navbar &amp; footer</button>
        </div>
      </div>
      <div class="pbe-panel-body" id="pbe-panel"></div>
    </aside>

    <section class="pbe-stage">
      <div class="pbe-panel-head">
        <div class="pbe-seg" style="margin:0" aria-label="Preview size">
          <button type="button" data-device="100%" class="is-on">Desktop</button>
          <button type="button" data-device="820px">Tablet</button>
          <button type="button" data-device="390px">Phone</button>
        </div>
        <button type="button" class="btn-secondary" id="pbe-reset" style="padding:.3rem .7rem;font-size:.75rem">Start from original design</button>
      </div>
      <div class="pbe-frame-wrap">
        <iframe id="pbe-frame" title="Page preview" src="<?= e(url($pageUrls[$page]) . '?pb_preview=' . $previewToken) ?>"></iframe>
      </div>
    </section>
  </div>
</div>

<script>
(function () {
  var PAGE = <?= json_encode($page) ?>;
  var SCHEMA = <?= json_encode($schema, JSON_UNESCAPED_UNICODE) ?>;
  var DEFAULTS = <?= json_encode($defaults, JSON_UNESCAPED_UNICODE) ?>;
  var CSRF = <?= json_encode(csrfToken()) ?>;
  var URLS = {
    save: <?= json_encode(url('/admin/pages/save')) ?>,
    preview: <?= json_encode(url('/admin/pages/preview')) ?>,
    upload: <?= json_encode(url('/admin/pages/upload')) ?>,
    image: <?= json_encode(imageUrl('__PATH__')) ?>
  };
  var state = { sections: <?= json_encode($sections, JSON_UNESCAPED_UNICODE) ?>, site: <?= json_encode($site, JSON_UNESCAPED_UNICODE) ?> };
  var mode = 'sections';      // or 'site'
  var selected = null;        // index of the section being edited
  var sectionTab = 'content'; // or 'style'
  var dirty = false;

  var panel = document.getElementById('pbe-panel');
  var frame = document.getElementById('pbe-frame');
  var statusEl = document.getElementById('pbe-status');

  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function clone(o) { return JSON.parse(JSON.stringify(o)); }
  function typeInfo(type) { return SCHEMA.types[type] || { label: type, fields: [] }; }
  function allowedTypes() {
    return Object.keys(SCHEMA.types).filter(function (t) { var p = SCHEMA.types[t].pages; return !p || p.indexOf(PAGE) !== -1; });
  }
  function summary(section) {
    var c = section.content || {};
    return c.title || c.badge || c.label || c.text || '';
  }

  /* ---------- Sending changes ---------- */
  var previewTimer = null;
  function changed() {
    dirty = true;
    statusEl.textContent = 'Unpublished changes';
    statusEl.style.color = '#b45309';
    clearTimeout(previewTimer);
    previewTimer = setTimeout(sendPreview, 450);
  }
  function post(url, body) {
    return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(body) })
      .then(function (r) { return r.json(); });
  }
  function sendPreview() {
    post(URLS.preview, { csrf_token: CSRF, page: PAGE, sections: state.sections, site: state.site }).then(reloadFrame);
  }
  function reloadFrame() {
    var y = 0;
    try { y = frame.contentWindow.scrollY; } catch (e) {}
    frame.onload = function () {
      try { frame.contentWindow.scrollTo(0, y); } catch (e) {}
      highlight();
    };
    frame.src = frame.src.split('#')[0];
  }
  function highlight() {
    try { frame.contentWindow.postMessage({ pbSelected: mode === 'sections' && selected !== null ? selected : -1 }, location.origin); } catch (e) {}
  }
  window.addEventListener('message', function (event) {
    if (event.origin !== location.origin || typeof event.data.pbSelect !== 'number') return;
    mode = 'sections'; selected = event.data.pbSelect; sectionTab = 'content';
    syncModeButtons(); render();
  });

  document.getElementById('pbe-publish').addEventListener('click', function () {
    var btn = this; btn.disabled = true;
    post(URLS.save, { csrf_token: CSRF, page: PAGE, sections: state.sections, site: state.site }).then(function (res) {
      btn.disabled = false;
      if (res.ok) { dirty = false; statusEl.textContent = res.message || 'Published'; statusEl.style.color = 'var(--ke-green,#006b3f)'; }
      else { statusEl.textContent = res.message || 'Could not publish'; statusEl.style.color = '#b91c1c'; }
    }).catch(function () { btn.disabled = false; statusEl.textContent = 'Could not publish — check your connection.'; });
  });
  document.getElementById('pbe-reset').addEventListener('click', function () {
    if (!confirm('Replace this page with its original design? Nothing changes for visitors until you publish.')) return;
    state.sections = clone(DEFAULTS); selected = null; render(); changed();
  });
  document.querySelectorAll('[data-leave]').forEach(function (a) {
    a.addEventListener('click', function (e) { if (dirty && !confirm('You have unpublished changes on this page. Leave without publishing?')) e.preventDefault(); });
  });
  window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

  document.querySelectorAll('[data-device]').forEach(function (b) {
    b.addEventListener('click', function () {
      document.querySelectorAll('[data-device]').forEach(function (x) { x.classList.toggle('is-on', x === b); });
      frame.style.width = b.getAttribute('data-device');
    });
  });
  function syncModeButtons() {
    document.querySelectorAll('[data-mode]').forEach(function (x) { x.classList.toggle('is-on', x.getAttribute('data-mode') === mode); });
  }
  document.querySelectorAll('[data-mode]').forEach(function (b) {
    b.addEventListener('click', function () { mode = b.getAttribute('data-mode'); selected = null; syncModeButtons(); render(); highlight(); });
  });

  /* ---------- Fields ---------- */
  // Draws one field bound to obj[field.key]; calls onChange after edits.
  function fieldHtml(field, value, path) {
    var id = 'f-' + path.replace(/[^a-z0-9]/gi, '-');
    var attr = ' data-path="' + esc(path) + '" data-kind="' + field.kind + '"';
    switch (field.kind) {
      case 'textarea':
        return '<div class="pbe-field"><label for="' + id + '">' + esc(field.label) + '</label><textarea id="' + id + '"' + attr + '>' + esc(value) + '</textarea></div>';
      case 'html':
        return '<div class="pbe-field"><label for="' + id + '">' + esc(field.label) + '</label><textarea id="' + id + '"' + attr + ' style="min-height:220px;font-family:ui-monospace,Menlo,monospace;font-size:.78rem" spellcheck="false">' + esc(value) + '</textarea></div>';
      case 'select':
        var opts = Object.keys(field.options).map(function (k) { return '<option value="' + esc(k) + '"' + (String(value) === k ? ' selected' : '') + '>' + esc(field.options[k]) + '</option>'; }).join('');
        return '<div class="pbe-field"><label for="' + id + '">' + esc(field.label) + '</label><select id="' + id + '"' + attr + '><option value="">Default</option>' + opts + '</select></div>';
      case 'number':
        return '<div class="pbe-field"><label for="' + id + '">' + esc(field.label) + '</label><input type="number" id="' + id + '" min="' + field.min + '" max="' + field.max + '" value="' + esc(value == null ? '' : value) + '"' + attr + '></div>';
      case 'checkbox':
        return '<div class="pbe-field"><label class="pbe-check"><input type="checkbox"' + attr + (value ? ' checked' : '') + '> ' + esc(field.label) + '</label></div>';
      case 'color':
        return '<div class="pbe-field"><label>' + esc(field.label) + '</label><div class="pbe-color"><input type="color" value="' + esc(value || '#ffffff') + '"' + attr + ' data-color-pick><input type="text" placeholder="e.g. #006b3f" value="' + esc(value) + '"' + attr + '><button type="button" class="pbe-icon-btn" data-clear="' + esc(path) + '" title="Clear">✕</button></div></div>';
      case 'image':
        return '<div class="pbe-field"><label>' + esc(field.label) + '</label><div class="pbe-image">'
          + (value ? '<img src="' + esc(URLS.image.replace('__PATH__', value)) + '" alt="">' : '')
          + '<input type="file" accept="image/*" data-upload="' + esc(path) + '">'
          + (value ? '<button type="button" class="btn-secondary" style="padding:.25rem .6rem;font-size:.75rem" data-clear="' + esc(path) + '">Remove picture</button>' : '')
          + '</div></div>';
      case 'items':
        var list = Array.isArray(value) ? value : [];
        var html = '<div class="pbe-field"><label>' + esc(field.label) + '</label><div class="pbe-items">';
        list.forEach(function (item, i) {
          html += '<div class="pbe-sub"><div class="pbe-sub-head"><span>' + (i + 1) + '. ' + esc(item.title || item.label || item.name || item.value || '') + '</span><span>'
            + '<button type="button" class="pbe-icon-btn" data-item-move="' + esc(path) + '|' + i + '|-1" title="Move up">↑</button>'
            + '<button type="button" class="pbe-icon-btn" data-item-move="' + esc(path) + '|' + i + '|1" title="Move down">↓</button>'
            + '<button type="button" class="pbe-icon-btn" data-item-del="' + esc(path) + '|' + i + '" title="Remove">✕</button></span></div>';
          field.fields.forEach(function (sub) { html += fieldHtml(sub, item[sub.key], path + '.' + i + '.' + sub.key); });
          html += '</div>';
        });
        return html + '<button type="button" class="btn-secondary" style="padding:.3rem .7rem;font-size:.75rem" data-item-add="' + esc(path) + '">+ Add</button></div></div>';
      default:
        return '<div class="pbe-field"><label for="' + id + '">' + esc(field.label) + '</label><input type="text" id="' + id + '" value="' + esc(value) + '"' + attr + (field.kind === 'url' ? ' placeholder="/courses or https://…"' : '') + '></div>';
    }
  }

  // Paths look like "s.2.content.items.0.title" (section) or "site.nav_links.1.url".
  function resolve(path) {
    var parts = path.split('.');
    var obj = parts[0] === 'site' ? state : state.sections;
    if (parts[0] === 's') parts.shift(); // section paths start at the index
    for (var i = 0; i < parts.length - 1; i++) {
      if (obj[parts[i]] == null) obj[parts[i]] = /^\d+$/.test(parts[i + 1]) ? [] : {};
      obj = obj[parts[i]];
    }
    return { obj: obj, key: parts[parts.length - 1] };
  }
  function setPath(path, value) { var r = resolve(path); r.obj[r.key] = value; }
  function getPath(path) { var r = resolve(path); return r.obj[r.key]; }
  function blankItem(fields) { var o = {}; fields.forEach(function (f) { o[f.key] = f.kind === 'checkbox' ? false : ''; }); return o; }
  function fieldAt(path) {
    // Finds the schema field for an items path, to know its sub-fields.
    var parts = path.split('.');
    if (parts[0] === 'site') return SCHEMA.site.filter(function (f) { return f.key === parts[1]; })[0];
    var section = state.sections[+parts[1]];
    var list = parts[2] === 'style' ? SCHEMA.style : typeInfo(section.type).fields;
    return list.filter(function (f) { return f.key === parts[3]; })[0];
  }

  panel.addEventListener('input', function (e) {
    var el = e.target, path = el.getAttribute('data-path');
    if (!path || el.type === 'file') return;
    var kind = el.getAttribute('data-kind');
    var value = kind === 'checkbox' ? el.checked : (kind === 'number' ? (el.value === '' ? null : +el.value) : el.value);
    setPath(path, value);
    if (kind === 'color') { // keep the picker and the text box in step
      panel.querySelectorAll('[data-path="' + path + '"]').forEach(function (x) { if (x !== el) x.value = value || (x.type === 'color' ? '#ffffff' : ''); });
    }
    if (mode === 'sections' && selected === null) return;
    changed();
    // Refresh the list name without redrawing the form under the cursor.
    if (/\.content\.title$/.test(path)) { var n = panel.querySelector('[data-title-of="' + selected + '"]'); if (n) n.textContent = value; }
  });
  panel.addEventListener('change', function (e) {
    var el = e.target;
    if (el.getAttribute('data-kind') === 'checkbox' || el.tagName === 'SELECT') { changed(); }
    var upload = el.getAttribute('data-upload');
    if (upload && el.files[0]) {
      var data = new FormData();
      data.append('csrf_token', CSRF);
      data.append('image', el.files[0]);
      el.disabled = true;
      fetch(URLS.upload, { method: 'POST', body: data }).then(function (r) { return r.json(); }).then(function (res) {
        if (!res.ok) { alert(res.message || 'Upload failed'); el.disabled = false; return; }
        setPath(upload, res.path); render(); changed();
      }).catch(function () { alert('Upload failed'); el.disabled = false; });
    }
  });
  panel.addEventListener('click', function (e) {
    var t = e.target.closest('button,[data-select]');
    if (!t) return;
    var a;
    if ((a = t.getAttribute('data-clear')) !== null) { setPath(a, ''); render(); changed(); return; }
    if ((a = t.getAttribute('data-item-add')) !== null) {
      var list = getPath(a); if (!Array.isArray(list)) { list = []; setPath(a, list); }
      list.push(blankItem(fieldAt(a).fields)); render(); changed(); return;
    }
    if ((a = t.getAttribute('data-item-del')) !== null) { var p = a.split('|'); getPath(p[0]).splice(+p[1], 1); render(); changed(); return; }
    if ((a = t.getAttribute('data-item-move')) !== null) {
      var q = a.split('|'), arr = getPath(q[0]), from = +q[1], to = from + (+q[2]);
      if (to >= 0 && to < arr.length) { arr.splice(to, 0, arr.splice(from, 1)[0]); render(); changed(); }
      return;
    }
    if ((a = t.getAttribute('data-add')) !== null) {
      var info = typeInfo(a), content = {};
      info.fields.forEach(function (f) { content[f.key] = f.kind === 'items' ? [blankItem(f.fields)] : (f.kind === 'checkbox' ? false : ''); });
      if (content.title !== undefined) content.title = info.label;
      var at = selected === null ? state.sections.length : selected + 1;
      state.sections.splice(at, 0, { type: a, content: content, style: {} });
      selected = at; sectionTab = 'content'; render(); changed(); return;
    }
    if ((a = t.getAttribute('data-act')) !== null) {
      var i = +t.getAttribute('data-i');
      e.stopPropagation();
      if (a === 'hide') { var st = state.sections[i].style = state.sections[i].style || {}; st.hidden = !st.hidden; }
      if (a === 'dup') { state.sections.splice(i + 1, 0, clone(state.sections[i])); selected = i + 1; }
      if (a === 'del') { if (!confirm('Delete this section?')) return; state.sections.splice(i, 1); selected = null; }
      if (a === 'up' && i > 0) { state.sections.splice(i - 1, 0, state.sections.splice(i, 1)[0]); selected = i - 1; }
      if (a === 'down' && i < state.sections.length - 1) { state.sections.splice(i + 1, 0, state.sections.splice(i, 1)[0]); selected = i + 1; }
      render(); changed(); return;
    }
    if (t.hasAttribute('data-back')) { selected = null; render(); highlight(); return; }
    if ((a = t.getAttribute('data-tab')) !== null) { sectionTab = a; render(); return; }
    if ((a = t.getAttribute('data-select')) !== null) { selected = +a; sectionTab = 'content'; render(); highlight(); }
  });

  /* ---------- Drag to reorder ---------- */
  var dragFrom = null;
  panel.addEventListener('dragstart', function (e) {
    var item = e.target.closest('[data-select]'); if (!item) return;
    dragFrom = +item.getAttribute('data-select'); item.classList.add('is-dragging');
    e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', String(dragFrom));
  });
  panel.addEventListener('dragover', function (e) {
    var item = e.target.closest('[data-select]'); if (dragFrom === null || !item) return;
    e.preventDefault();
    panel.querySelectorAll('.drop-before').forEach(function (x) { x.classList.remove('drop-before'); });
    item.classList.add('drop-before');
  });
  panel.addEventListener('drop', function (e) {
    var item = e.target.closest('[data-select]'); if (dragFrom === null || !item) return;
    e.preventDefault();
    var to = +item.getAttribute('data-select');
    var moved = state.sections.splice(dragFrom, 1)[0];
    if (dragFrom < to) to--;
    state.sections.splice(to, 0, moved);
    selected = to; dragFrom = null; render(); changed();
  });
  panel.addEventListener('dragend', function () { dragFrom = null; render(); });

  /* ---------- Panel ---------- */
  function render() {
    if (mode === 'site') {
      panel.innerHTML = '<p class="text-xs font-semibold text-neutral-500" style="margin-bottom:12px">The navbar and footer are shared by every public page.</p>'
        + SCHEMA.site.map(function (f) { return fieldHtml(f, state.site[f.key], 'site.' + f.key); }).join('');
      return;
    }
    if (selected !== null && state.sections[selected]) {
      var s = state.sections[selected], info = typeInfo(s.type);
      var fields = sectionTab === 'content' ? info.fields : SCHEMA.style;
      var bag = sectionTab === 'content' ? (s.content = s.content || {}) : (s.style = s.style || {});
      panel.innerHTML = '<button type="button" class="btn-secondary" data-back style="padding:.3rem .7rem;font-size:.75rem;margin-bottom:10px">← All sections</button>'
        + '<h3 style="font-weight:900;margin-bottom:8px">' + esc(info.label) + '</h3>'
        + '<div class="pbe-seg"><button type="button" data-tab="content" class="' + (sectionTab === 'content' ? 'is-on' : '') + '">Content</button><button type="button" data-tab="style" class="' + (sectionTab === 'style' ? 'is-on' : '') + '">Layout &amp; size</button></div>'
        + (fields.length ? fields.map(function (f) { return fieldHtml(f, bag[f.key], 's.' + selected + '.' + (sectionTab === 'content' ? 'content' : 'style') + '.' + f.key); }).join('')
                         : '<p class="text-sm text-neutral-500">This section has no settings of its own — use Layout &amp; size.</p>');
      return;
    }
    var html = '<div class="pbe-list">';
    state.sections.forEach(function (s, i) {
      var hidden = s.style && s.style.hidden;
      html += '<div class="pbe-item' + (hidden ? ' is-hidden' : '') + (selected === i ? ' is-selected' : '') + '" draggable="true" data-select="' + i + '">'
        + '<span class="pbe-grip" title="Drag to move">⠿</span>'
        + '<span class="pbe-item-name">' + esc(typeInfo(s.type).label) + '<small data-title-of="' + i + '">' + esc(summary(s)) + '</small></span>'
        + '<button type="button" class="pbe-icon-btn" data-act="up" data-i="' + i + '" title="Move up">↑</button>'
        + '<button type="button" class="pbe-icon-btn" data-act="down" data-i="' + i + '" title="Move down">↓</button>'
        + '<button type="button" class="pbe-icon-btn" data-act="hide" data-i="' + i + '" title="' + (hidden ? 'Show' : 'Hide') + '">' + (hidden ? '🙈' : '👁') + '</button>'
        + '<button type="button" class="pbe-icon-btn" data-act="dup" data-i="' + i + '" title="Duplicate">⧉</button>'
        + '<button type="button" class="pbe-icon-btn" data-act="del" data-i="' + i + '" title="Delete">🗑</button>'
        + '</div>';
    });
    if (!state.sections.length) html += '<p class="text-sm font-semibold text-neutral-500">This page has no sections. Add one below.</p>';
    html += '</div><p class="text-[11px] font-black uppercase tracking-widest text-neutral-500" style="margin-top:16px">Add a section</p><div class="pbe-add">';
    allowedTypes().forEach(function (t) { html += '<button type="button" data-add="' + t + '">+ ' + esc(SCHEMA.types[t].label) + '</button>'; });
    panel.innerHTML = html + '</div>';
  }

  render();
})();
</script>

<?php require __DIR__ . '/../layout-footer.php'; ?>
