  </main>

  <footer class="border-t py-6 text-center text-[11px] font-semibold" style="border-color:var(--ke-line);color:var(--ke-muted)">
    &copy; <?= date('Y') ?> <?= e(appName()) ?>. Learning management system.
  </footer>
  <script>
  (function () {
    function reindexBranchRows(holder, key) {
      holder.querySelectorAll('.branch-row').forEach(function (row, index) {
        row.querySelectorAll('[name]').forEach(function (input) {
          input.name = input.name.replace(/answers\[[^\]]+\]\[(?:__INDEX__|\d+)\]/, 'answers[' + key + '][' + index + ']');
        });
      });
    }
    document.addEventListener('click', function (event) {
      var addBtn = event.target.closest('.add-branch-row');
      if (addBtn) {
        event.preventDefault();
        var wrap = addBtn.closest('.js-branch-field');
        var holder = wrap ? wrap.querySelector('[data-branches]') : null;
        var template = wrap ? wrap.querySelector('.branch-row-template') : null;
        if (!holder || !template) return;
        var key = addBtn.getAttribute('data-branches-add') || holder.getAttribute('data-branches');
        var html = template.innerHTML.replace(/__INDEX__/g, String(holder.querySelectorAll('.branch-row').length));
        holder.insertAdjacentHTML('beforeend', html);
        reindexBranchRows(holder, key);
        return;
      }
      var removeBtn = event.target.closest('.remove-branch-row');
      if (removeBtn) {
        event.preventDefault();
        var wrap = removeBtn.closest('.js-branch-field');
        var holder = wrap ? wrap.querySelector('[data-branches]') : removeBtn.closest('[data-branches]');
        if (!holder || holder.querySelectorAll('.branch-row').length === 1) return;
        removeBtn.closest('.branch-row').remove();
        reindexBranchRows(holder, holder.getAttribute('data-branches'));
      }
    });
  })();
  </script>
</body>
</html>
