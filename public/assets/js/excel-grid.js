/*
 * Turns <table data-xl> into a spreadsheet: a name box + formula bar above,
 * a row of column letters (A, B, C…), the first row frozen as headings, row
 * numbers down the left, and a green outline on the clicked cell (arrow keys
 * move it). Optional data-xl-tabs="<html>" is shown as the sheet tabs below.
 */
(function () {
  function letter(n) { var s = ''; n++; while (n > 0) { var m = (n - 1) % 26; s = String.fromCharCode(65 + m) + s; n = Math.floor((n - 1) / 26); } return s; }

  function build(table) {
    if (table.dataset.xlReady) return;
    table.dataset.xlReady = '1';
    var head = table.tHead && table.tHead.rows[0];
    var cols = head ? head.cells.length : (table.rows[0] ? table.rows[0].cells.length : 0);

    // Wrap: book > bar + scroll(table) + tabs.
    var book = document.createElement('div');
    book.className = 'xl-book';
    book.innerHTML = '<div class="xl-bar"><div class="xl-namebox">A1</div><div class="xl-fx">fx</div><div class="xl-formula"></div></div><div class="xl-scroll"></div>';
    table.parentNode.insertBefore(book, table);
    book.querySelector('.xl-scroll').appendChild(table);
    if (table.dataset.xlTabs) {
      var tabs = document.createElement('div');
      tabs.className = 'xl-tabs';
      tabs.innerHTML = table.dataset.xlTabs;
      book.appendChild(tabs);
    }

    // Column letters + corner, and row numbers.
    if (head) {
      head.classList.add('xl-header');
      var letters = document.createElement('tr');
      letters.className = 'xl-letters';
      letters.innerHTML = '<th class="xl-rownum"></th>' + Array.from({ length: cols }, function (_, i) { return '<th>' + letter(i) + '</th>'; }).join('');
      table.tHead.insertBefore(letters, head);
      var corner = document.createElement('th');
      corner.className = 'xl-rownum';
      corner.textContent = '1';
      head.insertBefore(corner, head.firstChild);
    }
    Array.from(table.tBodies).forEach(function (body) {
      Array.from(body.rows).forEach(function (row) {
        var num = document.createElement('td');
        num.className = 'xl-rownum';
        if (row.cells.length === 1 && row.cells[0].colSpan > 1) { row.classList.add('xl-empty'); }
        row.insertBefore(num, row.firstChild);
        Array.from(row.cells).forEach(function (td) { if (/^-?[\d,]+(\.\d+)?%?$/.test(td.textContent.trim()) && td.textContent.trim() !== '' && !td.classList.contains('xl-rownum')) td.classList.add('xl-num'); });
      });
    });
    renumber(table);

    var nameBox = book.querySelector('.xl-namebox'), formula = book.querySelector('.xl-formula');
    function select(td) {
      if (!td || td.classList.contains('xl-rownum')) return;
      table.querySelectorAll('.is-sel').forEach(function (c) { c.classList.remove('is-sel'); });
      table.querySelectorAll('tr.is-active').forEach(function (r) { r.classList.remove('is-active'); });
      table.querySelectorAll('.xl-letters .is-active').forEach(function (c) { c.classList.remove('is-active'); });
      td.classList.add('is-sel');
      td.parentNode.classList.add('is-active');
      var col = td.cellIndex; // the row-number cell is index 0
      var lettersRow = table.querySelector('.xl-letters');
      if (lettersRow && lettersRow.cells[col]) lettersRow.cells[col].classList.add('is-active');
      var rowNo = td.parentNode.querySelector('.xl-rownum').textContent;
      nameBox.textContent = letter(col - 1) + rowNo;
      formula.textContent = td.textContent.trim().replace(/\s+/g, ' ');
    }
    table.addEventListener('click', function (e) {
      var td = e.target.closest('td');
      if (td && table.contains(td)) select(td);
    });
    table.tabIndex = 0;
    table.addEventListener('keydown', function (e) {
      var cur = table.querySelector('td.is-sel');
      if (!cur) return;
      var row = cur.parentNode, i = cur.cellIndex, next = null;
      var visible = function (r) { return r && !r.hidden && r.parentNode.tagName === 'TBODY'; };
      if (e.key === 'ArrowRight') next = row.cells[i + 1];
      if (e.key === 'ArrowLeft' && i > 1) next = row.cells[i - 1];
      if (e.key === 'ArrowDown') { var r = row.nextElementSibling; while (r && !visible(r)) r = r.nextElementSibling; next = r && r.cells[i]; }
      if (e.key === 'ArrowUp') { var u = row.previousElementSibling; while (u && !visible(u)) u = u.previousElementSibling; next = u && u.cells[i]; }
      if (next) { e.preventDefault(); select(next); next.scrollIntoView({ block: 'nearest', inline: 'nearest' }); }
    });
  }

  /* Row numbers follow what is shown (after a filter hides rows). */
  function renumber(table) {
    var n = 2;
    Array.from(table.tBodies).forEach(function (body) {
      Array.from(body.rows).forEach(function (row) {
        var cell = row.querySelector('.xl-rownum');
        if (cell) cell.textContent = row.hidden ? '' : String(n++);
      });
    });
  }

  window.xlRenumber = function (table) { renumber(table); };
  document.querySelectorAll('table[data-xl]').forEach(build);
})();
