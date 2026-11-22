<?php
/**
 * The one page that can run before Composer's autoloader exists at all —
 * so it is a plain script with no App\ classes, no namespace, nothing from
 * vendor/. public/index.php requires this file first; if vendor/autoload.php
 * is already there, vendorGateCheck() returns immediately and nothing below
 * ever runs.
 *
 * A shared-hosting upload (FTP/cPanel File Manager) of vendor/ as thousands
 * of small files fails or times out on a lot of hosts. This page lets you
 * upload one file instead — a zip of vendor/'s own contents — and extracts
 * it in place. Once vendor/autoload.php exists, every later request skips
 * this file entirely; there is nothing to turn off or forget about.
 */

/** Call once from public/index.php, before requiring vendor/autoload.php. */
function vendorGateCheck(string $vendorDir): void
{
    if (is_file($vendorDir . '/autoload.php')) {
        return; // normal case on every request once this has run once
    }

    header('Content-Type: text/html; charset=utf-8');

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !empty($_FILES['vendor_zip'])) {
        vendorGateHandleUpload($vendorDir);
        return; // vendorGateHandleUpload() always exits
    }

    vendorGateRender($vendorDir, '');
    exit;
}

function vendorGateHandleUpload(string $vendorDir): void
{
    $file = $_FILES['vendor_zip'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        vendorGateRender($vendorDir, 'The upload did not go through. Try again — if the file is large, your host may need a bigger upload limit.');
        exit;
    }
    if (!preg_match('/\.zip$/i', (string) ($file['name'] ?? ''))) {
        vendorGateRender($vendorDir, 'That is not a .zip file.');
        exit;
    }
    if (!class_exists('ZipArchive')) {
        vendorGateRender($vendorDir, 'This PHP has no "zip" extension, so it cannot open the archive here. Ask your host to enable it, or upload the vendor files individually instead.');
        exit;
    }

    $zip = new ZipArchive();
    if ($zip->open($file['tmp_name']) !== true) {
        vendorGateRender($vendorDir, 'That file is not a valid zip archive.');
        exit;
    }

    // Guard against a zip entry escaping vendor/ (e.g. "../../something").
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if ($name === false || str_contains($name, '..') || str_starts_with($name, '/')) {
            $zip->close();
            vendorGateRender($vendorDir, 'That zip has an unsafe file path in it (' . htmlspecialchars((string) $name) . '). Rebuild it from vendor/\'s own contents and try again.');
            exit;
        }
    }

    if (!is_dir($vendorDir) && !@mkdir($vendorDir, 0775, true)) {
        $zip->close();
        vendorGateRender($vendorDir, 'Could not create ' . htmlspecialchars($vendorDir) . '. Check the folder above it is writable.');
        exit;
    }

    $ok = $zip->extractTo($vendorDir);
    $zip->close();

    if (!$ok) {
        vendorGateRender($vendorDir, 'Extraction failed — check the web server user can write to ' . htmlspecialchars($vendorDir) . '.');
        exit;
    }
    if (!is_file($vendorDir . '/autoload.php')) {
        vendorGateRender($vendorDir, 'Extracted, but there is still no autoload.php at the top of vendor/. Make sure the zip holds vendor/\'s contents directly (autoload.php, composer/, phpmailer/ …), not vendor/ itself as a sub-folder.');
        exit;
    }

    // Success: vendor/autoload.php exists now, so every request after this
    // one (including a refresh of this very page) takes the normal path.
    header('Location: ' . ($_SERVER['REQUEST_URI'] ?? '/'));
    exit;
}

function vendorGateRender(string $vendorDir, string $error): void
{
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install the vendor files</title>
<style>
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f5f7f9;margin:0;padding:24px;color:#1f2a24}
  .box{max-width:560px;margin:6vh auto;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:32px}
  h1{font-size:20px;margin:0 0 6px}
  p{color:#4b5563;line-height:1.6;margin:0 0 14px;font-size:14px}
  .err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:10px 14px;border-radius:10px;font-size:13px;margin-bottom:16px}
  ol{color:#4b5563;line-height:1.7;font-size:14px;padding-left:20px;margin:0 0 20px}
  code{background:#f1f5f2;padding:2px 6px;border-radius:5px;font-size:12.5px}
  input[type=file]{display:block;width:100%;margin-bottom:16px;font-size:14px}
  button{background:#006b3f;color:#fff;border:0;padding:11px 18px;border-radius:9px;font-weight:700;font-size:14px;cursor:pointer}
  button:hover{background:#00502f}
</style>
</head>
<body>
<div class="box">
  <h1>One more file before this site can run</h1>
  <p>
    <code>vendor/</code> (the libraries this app needs — just PHPMailer) is missing, and uploading it as thousands of
    small files over FTP or a file manager is where that usually goes wrong. Upload it as one zip instead and it is
    put in place automatically.
  </p>
  <?php if ($error !== ''): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <ol>
    <li>On the computer that has this project, zip the <strong>contents</strong> of its <code>vendor/</code> folder —
      so the zip's top level is <code>autoload.php</code>, <code>composer/</code>, <code>phpmailer/</code>, not a
      <code>vendor</code> folder containing those.</li>
    <li>Upload that one zip below.</li>
  </ol>
  <form method="post" enctype="multipart/form-data">
    <input type="file" name="vendor_zip" accept=".zip" required>
    <button type="submit">Upload &amp; install</button>
  </form>
  <p style="margin-top:18px;font-size:12px;color:#9ca3af">This page only appears while vendor/autoload.php is missing — once it's in place, every page (including this one) works as normal and this screen never shows again.</p>
</div>
</body>
</html>
<?php
}
