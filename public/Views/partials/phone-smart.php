<script>
// Phone boxes know the country: a local Kenyan number (07… / 01…) becomes
// +2547… / +2541…, "254…" gets its "+", and the country shows underneath.
// Works on every type="tel" box, boxes named phone, and [data-phone-smart]
// (email-or-phone boxes: only acts once what's typed looks like a number).
(function () {
  var COUNTRIES = { '254': '🇰🇪 Kenya', '255': '🇹🇿 Tanzania', '256': '🇺🇬 Uganda', '250': '🇷🇼 Rwanda', '257': '🇧🇮 Burundi',
    '211': '🇸🇸 South Sudan', '251': '🇪🇹 Ethiopia', '252': '🇸🇴 Somalia', '243': '🇨🇩 DR Congo', '234': '🇳🇬 Nigeria',
    '233': '🇬🇭 Ghana', '27': '🇿🇦 South Africa', '44': '🇬🇧 United Kingdom', '1': '🇺🇸 USA / Canada', '971': '🇦🇪 UAE', '91': '🇮🇳 India' };
  function isPhoneBox(el) {
    return el && el.tagName === 'INPUT' && (el.type === 'tel' || /(^|\[|_)phone/i.test(el.name || '') || el.hasAttribute('data-phone-smart'));
  }
  function looksLikePhone(v) { return /^[+\d][\d\s\-()]*$/.test(v) && v.replace(/\D/g, '').length >= 2; }
  function normalise(v) {
    var d = v.replace(/[^\d+]/g, '');
    if (/^0[17]\d/.test(d)) return '+254' + d.slice(1);           // 07… / 01… → Kenya
    if (/^254[17]\d/.test(d)) return '+' + d;                       // 2547… → +2547…
    if (/^[17]\d{8}$/.test(d)) return '+254' + d;                   // 712345678 → +254712345678
    return d;
  }
  function country(v) {
    if (v.charAt(0) !== '+') return '';
    var d = v.slice(1);
    for (var len = 3; len >= 1; len--) { if (COUNTRIES[d.slice(0, len)]) return COUNTRIES[d.slice(0, len)]; }
    return '';
  }
  function hint(el) {
    var tip = el.parentNode.querySelector(':scope > .phone-country');
    if (!tip) { tip = document.createElement('span'); tip.className = 'phone-country'; el.insertAdjacentElement('afterend', tip); }
    return tip;
  }
  function update(el, final) {
    var v = el.value.trim();
    var tip = el.parentNode.querySelector(':scope > .phone-country');
    if (!looksLikePhone(v)) { if (tip) tip.textContent = ''; return; }
    var n = normalise(v);
    if (final || /^0[17]\d|^254/.test(v.replace(/\D/g, ''))) {
      if (n !== v && (final || n.length > v.length || /^0/.test(v))) {
        var atEnd = el.selectionStart === v.length;
        el.value = n;
        if (atEnd) { try { el.setSelectionRange(n.length, n.length); } catch (e) {} }
      }
    }
    hint(el).textContent = country(el.value);
  }
  document.addEventListener('input', function (e) { if (isPhoneBox(e.target)) update(e.target, false); });
  document.addEventListener('blur', function (e) { if (isPhoneBox(e.target)) update(e.target, true); }, true);
  document.addEventListener('submit', function (e) {
    e.target.querySelectorAll('input').forEach(function (el) { if (isPhoneBox(el) && looksLikePhone(el.value.trim())) el.value = normalise(el.value.trim()); });
  }, true);
  document.querySelectorAll('input').forEach(function (el) { if (isPhoneBox(el) && el.value) update(el, false); });
})();
</script>
<style>.phone-country{display:block;margin-top:.25rem;font-size:.72rem;font-weight:700;color:#6b7280;min-height:0}.phone-country:empty{display:none}</style>
