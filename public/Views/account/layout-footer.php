  </main>

  <footer class="border-t py-6 text-center text-[11px] font-semibold" style="border-color:var(--ke-line);color:var(--ke-muted)">
    &copy; <?= date('Y') ?> <?= e(appName()) ?>. Learning management system.
  </footer>
  <?php if ($loggedInUser): ?></div></div><?php endif; ?>
  <script>
  (function () {
    var sidebar = document.getElementById('srmsSidebar');
    var backdrop = document.getElementById('srmsSidebarBackdrop');
    var toggle = document.getElementById('srmsMenuToggle');
    var closeBtn = document.getElementById('srmsSidebarClose');
    function openSidebar() {
      if (sidebar) sidebar.classList.add('is-open');
      if (backdrop) backdrop.classList.add('is-open');
      document.body.classList.add('srms-nav-open');
    }
    function closeSidebar() {
      if (sidebar) sidebar.classList.remove('is-open');
      if (backdrop) backdrop.classList.remove('is-open');
      document.body.classList.remove('srms-nav-open');
    }
    if (toggle && sidebar) {
      toggle.addEventListener('click', function () {
        if (sidebar.classList.contains('is-open')) closeSidebar(); else openSidebar();
      });
    }
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (backdrop) backdrop.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeSidebar();
    });
    if (sidebar) {
      sidebar.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', closeSidebar);
      });
    }
    window.addEventListener('resize', function () {
      if (window.innerWidth > 900) closeSidebar();
    });
  })();
  (function () {
    function escapeHtml(value) {
      var div = document.createElement('div');
      div.textContent = String(value == null ? '' : value);
      return div.innerHTML;
    }

    function selectedOrganisationIds(form) {
      var ids = [];
      form.querySelectorAll('.js-organisation-value').forEach(function (field) {
        var value = field.value;
        if (value && ids.indexOf(value) === -1) ids.push(value);
      });
      return ids;
    }

    function selectedAttachmentProviderIds(form) {
      var ids = [];
      form.querySelectorAll('.js-attachment-provider-value').forEach(function (field) {
        var value = field.value;
        if (value && ids.indexOf(value) === -1) ids.push(value);
      });
      return ids;
    }

    function selectedLabel(input) {
      return input.getAttribute('data-label') || ('Organisation #' + input.value);
    }

    function renderSelected(box) {
      var holder = box.querySelector('.js-org-selected');
      if (!holder) return;
      var values = Array.prototype.slice.call(box.querySelectorAll('.js-organisation-value')).filter(function (input) {
        return input.value !== '';
      });
      holder.innerHTML = values.map(function (input) {
        return '<span class="inline-flex items-center gap-2 rounded-full px-2 py-1 text-xs font-bold" style="background:#e8f5ee;color:var(--ke-green-dark);border:1px solid var(--ke-green)">' + escapeHtml(selectedLabel(input)) + '<button type="button" class="js-org-remove font-black" data-id="' + escapeHtml(input.value) + '" aria-label="Remove organisation">x</button></span>';
      }).join('');
    }

    function initialiseOrganisationSearch(box) {
      var input = box.querySelector('.js-org-search-input');
      var multiple = box.getAttribute('data-multiple') === '1';
      var selected = box.querySelector('.js-organisation-value[value]:not([value=""])');
      if (input && !multiple && selected) input.value = selectedLabel(selected);
      renderSelected(box);
    }

    function setOrganisation(box, id, label) {
      var multiple = box.getAttribute('data-multiple') === '1';
      var name = box.getAttribute('data-name');
      var values = box.querySelector('.js-org-values');
      if (!values || !name || !id) return;
      if (!multiple) {
        values.innerHTML = '<input type="hidden" name="' + escapeHtml(name) + '" value="' + escapeHtml(id) + '" class="js-organisation-field js-organisation-value" data-label="' + escapeHtml(label) + '" />';
      } else if (!values.querySelector('.js-organisation-value[value="' + id + '"]')) {
        values.insertAdjacentHTML('beforeend', '<input type="hidden" name="' + escapeHtml(name) + '[]" value="' + escapeHtml(id) + '" class="js-organisation-field js-organisation-value" data-label="' + escapeHtml(label) + '" />');
      }
      var input = box.querySelector('.js-org-search-input');
      var results = box.querySelector('.js-org-results');
      if (input) input.value = multiple ? '' : label;
      if (results) results.classList.add('hidden');
      renderSelected(box);
      refreshAllBranchSelects(box.closest('form') || document);
    }

    function searchOrganisations(box) {
      var input = box.querySelector('.js-org-search-input');
      var results = box.querySelector('.js-org-results');
      var url = box.getAttribute('data-search-url');
      if (!input || !results || !url) return;
      fetch(url + '?q=' + encodeURIComponent(input.value || ''), { headers: { Accept: 'application/json' } })
        .then(function (response) { return response.json(); })
        .then(function (payload) {
          var items = payload.items || [];
          if (!items.length) {
            results.innerHTML = '<p class="p-3 text-sm font-bold" style="color:var(--ke-muted)">No organisations found.</p>';
          } else {
            results.innerHTML = items.map(function (item) {
              return '<button type="button" class="block w-full px-3 py-2 text-left text-sm font-semibold hover:bg-neutral-50 js-org-option" data-id="' + escapeHtml(item.id) + '" data-label="' + escapeHtml(item.name) + '">' + escapeHtml(item.name) + '</button>';
            }).join('');
          }
          results.classList.remove('hidden');
        })
        .catch(function () {
          results.innerHTML = '<p class="p-3 text-sm font-bold" style="color:var(--ke-red)">Organisation search failed. Try again.</p>';
          results.classList.remove('hidden');
        });
    }

    function refreshBranchSelect(box) {
      var form = box.closest('form');
      if (!form) return;
      var ids = selectedOrganisationIds(form);
      var providerIds = selectedAttachmentProviderIds(form);
      var defaultOrgId = box.getAttribute('data-default-org-id');
      if (!ids.length && defaultOrgId) ids = [defaultOrgId];
      var defaultProviderId = box.getAttribute('data-default-provider-id');
      if (!providerIds.length && defaultProviderId) providerIds = [defaultProviderId];
      var checked = box.querySelector('input:checked') || box.querySelector('input[type=hidden]');
      var current = checked ? checked.value : (box.getAttribute('data-current') || '');
      var name = box.getAttribute('data-name');
      var required = box.getAttribute('data-required') === '1';
      var url = box.getAttribute('data-branches-url');
      if (!url || (!ids.length && !providerIds.length)) {
        box.innerHTML = '<p class="text-sm font-bold" style="color:var(--ke-muted)">Choose an organisation first to see its branches.</p>';
        return;
      }
      fetch(url + '?organisation_ids=' + encodeURIComponent(ids.join(',')) + '&attachment_provider_ids=' + encodeURIComponent(providerIds.join(',')), { headers: { Accept: 'application/json' } })
        .then(function (response) { return response.json(); })
        .then(function (payload) {
          var items = payload.items || [];
          if (!items.length) {
            box.innerHTML = '<p class="text-sm font-bold" style="color:var(--ke-muted)">No branches saved for this organisation.</p>';
            return;
          }
          box.innerHTML = items.map(function (item, i) {
            var lines = '';
            if (item.location) lines += '<span class="block text-xs font-semibold text-neutral-600">📍 ' + escapeHtml(item.location) + '</span>';
            if (item.admin_name) lines += '<span class="block text-xs font-semibold text-neutral-600">Branch admin: ' + escapeHtml(item.admin_name) + '</span>';
            if (item.phone) lines += '<span class="block text-xs font-semibold text-neutral-600">📞 ' + escapeHtml(item.phone) + '</span>';
            if (item.email) lines += '<span class="block break-all text-xs font-semibold text-neutral-600">✉️ ' + escapeHtml(item.email) + '</span>';
            return '<label class="branch-pick">'
              + '<input type="radio" name="' + escapeHtml(name) + '" value="' + escapeHtml(item.id) + '"' + (String(item.id) === String(current) ? ' checked' : '') + (required && i === 0 ? ' required' : '') + '>'
              + '<span class="min-w-0"><span class="block text-sm font-bold text-gray-800">' + escapeHtml(item.title) + '</span>'
              + (item.organisation_name ? '<span class="block text-[11px] font-bold uppercase text-neutral-500">' + escapeHtml(item.organisation_name) + '</span>' : '')
              + lines + '</span></label>';
          }).join('');
        })
        .catch(function () {
          box.innerHTML = '<p class="text-sm font-bold" style="color:var(--ke-red)">Could not load branches. Try again.</p>';
        });
    }

    function refreshAllBranchSelects(root) {
      (root || document).querySelectorAll('.js-branch-select').forEach(refreshBranchSelect);
    }

    document.addEventListener('change', function (event) {
      if (event.target.classList.contains('js-organisation-field') || event.target.classList.contains('js-attachment-provider-value')) {
        var form = event.target.closest('form');
        refreshAllBranchSelects(form || document);
      }
    });
    function renderProvider(box) {
      var holder = box.querySelector('.js-provider-selected');
      var value = box.querySelector('.js-attachment-provider-value');
      if (!holder || !value || !value.value) {
        if (holder) holder.innerHTML = '';
        return;
      }
      holder.innerHTML = '<span class="inline-flex items-center gap-2 rounded-full px-2 py-1 text-xs font-bold" style="background:#e8f5ee;color:var(--ke-green-dark);border:1px solid var(--ke-green)">' + escapeHtml(value.getAttribute('data-label') || ('Provider #' + value.value)) + '<button type="button" class="js-provider-remove font-black" aria-label="Remove attachment provider">x</button></span>';
    }
    function searchProviders(box) {
      var input = box.querySelector('.js-provider-search-input');
      var results = box.querySelector('.js-provider-results');
      var url = box.getAttribute('data-search-url');
      if (!input || !results || !url) return;
      fetch(url + '?q=' + encodeURIComponent(input.value || ''), { headers: { Accept: 'application/json' } })
        .then(function (response) { return response.json(); })
        .then(function (payload) {
          var items = payload.items || [];
          results.innerHTML = items.length ? items.map(function (item) {
            var suffix = item.organisation_name ? ' - ' + item.organisation_name : '';
            return '<button type="button" class="block w-full px-3 py-2 text-left text-sm font-semibold hover:bg-neutral-50 js-provider-option" data-id="' + escapeHtml(item.id) + '" data-label="' + escapeHtml(item.name) + '">' + escapeHtml(item.name + suffix) + '</button>';
          }).join('') : '<p class="p-3 text-sm font-bold" style="color:var(--ke-muted)">No attachment providers found.</p>';
          results.classList.remove('hidden');
        });
    }
    document.addEventListener('input', function (event) {
      if (!event.target.classList.contains('js-provider-search-input')) return;
      var box = event.target.closest('.js-provider-search');
      if (box) {
        clearTimeout(box.__providerTimer);
        box.__providerTimer = setTimeout(function () { searchProviders(box); }, 250);
      }
    });
    document.addEventListener('focusin', function (event) {
      if (event.target.classList.contains('js-provider-search-input')) searchProviders(event.target.closest('.js-provider-search'));
    });
    document.addEventListener('click', function (event) {
      var option = event.target.closest('.js-provider-option');
      if (option) {
        var box = option.closest('.js-provider-search');
        var value = box.querySelector('.js-attachment-provider-value');
        value.value = option.getAttribute('data-id');
        value.setAttribute('data-label', option.getAttribute('data-label'));
        box.querySelector('.js-provider-search-input').value = option.getAttribute('data-label');
        box.querySelector('.js-provider-results').classList.add('hidden');
        renderProvider(box);
        refreshAllBranchSelects(box.closest('form') || document);
        return;
      }
      var removeProvider = event.target.closest('.js-provider-remove');
      if (removeProvider) {
        var providerBox = removeProvider.closest('.js-provider-search');
        providerBox.querySelector('.js-attachment-provider-value').value = '';
        providerBox.querySelector('.js-provider-search-input').value = '';
        renderProvider(providerBox);
        refreshAllBranchSelects(providerBox.closest('form') || document);
      }
    });
    document.addEventListener('input', function (event) {
      if (!event.target.classList.contains('js-org-search-input')) return;
      var box = event.target.closest('.js-org-search');
      if (!box) return;
      if (box.getAttribute('data-multiple') !== '1' && event.target.value === '') {
        box.querySelectorAll('.js-organisation-value').forEach(function (input) { input.remove(); });
        renderSelected(box);
        refreshAllBranchSelects(box.closest('form') || document);
      }
      clearTimeout(box.__orgSearchTimer);
      box.__orgSearchTimer = setTimeout(function () { searchOrganisations(box); }, 250);
    });
    document.addEventListener('focusin', function (event) {
      if (!event.target.classList.contains('js-org-search-input')) return;
      var box = event.target.closest('.js-org-search');
      if (box) searchOrganisations(box);
    });
    document.addEventListener('click', function (event) {
      var option = event.target.closest('.js-org-option');
      if (option) {
        var box = option.closest('.js-org-search');
        if (box) setOrganisation(box, option.getAttribute('data-id'), option.getAttribute('data-label'));
        return;
      }
      var remove = event.target.closest('.js-org-remove');
      if (remove) {
        var searchBox = remove.closest('.js-org-search');
        if (!searchBox) return;
        searchBox.querySelectorAll('.js-organisation-value').forEach(function (input) {
          if (input.value === remove.getAttribute('data-id')) input.remove();
        });
        var textInput = searchBox.querySelector('.js-org-search-input');
        if (textInput && searchBox.getAttribute('data-multiple') !== '1') textInput.value = '';
        renderSelected(searchBox);
        refreshAllBranchSelects(searchBox.closest('form') || document);
        return;
      }
      document.querySelectorAll('.js-org-results').forEach(function (results) {
        if (!results.contains(event.target) && !event.target.classList.contains('js-org-search-input')) {
          results.classList.add('hidden');
        }
      });
    });
    document.querySelectorAll('.js-org-search').forEach(initialiseOrganisationSearch);
    document.querySelectorAll('.js-provider-search').forEach(renderProvider);
    refreshAllBranchSelects(document);
  })();

  (function () {
    document.addEventListener('click', function (event) {
      var removeBtn = event.target.closest('.category-chip-remove');
      if (!removeBtn) return;
      var picker = removeBtn.closest('.category-picker');
      var chip = removeBtn.closest('.category-chip');
      if (!picker || !chip) return;
      var id = chip.getAttribute('data-id');
      var all = [];
      try { all = JSON.parse(picker.getAttribute('data-all') || '[]'); } catch (e) { all = []; }
      var match = all.find(function (cat) { return String(cat.id) === String(id); });
      var select = picker.querySelector('.category-add');
      if (select && match) {
        var option = document.createElement('option');
        option.value = match.id;
        option.textContent = match.name;
        select.appendChild(option);
      }
      chip.remove();
      var chips = picker.querySelector('.category-chips');
      if (chips && !chips.querySelector('.category-chip')) {
        var hint = document.createElement('span');
        hint.className = 'text-xs font-bold text-neutral-500 category-empty-hint';
        hint.textContent = 'No categories selected.';
        chips.appendChild(hint);
      }
    });

    document.addEventListener('change', function (event) {
      var select = event.target.closest('.category-add');
      if (!select || !select.value) return;
      var picker = select.closest('.category-picker');
      if (!picker) return;
      var all = [];
      try { all = JSON.parse(picker.getAttribute('data-all') || '[]'); } catch (e) { all = []; }
      var match = all.find(function (cat) { return String(cat.id) === String(select.value); });
      if (!match) return;
      var chips = picker.querySelector('.category-chips');
      var hint = chips.querySelector('.category-empty-hint');
      if (hint) hint.remove();
      var chip = document.createElement('span');
      chip.className = 'category-chip inline-flex items-center gap-1 rounded-full border border-neutral-200 bg-neutral-50 px-2 py-1 text-[11px] font-bold whitespace-nowrap';
      chip.setAttribute('data-id', match.id);
      chip.innerHTML = match.name.replace(/[<>&]/g, function (c) { return { '<': '&lt;', '>': '&gt;', '&': '&amp;' }[c]; })
        + ' <button type="button" class="category-chip-remove font-black text-neutral-500 hover:text-rose-600" aria-label="Remove">×</button>'
        + '<input type="hidden" name="category_ids[]" value="' + match.id + '" />';
      chips.appendChild(chip);
      select.querySelector('option[value="' + select.value + '"]').remove();
      select.value = '';
    });
  })();
  </script>
<script src="<?= asset('assets/js/app-shell.js') ?>" defer></script>
<?php require __DIR__ . '/../partials/image-cropper.php'; ?>
</body>
</html>
