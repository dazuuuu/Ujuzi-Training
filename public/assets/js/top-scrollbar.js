// Wide tables get a second horizontal scrollbar above them, kept in step
// with the one below, so you can scroll sideways without going to the bottom.
(function () {
  function attach(box) {
    if (box.dataset.topScroll) return;
    box.dataset.topScroll = '1';
    var bar = document.createElement('div');
    bar.className = 'top-scrollbar';
    bar.setAttribute('aria-hidden', 'true');
    bar.style.cssText = 'overflow-x:auto;overflow-y:hidden;height:14px;margin-bottom:2px;';
    var inner = document.createElement('div');
    inner.style.height = '1px';
    bar.appendChild(inner);
    box.parentNode.insertBefore(bar, box);
    bar.addEventListener('scroll', function () { if (box.scrollLeft !== bar.scrollLeft) box.scrollLeft = bar.scrollLeft; });
    box.addEventListener('scroll', function () { if (bar.scrollLeft !== box.scrollLeft) bar.scrollLeft = box.scrollLeft; });
    function size() {
      inner.style.width = box.scrollWidth + 'px';
      bar.style.width = box.clientWidth + 'px';
      bar.style.display = box.scrollWidth > box.clientWidth + 1 ? 'block' : 'none';
    }
    size();
    if (window.ResizeObserver) {
      var ro = new ResizeObserver(size);
      ro.observe(box);
      var table = box.querySelector('table');
      if (table) ro.observe(table);
    } else {
      window.addEventListener('resize', size);
    }
  }
  function scan() {
    document.querySelectorAll('.overflow-x-auto, .table-scroll').forEach(function (box) {
      if (box.querySelector('table')) attach(box);
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan); else scan();
})();
