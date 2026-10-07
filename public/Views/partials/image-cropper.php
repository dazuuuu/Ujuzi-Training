<?php
/**
 * Crop before upload: any <input type="file" data-crop> opens a square crop
 * step (drag, zoom, rotate) when a picture is chosen; the cropped picture
 * replaces the chosen file, so the form uploads it as usual. data-crop="16/9"
 * etc. sets another shape.
 */
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js" defer></script>
<div class="crop-modal" id="crop-modal" hidden role="dialog" aria-modal="true" aria-labelledby="crop-title">
  <div class="crop-box">
    <h2 id="crop-title" class="text-base font-black">Crop your picture</h2>
    <p class="text-xs font-semibold text-neutral-500">Drag to move, scroll or pinch to zoom.</p>
    <div class="crop-stage"><img id="crop-image" alt=""></div>
    <div class="crop-actions">
      <button type="button" class="btn-secondary" data-crop-act="rotate">↻ Rotate</button>
      <button type="button" class="btn-secondary" data-crop-act="cancel">Cancel</button>
      <button type="button" class="btn-primary" data-crop-act="done">Use picture</button>
    </div>
  </div>
</div>
<style>
  .crop-modal{position:fixed;inset:0;z-index:400;display:flex;align-items:center;justify-content:center;padding:12px;background:rgba(15,23,42,.55);}
  .crop-modal[hidden]{display:none;}
  .crop-box{display:flex;flex-direction:column;gap:10px;width:min(520px,100%);max-height:100%;padding:16px;border-radius:16px;background:#fff;}
  .crop-stage{height:min(60vh,380px);background:#111;border-radius:10px;overflow:hidden;}
  .crop-stage img{display:block;max-width:100%;}
  .crop-actions{display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;}
</style>
<script>
(function () {
  var modal = document.getElementById('crop-modal');
  var img = document.getElementById('crop-image');
  var cropper = null, input = null, original = null;

  function close(keep) {
    if (cropper) { cropper.destroy(); cropper = null; }
    modal.hidden = true;
    if (!keep && input) input.value = '';
  }

  document.addEventListener('change', function (e) {
    var el = e.target;
    if (!el.matches || !el.matches('input[type=file][data-crop]') || !el.files[0] || el.dataset.cropping === '1') return;
    if (!window.Cropper || !/^image\//.test(el.files[0].type)) return; // upload as it is
    input = el; original = el.files[0];
    var ratio = (el.getAttribute('data-crop') || '1').split('/');
    img.src = URL.createObjectURL(original);
    modal.hidden = false;
    img.onload = function () {
      cropper = new Cropper(img, { aspectRatio: ratio.length === 2 ? (+ratio[0] / +ratio[1]) : 1, viewMode: 1, autoCropArea: 1, background: false });
    };
  });

  modal.addEventListener('click', function (e) {
    var act = (e.target.closest('[data-crop-act]') || {}).getAttribute ? e.target.closest('[data-crop-act]').getAttribute('data-crop-act') : null;
    if (act === 'rotate' && cropper) { cropper.rotate(90); }
    if (act === 'cancel') { close(false); }
    if (act === 'done' && cropper) {
      cropper.getCroppedCanvas({ width: 800, height: 800 * (cropper.getData().height / cropper.getData().width), imageSmoothingQuality: 'high' }).toBlob(function (blob) {
        var file = new File([blob], (original.name || 'picture').replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' });
        var dt = new DataTransfer();
        dt.items.add(file);
        input.dataset.cropping = '1';
        input.files = dt.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
        input.dataset.cropping = '';
        // Show the cropped picture beside the field when there is one.
        var preview = input.closest('form') && input.closest('form').querySelector('img');
        if (preview) preview.src = URL.createObjectURL(file);
        close(true);
      }, 'image/jpeg', 0.9);
    }
  });
})();
</script>
