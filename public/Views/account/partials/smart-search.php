<?php
/**
 * The portal's smart search panel, opened from the top bar (or Ctrl/⌘ K, or
 * "/"). Asks /account/search as you type and groups what comes back:
 * courses, organisations providing attachment, organisations providing
 * courses and pages. Arrow keys move, Enter opens; recent searches are kept
 * in this browser only.
 */
$ssStudent = !empty($isStudent);
?>
<div class="ss-overlay" id="ss" hidden role="dialog" aria-modal="true" aria-label="Search">
  <div class="ss-panel">
    <div class="ss-bar">
      <?= icon('search', 'h-5 w-5') ?>
      <input type="search" id="ss-input" autocomplete="off" spellcheck="false"
             placeholder="<?= $ssStudent ? 'Search courses, attachment organisations…' : 'Search courses, organisations, pages…' ?>"
             aria-controls="ss-results" aria-autocomplete="list">
      <button type="button" class="ss-close" data-ss-close aria-label="Close search">Esc</button>
    </div>
    <div class="ss-chips" role="tablist" aria-label="What to search">
      <button type="button" class="is-on" data-ss-type="all">All</button>
      <button type="button" data-ss-type="courses">Courses</button>
      <button type="button" data-ss-type="attachment">Attachment</button>
      <button type="button" data-ss-type="organisations">Organisations</button>
      <button type="button" data-ss-type="pages">Pages</button>
    </div>
    <div class="ss-results" id="ss-results" role="listbox"></div>
    <p class="ss-foot"><span>↑↓ to move · Enter to open</span><span>Small typos are fine</span></p>
  </div>
</div>

