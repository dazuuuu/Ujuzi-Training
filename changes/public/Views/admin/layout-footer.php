    </main>
  </div>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const toggle = document.getElementById('adminMenuToggle');
      const close = document.getElementById('adminSidebarClose');
      const sidebar = document.getElementById('adminSidebar');
      const backdrop = document.getElementById('adminSidebarBackdrop');

      function openSidebar() {
        sidebar.classList.remove('-translate-x-full');
        backdrop.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
      }

      function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        backdrop.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
      }

      if (toggle) toggle.addEventListener('click', openSidebar);
      if (close) close.addEventListener('click', closeSidebar);
      if (backdrop) backdrop.addEventListener('click', closeSidebar);

      // Live "find as you type" search: filters table rows as you type, no submit needed.
      document.querySelectorAll('[data-live-search]').forEach(function (input) {
        var targetSelector = input.getAttribute('data-live-search');
        var scope = input.closest('[data-live-search-scope]') || document;
        var tables = scope.querySelectorAll(targetSelector);
        var emptyStates = [];
        tables.forEach(function (table) {
          var empty = document.createElement('tr');
          empty.className = 'live-search-empty-row';
          empty.style.display = 'none';
          var td = document.createElement('td');
          td.colSpan = table.querySelectorAll('thead tr:last-child th').length || 6;
          td.className = 'live-search-empty';
          td.textContent = 'No matches found.';
          empty.appendChild(td);
          var tbody = table.querySelector('tbody');
          if (tbody) {
            tbody.appendChild(empty);
            emptyStates.push({ tbody: tbody, row: empty });
          }
        });

        input.addEventListener('input', function () {
          var term = input.value.trim().toLowerCase();
          tables.forEach(function (table) {
            var tbody = table.querySelector('tbody');
            if (!tbody) return;
            var visibleCount = 0;
            tbody.querySelectorAll('tr').forEach(function (row) {
              if (row.classList.contains('live-search-empty-row')) return;
              var text = row.textContent.toLowerCase();
              var match = term === '' || text.indexOf(term) !== -1;
              row.style.display = match ? '' : 'none';
              if (match) visibleCount++;
            });
            var state = emptyStates.find(function (s) { return s.tbody === tbody; });
            if (state) state.row.style.display = visibleCount === 0 ? '' : 'none';
          });
          // Also toggle whole role-group sections when every row inside is hidden.
          scope.querySelectorAll('[data-live-search-group]').forEach(function (group) {
            var anyVisible = Array.prototype.some.call(
              group.querySelectorAll('tbody tr:not(.live-search-empty-row)'),
              function (row) { return row.style.display !== 'none'; }
            );
            group.style.display = anyVisible || term === '' ? '' : 'none';
          });
        });
      });
    });
  </script>
<script src="<?= asset('assets/js/app-shell.js') ?>" defer></script>
</body>
</html>
