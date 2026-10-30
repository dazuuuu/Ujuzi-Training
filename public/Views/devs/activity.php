<?php
/** Standalone dark console — Super Admin activity. Requires $rows, $total, $page, $perPage. */
$rows = $rows ?? [];
$total = (int) ($total ?? 0);
$page = (int) ($page ?? 1);
$perPage = (int) ($perPage ?? 100);
$pages = max(1, (int) ceil($total / max(1, $perPage)));
$ago = static function (string $ts): string {
    $t = strtotime($ts);
    if (!$t) { return $ts; }
    $d = time() - $t;
    if ($d < 60) { return $d . 's ago'; }
    if ($d < 3600) { return floor($d / 60) . 'm ago'; }
    if ($d < 86400) { return floor($d / 3600) . 'h ago'; }
    return date('j M, H:i', $t);
};
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Console · Activity</title>
  <style>
    :root{--bg:#0f1a14;--panel:#16241c;--panel2:#13201a;--line:#27402f;--ink:#e8efe9;--muted:#8fb89d;--accent:#c7a008;}
    *{box-sizing:border-box}
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:var(--bg);margin:0;min-height:100vh;color:var(--ink);padding:24px}
    .wrap{max-width:1000px;margin:3vh auto}
    .top{display:flex;justify-content:space-between;align-items:center;gap:12px;margin:0 0 18px;flex-wrap:wrap}
    .top h1{font-size:15px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);margin:0}
    .nav{display:flex;gap:14px;align-items:center}
    .nav a{color:var(--muted);font-size:13px;text-decoration:none;font-weight:600}
    .nav a.on{color:var(--ink)}
    .nav a:hover{color:var(--ink)}
    .meta{color:var(--muted);font-size:12px;margin:0 0 10px}
    .card{background:var(--panel);border:1px solid var(--line);border-radius:14px;overflow:hidden}
    table{width:100%;border-collapse:collapse;font-size:13px}
    thead th{text-align:left;padding:11px 14px;font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--line);background:var(--panel2)}
    tbody td{padding:10px 14px;border-bottom:1px solid rgba(39,64,47,.6);vertical-align:top}
    tbody tr:last-child td{border-bottom:0}
    tbody tr:hover{background:rgba(255,255,255,.02)}
    .who{font-weight:700}
    .owner{display:inline-block;margin-left:6px;font-size:9px;font-weight:800;text-transform:uppercase;color:#160f00;background:var(--accent);border-radius:99px;padding:1px 6px}
    .m{display:inline-block;font-size:10px;font-weight:800;border-radius:99px;padding:1px 7px}
    .m.get{background:#16304a;color:#8fc0ff}
    .m.post{background:#3a2a16;color:#f0c86a}
    .path{color:var(--muted);font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11px;word-break:break-all}
    .when{color:var(--muted);white-space:nowrap}
    .empty{padding:28px 14px;text-align:center;color:var(--muted)}
    .pager{display:flex;justify-content:space-between;align-items:center;margin-top:12px;color:var(--muted);font-size:13px}
    .pager a{color:var(--ink);text-decoration:none;border:1px solid var(--line);border-radius:8px;padding:6px 12px;font-weight:700}
    .pager a.disabled{opacity:.35;pointer-events:none}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <h1>Console · Activity</h1>
      <nav class="nav">
        <a href="<?= url('/devs/dashboard') ?>">Dashboard</a>
        <a href="<?= url('/devs/activity') ?>" class="on">Activity</a>
        <a href="<?= url('/devs/logout') ?>">Sign out</a>
      </nav>
    </div>
    <p class="meta">Every page opened and action taken by the website owner and any admins — newest first. <?= number_format($total) ?> event(s) recorded.</p>

    <div class="card">
      <table>
        <thead>
          <tr><th>When</th><th>Who</th><th>Activity</th><th>Request</th><th>IP</th></tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="5" class="empty">No activity recorded yet. It appears here as soon as an admin uses the panel.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td class="when" title="<?= e((string) $r['created_at']) ?>"><?= e($ago((string) $r['created_at'])) ?></td>
              <td>
                <span class="who"><?= e(($r['admin_name'] ?? '') !== '' ? $r['admin_name'] : ($r['admin_email'] ?? 'Unknown')) ?></span>
                <?php if (!empty($r['is_owner'])): ?><span class="owner">Owner</span><?php endif; ?>
                <?php if (($r['admin_name'] ?? '') !== '' && ($r['admin_email'] ?? '') !== ''): ?>
                  <div class="path"><?= e($r['admin_email']) ?></div>
                <?php endif; ?>
              </td>
              <td><?= e((string) ($r['action'] ?? '')) ?></td>
              <td><span class="m <?= strtolower((string) $r['method']) === 'post' ? 'post' : 'get' ?>"><?= e((string) $r['method']) ?></span> <span class="path"><?= e((string) $r['path']) ?></span></td>
              <td class="path"><?= e((string) ($r['ip'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
      <div class="pager">
        <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="<?= url('/devs/activity?page=' . ($page - 1)) ?>">← Newer</a>
        <span>Page <?= $page ?> of <?= $pages ?></span>
        <a class="<?= $page >= $pages ? 'disabled' : '' ?>" href="<?= url('/devs/activity?page=' . ($page + 1)) ?>">Older →</a>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