<style>
  .ss-trigger{display:inline-flex;align-items:center;gap:8px;height:40px;padding:0 12px;border:1px solid #e5e7eb;border-radius:999px;background:#fff;color:#6b7280;font-size:.85rem;font-weight:600;cursor:pointer;min-width:0;}
  .ss-trigger:hover{border-color:var(--ke-green);color:var(--ke-green);}
  .ss-trigger-text{white-space:nowrap;}
  .ss-kbd{font-family:inherit;font-size:.65rem;font-weight:800;padding:2px 6px;border-radius:6px;background:#f3f4f6;color:#6b7280;}
  @media(max-width:900px){
    .ss-trigger{width:40px;height:40px;padding:0;justify-content:center;border-radius:12px;color:#374151;}
    .ss-trigger-text,.ss-kbd{display:none;}
  }
  .ss-overlay{position:fixed;inset:0;z-index:300;display:flex;justify-content:center;align-items:flex-start;padding:min(10vh,80px) 12px 12px;background:rgba(15,23,42,.45);backdrop-filter:blur(2px);}
  .ss-overlay[hidden]{display:none;}
  .ss-panel{display:flex;flex-direction:column;width:min(640px,100%);max-height:min(76vh,640px);border-radius:18px;background:#fff;box-shadow:0 24px 60px rgba(0,0,0,.25);overflow:hidden;}
  .ss-bar{display:flex;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid #f1f5f9;color:#6b7280;}
  .ss-bar input{flex:1;min-width:0;border:0;outline:none;font-size:1rem;font-weight:600;color:#111827;background:transparent;}
  .ss-close{border:1px solid #e5e7eb;border-radius:8px;padding:2px 8px;font-size:.7rem;font-weight:800;color:#6b7280;background:#fff;cursor:pointer;}
  .ss-chips{display:flex;gap:6px;padding:10px 16px;overflow-x:auto;border-bottom:1px solid #f1f5f9;scrollbar-width:none;}
  .ss-chips button{flex-shrink:0;padding:.3rem .8rem;border:1px solid #e5e7eb;border-radius:999px;background:#fff;font-size:.75rem;font-weight:800;color:#374151;cursor:pointer;}
  .ss-chips button.is-on{background:var(--ke-green);border-color:var(--ke-green);color:#fff;}
  .ss-results{flex:1;overflow-y:auto;padding:6px 8px 10px;}
  .ss-group{padding:10px 8px 4px;font-size:.66rem;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:#9ca3af;}
  .ss-item{display:flex;align-items:center;gap:12px;padding:9px 10px;border-radius:12px;color:#111827;text-decoration:none;}
  .ss-item.is-active,.ss-item:hover{background:#ecf7f0;}
  .ss-thumb{display:flex;align-items:center;justify-content:center;width:40px;height:40px;flex-shrink:0;border-radius:10px;background:#f1f5f9;color:var(--ke-green);font-weight:900;overflow:hidden;}
  .ss-thumb img{width:100%;height:100%;object-fit:cover;}
  .ss-title{display:block;font-size:.9rem;font-weight:800;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
  .ss-sub{display:block;font-size:.74rem;font-weight:600;color:#6b7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
  .ss-item mark{background:#fef3c7;color:inherit;border-radius:3px;padding:0 1px;}
  .ss-kind{margin-left:auto;flex-shrink:0;font-size:.62rem;font-weight:900;text-transform:uppercase;color:#9ca3af;}
  .ss-empty{padding:28px 16px;text-align:center;font-size:.85rem;font-weight:700;color:#6b7280;}
  .ss-recent{display:flex;flex-wrap:wrap;gap:6px;padding:4px 8px;}
  .ss-recent button{padding:.3rem .7rem;border-radius:999px;border:1px dashed #d1d5db;background:#fff;font-size:.78rem;font-weight:700;color:#374151;cursor:pointer;}
  .ss-foot{display:flex;justify-content:space-between;gap:8px;padding:8px 16px;border-top:1px solid #f1f5f9;font-size:.68rem;font-weight:700;color:#9ca3af;}
  @media(max-width:640px){.ss-overlay{padding:0;}.ss-panel{max-height:100dvh;height:100dvh;border-radius:0;width:100%;}.ss-foot{display:none;}}
</style>

<script>
(function () {
  var overlay = document.getElementById('ss');
  var input = document.getElementById('ss-input');
  var list = document.getElementById('ss-results');
  var endpoint = <?= json_encode(url('/account/search')) ?>;
  var type = 'all', timer = null, active = -1, seq = 0;
  var labels = { courses: 'Courses', attachment: 'Organisations providing attachment', organisations: 'Organisations providing courses', pages: 'Pages' };
  var RECENT_KEY = 'ujuzi.search.recent';

  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function highlight(text, q) {
    var out = esc(text);
    q.split(/\s+/).filter(function (t) { return t.length > 1; }).forEach(function (t) {
      out = out.replace(new RegExp('(' + t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'ig'), '<mark>$1</mark>');
    });
    return out;
  }
  function recent() { try { return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]'); } catch (e) { return []; } }
  function remember(q) {
    try { var r = recent().filter(function (x) { return x !== q; }); r.unshift(q); localStorage.setItem(RECENT_KEY, JSON.stringify(r.slice(0, 6))); } catch (e) {}
  }

  function open() {
    overlay.hidden = false;
    document.body.style.overflow = 'hidden';
    setTimeout(function () { input.focus(); input.select(); }, 0);
    if (!input.value.trim()) showRecent();
  }
  function close() { overlay.hidden = true; document.body.style.overflow = ''; }

  function showRecent() {
    var r = recent();
    list.innerHTML = r.length
      ? '<p class="ss-group">Recent searches</p><div class="ss-recent">' + r.map(function (q) { return '<button type="button" data-ss-recent="' + esc(q) + '">' + esc(q) + '</button>'; }).join('') + '</div>'
      : '<p class="ss-empty">Type a course name, an organisation or a town — e.g. “driving”, “Nairobi”.</p>';
    active = -1;
  }

  function search() {
    var q = input.value.trim();
    if (!q) { showRecent(); return; }
    var mine = ++seq;
    list.innerHTML = '<p class="ss-empty">Searching…</p>';
    fetch(endpoint + '?q=' + encodeURIComponent(q) + '&type=' + type, { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (mine !== seq) return; // a newer search is on its way
        var html = '';
        Object.keys(data.groups || {}).forEach(function (g) {
          var items = data.groups[g];
          if (!items.length) return;
          html += '<p class="ss-group">' + esc(labels[g] || g) + '</p>';
          items.forEach(function (it) {
            var thumb = it.image ? '<img src="' + esc(it.image) + '" alt="">' : esc((it.title || '?').charAt(0).toUpperCase());
            html += '<a class="ss-item" role="option" href="' + esc(it.url) + '"><span class="ss-thumb" aria-hidden="true">' + thumb + '</span>'
              + '<span style="min-width:0"><span class="ss-title">' + highlight(it.title, q) + '</span>'
              + (it.subtitle ? '<span class="ss-sub">' + highlight(it.subtitle, q) + '</span>' : '') + '</span>'
              + '<span class="ss-kind">' + esc(it.kind) + '</span></a>';
          });
        });
        list.innerHTML = html || '<p class="ss-empty">Nothing matches “' + esc(q) + '”. Try fewer or different words.</p>';
        active = -1;
      })
      .catch(function () { if (mine === seq) list.innerHTML = '<p class="ss-empty">Search is unavailable right now. Try again.</p>'; });
  }

  function move(step) {
    var items = list.querySelectorAll('.ss-item');
    if (!items.length) return;
    active = (active + step + items.length) % items.length;
    items.forEach(function (el, i) { el.classList.toggle('is-active', i === active); });
    items[active].scrollIntoView({ block: 'nearest' });
  }

  document.querySelectorAll('[data-ss-open]').forEach(function (b) { b.addEventListener('click', open); });
  overlay.addEventListener('click', function (e) {
    if (e.target === overlay || e.target.closest('[data-ss-close]')) { close(); return; }
    var chip = e.target.closest('[data-ss-type]');
    if (chip) {
      type = chip.getAttribute('data-ss-type');
      overlay.querySelectorAll('[data-ss-type]').forEach(function (c) { c.classList.toggle('is-on', c === chip); });
      search(); input.focus(); return;
    }
    var r = e.target.closest('[data-ss-recent]');
    if (r) { input.value = r.getAttribute('data-ss-recent'); search(); input.focus(); return; }
    if (e.target.closest('.ss-item')) remember(input.value.trim());
  });
  input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(search, 220); });
  input.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowDown') { e.preventDefault(); move(1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
    else if (e.key === 'Enter') {
      var items = list.querySelectorAll('.ss-item');
      var target = items[active >= 0 ? active : 0];
      if (target) { e.preventDefault(); remember(input.value.trim()); location.href = target.href; }
    }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !overlay.hidden) { close(); return; }
    var typing = /input|textarea|select/i.test((document.activeElement || {}).tagName || '') || (document.activeElement || {}).isContentEditable;
    if (((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') || (e.key === '/' && !typing)) {
      e.preventDefault(); overlay.hidden ? open() : close();
    }
  });
})();
</script>
