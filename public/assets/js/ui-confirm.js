/* In-page confirmation (replaces the browser's confirm() box).
   - Any form or submit button with data-confirm="message" asks first.
   - In the Super Admin panel, anything that deletes data also asks for the
     admin's password (sent as confirm_password and checked on the server). */
(function () {
  var SENSITIVE = /\/(delete|data-cleanup|reset-everything|clear)(\/|$)|\/delete-requests\/\d+$/;
  var needsPassword = !!document.querySelector('meta[name="ui-admin"]');
  var modal;

  function build() {
    if (modal) return modal;
    var css = document.createElement('style');
    css.textContent = '.uic-b{position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:99999;display:none;align-items:center;justify-content:center;padding:16px}'
      + '.uic-b.open{display:flex}.uic-m{background:#fff;color:#111827;border-radius:14px;max-width:440px;width:100%;padding:22px;box-shadow:0 20px 50px rgba(0,0,0,.3);font-family:inherit}'
      + '.uic-m h3{margin:0 0 8px;font-size:16px;font-weight:800}.uic-m p{margin:0 0 14px;font-size:14px;line-height:1.5;color:#374151}'
      + '.uic-m input{width:100%;box-sizing:border-box;border:1px solid #9ca3af;border-radius:8px;padding:10px 12px;font-size:14px;margin-bottom:6px}'
      + '.uic-e{color:#b91c1c;font-size:12px;min-height:16px;margin-bottom:8px}.uic-r{display:flex;gap:8px;justify-content:flex-end}'
      + '.uic-r button{border-radius:8px;padding:9px 16px;font-size:13px;font-weight:700;cursor:pointer;border:1px solid #d1d5db;background:#fff}'
      + '.uic-r .ok{background:#166534;border-color:#166534;color:#fff}.uic-r .ok.danger{background:#b91c1c;border-color:#b91c1c}';
    document.head.appendChild(css);
    modal = document.createElement('div');
    modal.className = 'uic-b';
    modal.innerHTML = '<div class="uic-m" role="dialog" aria-modal="true"><h3></h3><p></p>'
      + '<input type="password" placeholder="Your password" autocomplete="current-password" style="display:none">'
      + '<div class="uic-e"></div><div class="uic-r"><button type="button" class="cancel">Cancel</button><button type="button" class="ok">Continue</button></div></div>';
    document.body.appendChild(modal);
    return modal;
  }

  function ask(message, withPassword, danger, done) {
    var m = build(), input = m.querySelector('input'), err = m.querySelector('.uic-e'), ok = m.querySelector('.ok');
    m.querySelector('h3').textContent = withPassword ? 'Confirm with your password' : 'Please confirm';
    m.querySelector('p').textContent = message;
    input.style.display = withPassword ? '' : 'none';
    input.value = ''; err.textContent = '';
    ok.className = 'ok' + (danger ? ' danger' : '');
    function close(result) {
      m.classList.remove('open');
      ok.onclick = m.querySelector('.cancel').onclick = null; input.onkeydown = null;
      done(result);
    }
    ok.onclick = function () {
      if (withPassword && !input.value) { err.textContent = 'Enter your password to continue.'; input.focus(); return; }
      close(withPassword ? input.value : true);
    };
    m.querySelector('.cancel').onclick = function () { close(null); };
    input.onkeydown = function (e) { if (e.key === 'Enter') { e.preventDefault(); ok.click(); } };
    m.classList.add('open');
    (withPassword ? input : ok).focus();
  }

  function guard(form, submitter, message) {
    var action = form.getAttribute('action') || '';
    var sensitive = needsPassword && SENSITIVE.test(action.split('?')[0]) &&
      !(/delete-requests/.test(action) && submitter && submitter.value !== 'approved');
    if (!message && !sensitive) return false;
    var text = message || 'This permanently deletes data and cannot be undone.';
    ask(text, sensitive, sensitive, function (result) {
      if (!result) return;
      if (sensitive) {
        var f = form.querySelector('input[name="confirm_password"]');
        if (!f) { f = document.createElement('input'); f.type = 'hidden'; f.name = 'confirm_password'; form.appendChild(f); }
        f.value = result;
      }
      form.setAttribute('data-uic-ok', '1');
      if (submitter && submitter.name) {
        var h = document.createElement('input'); h.type = 'hidden'; h.name = submitter.name; h.value = submitter.value; form.appendChild(h);
      }
      HTMLFormElement.prototype.submit.call(form);
    });
    return true;
  }

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement) || form.getAttribute('data-uic-ok')) return;
    var sub = e.submitter || null;
    var msg = (sub && sub.getAttribute('data-confirm')) || form.getAttribute('data-confirm');
    if (guard(form, sub, msg)) e.preventDefault();
  }, true);
})();
