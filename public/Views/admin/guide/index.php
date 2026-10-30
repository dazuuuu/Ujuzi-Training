<?php
/** Requires $sections (see App\Services\SystemGuide). */
require __DIR__ . '/../layout-header.php';
?>
<style>
  .g-ask{display:flex;gap:6px;}
  .g-ask input{flex:1;min-width:0;border:1px solid #d1d5db;border-radius:8px;padding:8px 10px;font-size:14px;}
  .g-answer{border:1px solid var(--ke-green);border-radius:8px;background:#fff;padding:10px 12px;}
  .g-sec{border:1px solid var(--ke-line,#e5e7eb);border-radius:8px;background:#fff;}
  .g-sec summary{cursor:pointer;padding:8px 12px;font-weight:700;font-size:14px;}
  .g-sec .g-body{padding:0 12px 10px;font-size:13.5px;color:#374151;}
  .g-sec .g-body p+p{margin-top:6px;}
  .g-where{font-size:11px;font-weight:700;text-transform:uppercase;color:var(--ke-green);margin-bottom:4px;}
  mark{background:#fff4c2;}
</style>
<div class="max-w-3xl space-y-3">
  <form class="g-ask" id="gAsk" role="search">
    <input id="gQ" type="search" placeholder="Ask anything, e.g. how do I download completed students for one branch?" aria-label="Your question" autocomplete="off">
    <button class="btn-primary">Ask</button>
  </form>
  <div id="gAnswer" hidden></div>

  <div class="space-y-1.5" id="gList">
    <?php foreach ($sections as $s): ?>
      <details class="g-sec" id="g-<?= e($s['id']) ?>">
        <summary><?= e($s['title']) ?></summary>
        <div class="g-body">
          <p class="g-where">Where: <?= e($s['where']) ?></p>
          <?php foreach ($s['body'] as $p): ?><p><?= e($p) ?></p><?php endforeach; ?>
        </div>
      </details>
    <?php endforeach; ?>
  </div>
</div>
<script>
(function () {
  var sections = <?= json_encode($sections, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  var stop = 'a an the i we you he she it is are was do does did how what where when why who which can could to of for in on at by with my me our his her their this that and or be get see show'.split(' ');
  function words(t) {
    return (t.toLowerCase().match(/[a-z0-9]+/g) || []).filter(function (w) { return w.length > 1 && stop.indexOf(w) < 0; })
      .map(function (w) { return w.replace(/(ies)$/, 'y').replace(/(es|s)$/, ''); });
  }
  // Score each paragraph: words in the title/keywords count most, then the text.
  function rank(q) {
    var qs = words(q), hits = [];
    sections.forEach(function (s) {
      var head = words(s.title + ' ' + s.keywords + ' ' + s.where);
      s.body.forEach(function (p, i) {
        var text = words(p), score = 0;
        qs.forEach(function (w) {
          if (head.indexOf(w) >= 0) score += 2;
          text.forEach(function (t) { if (t === w) score += 1.5; else if (w.length > 3 && t.indexOf(w) === 0) score += 0.5; });
        });
        if (score > 0) hits.push({ s: s, p: p, score: score + (i === 0 ? 0.3 : 0) });
      });
    });
    return hits.sort(function (a, b) { return b.score - a.score; });
  }
  function esc(t) { return t.replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function highlight(p, q) {
    var out = esc(p);
    words(q).forEach(function (w) { if (w.length > 2) out = out.replace(new RegExp('\\b(' + w + '[a-z]*)', 'gi'), '<mark>$1</mark>'); });
    return out;
  }
  document.getElementById('gAsk').addEventListener('submit', function (e) {
    e.preventDefault();
    var q = document.getElementById('gQ').value.trim(), box = document.getElementById('gAnswer');
    if (!q) { box.hidden = true; return; }
    var hits = rank(q).slice(0, 4);
    if (!hits.length) {
      box.innerHTML = '<div class="g-answer text-sm">No answer found. Try other words (e.g. "report", "payments", "menu", "duplicate"), or open a topic below.</div>';
    } else {
      var top = hits[0].s, seen = {}, more = [];
      hits.slice(1).forEach(function (h) { if (h.s.id !== top.id && !seen[h.s.id]) { seen[h.s.id] = 1; more.push(h.s); } });
      var paras = hits.filter(function (h) { return h.s.id === top.id; }).map(function (h) { return '<p class="mt-1">' + highlight(h.p, q) + '</p>'; }).join('');
      box.innerHTML = '<div class="g-answer text-sm"><p class="g-where">' + esc(top.title) + ' · where: ' + esc(top.where) + '</p>' + paras +
        (more.length ? '<p class="mt-2 text-xs text-neutral-500">Related: ' + more.map(function (s) { return '<a href="#g-' + s.id + '" class="underline" onclick="document.getElementById(\'g-' + s.id + '\').open=true">' + esc(s.title) + '</a>'; }).join(' · ') + '</p>' : '') + '</div>';
    }
    box.hidden = false;
  });
})();
</script>
<?php require __DIR__ . '/../layout-footer.php'; ?>
