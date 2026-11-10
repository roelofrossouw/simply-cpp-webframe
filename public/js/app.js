// Where the package's pages and scripts are served (the site's Alias to sc-webframe/public): this script's own folder,
// one up from js/, e.g. /framework
var framework = (function () {
  var source = document.currentScript ? document.currentScript.src : '';
  return source ? new URL(source).pathname.replace(/\/js\/app\.js$/, '') : '/framework';
})();
var currentAjax = null;
var longAction = null;
var chkSize = [];

// Entry point, load the main menu, and run the default item when done.
function main() {
  // Every popup's refresh button, see refreshModal()
  $(document).on('click', '.modal-refresh', refreshModal);
  // The tab shown on a page, to open it again when the page comes back (see rememberTab()); a popup or side panel
  // forgets its tabs when it closes
  $(document).on('shown.bs.tab', '[data-tab-key]', rememberTab);
  $(document).on('hidden.bs.modal hidden.bs.offcanvas', '.modal, .offcanvas', function () {
    $(this).removeData('tabs');
  });

  if (navigator.userAgent.match(/RJRIVShell/))
  {
    $('body').addClass('ios-app');
  }

  $('#main').load(framework + '/landing.php', function () {
    initComponents(this);
    menuclick();
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('load'))
    {
      localStorage.setItem('load', urlParams.get('load'));
      window.location = '/';
    }
    else
    {
      const load = localStorage.getItem('load');
      if (load)
      {
        localStorage.removeItem('load');
        setTimeout(function () {
          menuclick($('#' + load));
        }, 120);
      }
    }
  });
}

// After a menu is loaded, run the basic javascript that is needed
function setupMenu(menuID, urlMap) {

  for (let [key, value] of Object.entries(urlMap))
  {
    $('#' + key)
      .data('url', value)
      .on('click', function () {
        menuclick(this);
      });
  }

  let menu = $('#' + menuID);


  const resizeObserver = new ResizeObserver((entries) => {
    clearTimeout(chkSize[entries[0].target.id]);
    chkSize[entries[0].target.id] = setTimeout(function (target) {
      let menu = $(target);
      menu.addClass('navbar-expand');
      // A menu with nav overflow first moves what doesn't fit into its More menu; it folds away only when even that
      // doesn't fit
      let overflowing = false;
      menu.find('[data-bs-toggle="nav-overflow"]').each(function () {
        navOverflow(this);
        navOverflowUpdate(this);
        const nav = this.navOverflow && this.navOverflow.nav;
        if (nav && nav.scrollWidth > this.clientWidth + 1) overflowing = true;
      });
      if (overflowing || menu[0].scrollWidth > menu.outerWidth())
      {
        menu.removeClass('navbar-expand');
        // Folded away, the menu lists every item again, with no More
        menu.find('[data-bs-toggle="nav-overflow"]').each(function () {
          navOverflowUpdate(this);
        });
      }
    }, 100, entries[0].target);
  });

  resizeObserver.observe(menu[0]);


  // Add padding under fixed menus so they don't cover the content underneath
  if (menu.is('.fixed-top'))
  {
    menu.parent().parent().css('padding-top', menu.outerHeight() + 'px');
  }

  if (menu.is('.fixed-bottom'))
  {
    menu.parent().parent().css('padding-bottom', menu.outerHeight() + 'px');
  }
}


function menuclick(caller) {
  setTimeout(checkCookieDisclaimer, 100);
  caller = $(caller);
  if (caller.length === 0)
  {
    caller = $('nav ul li a.active').first();
  }

  // Close dropdowns manually (No using BS built-in to allow multiple levels)
  caller.parents('.dropdown-menu.show').removeClass('show');

  // Another menu item starts afresh, on its pages' first tabs
  $('.menu-target').removeData('tabs');

  // Inactivate previous active item, except for the current caller
  let parentMenu = caller.closest('ul');
  parentMenu.find('li a.active').not(caller).removeClass('active');
  // Close expanded menus
  caller.closest('.navbar-collapse.show').collapse('hide');
  caller.addClass("active");

  loadPage.call(caller[0], caller.data("url"));
}

// Loads a page into the target of the element it's called on (a link, button or form), or into `into` when given
function loadPage(url, into) {

  if (typeof url == 'undefined')
  {
    return;
  }

  // Check if another call is still busy and abort that request.
  if (currentAjax)
  {
    currentAjax.abort();
  }

  let caller = $(this);
  let target = into ? $(into) : getTarget(caller);
  // Remember what the target shows: for a popup's refresh button, and for the tab its page last showed
  target.data('url', url);

  // Check the form first, so an invalid form stays where it is (e.g. open in its popup)
  let form = caller.attr('data-skip-form') ? undefined : (caller.closest('form')[0]
    // A popup's footer buttons sit outside the form in its body, they post that form
    || caller.closest('.modal-footer').siblings('.modal-body').find('form')[0]);
  if (form)
  {
    // Show validation only when something needs fixing: on a good form the green checks would just flash by
    $(form).removeClass('was-validated');
    if (!form.checkValidity())
    {
      $(form).addClass('was-validated');
      if ($(form).closest('.modal, .offcanvas').length)
      {
        // In a popup or side panel: the browser points at the first field to fix. The invalid inputs popup
        // would replace the form. A field in a hidden tab can't be pointed at, so open its tab first.
        const pane = $(form).find(':invalid').first().closest('.tab-pane');
        if (pane.length && !pane.hasClass('active'))
        {
          bootstrap.Tab.getOrCreateInstance($('[data-bs-target="#' + pane.attr('id') + '"]')[0]).show();
        }
        form.reportValidity();
      }
      else
      {
        showInvalidInputs(form);
      }
      // Don't post the invalid form
      return;
    }
  }

  // Close the popup or side panel the click came from, unless the response goes back into it. Hiding starts an
  // animation, and Bootstrap ignores show() until it ends, so a fast response would leave the popup closed.
  if (!caller.closest('.modal, .offcanvas').is(target))
  {
    closePanel(caller);
  }

  // Only show progress indicator after a short delay to avoid flashing on fast pages

  longAction = setTimeout(showBusy, 100, target);
  currentAjax = $.ajax({
    url: url,
    method: "POST",
    context: target,
    // The form as it is: a name posted more than once (name[]: a MultiSelect, BS\Choices) keeps every value,
    // and files go too ($_FILES). jQuery mustn't turn it into a query string or set a content type of its own.
    data: new FormData(form),
    processData: false,
    contentType: false,
    success: ajaxSuccess,
    error: ajaxFailure,
  });
}

// Start the Bootstrap components that need a script: tooltips, popovers and auto-playing carousels.
// Called for every piece of content the app loads, as Bootstrap only starts them by itself on a full page load.
function initComponents(root) {
  // Bootstrap reads data-bs-* attributes with JSON.parse where it can, so a text such as
  // '{"url": ...}', '200' or 'true' would become an object, number or boolean, which a title or
  // content refuses. Given as options, which win over the attributes, they stay text.
  const asText = function (element, names) {
    const options = {};
    for (const name of names) {
      const value = element.getAttribute('data-bs-' + name);
      if (value !== null) options[name] = value;
    }
    return options;
  };
  // Each on its own: one that fails doesn't stop the others.
  const start = function (selector, make) {
    $(root).find(selector).each(function () {
      try {
        make(this);
      } catch (problem) {
        console.error('initComponents: ' + selector, this, problem);
      }
    });
  };
  start('[data-bs-toggle="tooltip"]', function (element) {
    bootstrap.Tooltip.getOrCreateInstance(element, asText(element, ['title']));
  });
  start('[data-bs-toggle="popover"]', function (element) {
    bootstrap.Popover.getOrCreateInstance(element, asText(element, ['title', 'content']));
  });
  start('[data-bs-ride="carousel"]', function (element) {
    bootstrap.Carousel.getOrCreateInstance(element).cycle();
  });
  start('[data-bs-toggle="nav-overflow"]', navOverflow);
  start('input[type="date"]', dateField);
  start('input[data-bs-toggle="datepicker"]', datePicker);
  start('select[data-bs-toggle="multi-select"]', multiSelect);
  start('input[data-bs-toggle="timepicker"]', timePicker);
  start('.date-time-picker[data-bs-toggle="datetimepicker"]', dateTimePicker);
  start('input[type="file"][data-bs-toggle="file-input"]', fileInput);
  start('fieldset[data-bs-toggle="choices"]', choices);
  start('button[data-bs-toggle="password"]', passwordToggle);
  start('[data-bs-strength]', passwordStrength);
  start('input[data-bs-toggle="number"]', numberInput);
  start('.otp[data-bs-otp]', otpInput);
  scrollTables(root);
  cutCells(root);
}

// A table wider than its place scrolls sideways instead of being cut off by the page's panel (.menu-target hides what
// overflows sideways): one not already in a scrolling wrapper (BS\ResponsiveTable) gets one.
function scrollTables(root) {
  $(root).find('table.table').addBack('table.table').each(function () {
    if (!$(this).parent().closest('.table-responsive').length) {
      $(this).wrap('<div class="table-responsive"></div>');
    }
  });
}

// Tables' one-line cells (style.css): a column whose heading asks to wrap (TableHeadingCell::Wrap()) has its body
// cells wrap too, and a cell its text doesn't fit gets the full text in a popover (hover, tap or focus). Checked again
// whenever a table changes size, which also covers one shown later (a tab, a popup).
const cellWatch = typeof ResizeObserver === 'undefined' ? null : new ResizeObserver(function (entries) {
  for (const entry of entries) markCutCells(entry.target);
});

function cutCells(root) {
  $(root).find('table.table').addBack('table.table').each(function () {
    const table = this;
    // A wrapping column: its heading's place, counting colspans, given to the body's cells in that place
    $(table).find('thead th.cell-wrap').each(function () {
      const heading = this;
      let column = 0;
      for (const cell of heading.parentElement.cells) {
        if (cell === heading) break;
        column += cell.colSpan || 1;
      }
      for (const row of table.tBodies.length ? Array.from(table.tBodies).flatMap(body => Array.from(body.rows)) : []) {
        let at = 0;
        for (const cell of row.cells) {
          if (at === column) cell.classList.add('cell-wrap');
          at += cell.colSpan || 1;
          if (at > column) break;
        }
      }
    });
    if (cellWatch) cellWatch.observe(table);
    markCutCells(table);
  });
}

function markCutCells(table) {
  for (const body of table.tBodies) {
    for (const row of body.rows) {
      for (const cell of row.cells) {
        const oneLine = getComputedStyle(cell).textOverflow === 'ellipsis';
        const cut = oneLine && cell.clientWidth > 0 && cell.scrollWidth > cell.clientWidth + 1;
        const popover = bootstrap.Popover.getInstance(cell);
        if (cut && !popover) {
          cell.classList.add('cell-cut');
          cell.tabIndex = 0;
          bootstrap.Popover.getOrCreateInstance(cell, {
            content: cell.textContent.trim(), trigger: 'hover focus', placement: 'top', container: 'body', customClass: 'cell-popover',
          });
        } else if (!cut && popover) {
          popover.dispose();
          cell.classList.remove('cell-cut');
          cell.removeAttribute('tabindex');
        }
      }
    }
  }
}

// Nav overflow, Bootstrap 6's NavOverflow ("Priority+") on Bootstrap 5.3 (BS\Tabs::Overflow(), BS\Menu::Overflow()):
// a .nav-overflow[data-bs-toggle="nav-overflow"] wrapper around a .nav moves the items that don't fit into a More
// dropdown at its end, last first, and back as room comes. It watches the wrapper, not the window, so it works in a
// popup or a side panel too. Items with .nav-overflow-keep stay; data-bs-threshold keeps that many visible at least;
// below data-bs-collapse-below pixels everything but the kept items goes into More; data-bs-more-text names the
// button ("" or "false": the icon only). A nav laid out as a column (a menu folded away on a narrow screen) shows
// everything. The More menu's entries stand in for the hidden items: choosing one clicks the item itself, so a tab,
// a menu item (menuclick()) or a link does what it always does. Fires update.bs.navoverflow and, when items moved
// into More, overflow.bs.navoverflow (overflowCount, visibleCount) on the wrapper.
const navOverflowWatch = typeof ResizeObserver === 'undefined' ? null : new ResizeObserver(function (entries) {
  for (const entry of entries) navOverflowUpdate(entry.target);
});

function navOverflow(wrapper) {
  if (wrapper.navOverflow) return;
  const nav = wrapper.querySelector(':scope > .nav, :scope > .navbar-nav');
  if (!nav) return;
  const more = document.createElement('li');
  more.className = 'nav-item dropdown nav-overflow-item d-none';
  more.setAttribute('role', 'presentation');
  const toggle = document.createElement('button');
  toggle.type = 'button';
  toggle.className = 'nav-link dropdown-toggle nav-overflow-toggle text-nowrap';
  toggle.setAttribute('data-bs-toggle', 'dropdown');
  toggle.setAttribute('aria-expanded', 'false');
  const icon = document.createElement('i');
  icon.className = 'bi bi-three-dots nav-overflow-icon';
  icon.setAttribute('aria-hidden', 'true');
  toggle.appendChild(icon);
  const text = wrapper.getAttribute('data-bs-more-text') ?? 'More';
  if (text === '' || text === 'false') {
    toggle.setAttribute('aria-label', 'More');
  } else {
    const label = document.createElement('span');
    label.className = 'nav-overflow-text ms-1';
    label.textContent = text;
    toggle.appendChild(label);
  }
  const menu = document.createElement('ul');
  menu.className = 'dropdown-menu dropdown-menu-end nav-overflow-menu';
  more.append(toggle, menu);
  nav.appendChild(more);
  wrapper.navOverflow = {nav: nav, more: more, toggle: toggle, menu: menu};
  // What the More menu marks as active follows the nav
  nav.addEventListener('shown.bs.tab', function () {
    navOverflowUpdate(wrapper);
  });
  nav.addEventListener('click', function () {
    setTimeout(navOverflowUpdate, 0, wrapper);
  });
  if (navOverflowWatch) navOverflowWatch.observe(wrapper);
  navOverflowUpdate(wrapper);
}

function navOverflowUpdate(wrapper) {
  const state = wrapper.navOverflow;
  if (!state) return;
  const {nav, more, toggle, menu} = state;
  const items = Array.from(nav.children).filter(item => item !== more);
  for (const item of items) item.classList.remove('nav-overflow-hidden');
  more.classList.add('d-none');
  menu.replaceChildren();
  toggle.classList.remove('active');

  const room = wrapper.clientWidth;
  const column = getComputedStyle(nav).flexDirection.startsWith('column');
  const hidden = [];
  if (room > 0 && !column) {
    const fits = () => nav.scrollWidth <= room + 1;
    const below = parseInt(wrapper.getAttribute('data-bs-collapse-below') || '0', 10);
    const threshold = parseInt(wrapper.getAttribute('data-bs-threshold') || '0', 10);
    const movable = items.filter(item => !item.classList.contains('nav-overflow-keep'));
    if (below > 0 && room < below) {
      hidden.push(...movable);
    } else if (!fits()) {
      more.classList.remove('d-none');
      let visible = items.length;
      for (const item of movable.reverse()) {
        if (visible <= threshold) break;
        item.classList.add('nav-overflow-hidden');
        hidden.unshift(item);
        visible--;
        if (fits()) break;
      }
    }
    for (const item of hidden) item.classList.add('nav-overflow-hidden');
    if (hidden.length) more.classList.remove('d-none');
  }
  for (const item of hidden) navOverflowEntries(item, menu, toggle);

  wrapper.dispatchEvent(new CustomEvent('update.bs.navoverflow', {bubbles: true}));
  if (hidden.length) {
    const event = new CustomEvent('overflow.bs.navoverflow', {bubbles: true});
    event.overflowCount = hidden.length;
    event.visibleCount = items.length - hidden.length;
    wrapper.dispatchEvent(event);
  }
}

// The More menu's entries for a hidden nav item: one that stands in for its link, or, for an item with a dropdown,
// its caption as a heading and an entry for each of the dropdown's items (Bootstrap 5 has no submenus)
function navOverflowEntries(item, menu, toggle) {
  const link = item.querySelector(':scope > .nav-link, :scope > .dropdown-item, :scope > a, :scope > button');
  if (!link) return;
  const dropdown = item.querySelector(':scope > .dropdown-menu');
  if (dropdown) {
    const heading = document.createElement('li');
    heading.innerHTML = '<h6 class="dropdown-header"></h6>';
    heading.firstChild.append(...navOverflowCaption(link));
    menu.appendChild(heading);
    for (const sub of dropdown.children) navOverflowEntries(sub, menu, toggle);
    return;
  }
  const entry = document.createElement('li');
  const stand = document.createElement(link.tagName === 'A' && link.getAttribute('href') ? 'a' : 'button');
  if (stand.tagName === 'A') stand.href = link.getAttribute('href');
  else stand.type = 'button';
  stand.className = 'dropdown-item text-nowrap';
  stand.append(...navOverflowCaption(link));
  if (link.classList.contains('active')) {
    stand.classList.add('active');
    stand.setAttribute('aria-current', 'true');
    toggle.classList.add('active');
  }
  if (link.classList.contains('disabled') || link.disabled) {
    stand.classList.add('disabled');
    stand.setAttribute('aria-disabled', 'true');
  }
  stand.addEventListener('click', function (event) {
    event.preventDefault();
    link.click();
  });
  entry.appendChild(stand);
  menu.appendChild(entry);
}

// A link's icon and caption, copied for the More menu, the caption shown even where the nav shows only the icon
function navOverflowCaption(link) {
  const copy = link.cloneNode(true);
  copy.querySelectorAll('[id]').forEach(element => element.removeAttribute('id'));
  copy.querySelectorAll('.visually-hidden').forEach(element => element.classList.remove('visually-hidden'));
  return Array.from(copy.childNodes);
}

// Plain date fields (<input type="date">, a BS\Input of InputType::Date, any page's) get the same calendar as
// BS\DatePicker, so every date is chosen the same way: a hidden field takes the field's name and posts YYYY-MM-DD as
// before; min and max become the calendar's limits; required, disabled and the form attribute carry over. A field
// (or anything around it) with data-native keeps the browser's own picker; without the script it stays the browser's.
function dateField(field) {
  if (field.closest('[data-native]')) return;
  if (!field.id) field.id = 'date_' + Math.random().toString(36).slice(2);
  const store = document.createElement('input');
  store.type = 'hidden';
  store.id = field.id + '_value';
  store.name = field.name;
  store.value = field.value; // the browser's own date input holds YYYY-MM-DD
  store.disabled = field.disabled;
  if (field.hasAttribute('form')) store.setAttribute('form', field.getAttribute('form'));
  if (field.min) field.setAttribute('data-bs-date-min', field.min);
  if (field.max) field.setAttribute('data-bs-date-max', field.max);
  for (const name of ['name', 'min', 'max', 'step']) field.removeAttribute(name);
  field.type = 'text';
  field.autocomplete = 'off';
  field.setAttribute('data-bs-toggle', 'datepicker');
  field.setAttribute('data-bs-value-input', '#' + CSS.escape(store.id));
  field.after(store);
}

// Date fields with a calendar (BS\DatePicker): Bootstrap 6's Datepicker on Bootstrap 5.3, with the same library
// (Vanilla Calendar Pro) and data-bs-* attributes: selection-mode (single, multiple, multiple-ranged), date-min,
// date-max, display-months-count, first-weekday (default Monday), placement (left, center, right, auto) and
// vcp-options (JSON, any Vanilla Calendar Pro setting). The field shows the dates written out in the page's language;
// the form posts them as YYYY-MM-DD in the hidden field data-bs-value-input names: several separated by commas, a
// range as its first and last day. A date typed as YYYY-MM-DD counts too; anything else makes the field invalid.
// Fires change on the field, and change.bs.datepicker with dates.
function datePicker(field) {
  if (field.datePicker || typeof VanillaCalendarPro === 'undefined') return;
  const store = document.querySelector(field.getAttribute('data-bs-value-input')) || field;
  const attribute = name => field.getAttribute('data-bs-' + name);
  const mode = attribute('selection-mode') || 'single';
  const locale = document.documentElement.lang || navigator.language || 'default';
  const isoDate = /^\d{4}-\d{2}-\d{2}$/;
  const written = new Intl.DateTimeFormat(locale, {year: 'numeric', month: 'short', day: 'numeric'});
  const show = dates => dates.map(date => written.format(new Date(date + 'T00:00:00'))).join(mode === 'multiple-ranged' ? ' – ' : ', ');
  // A range is kept as its first and last day; the calendar lists every day in it
  const kept = dates => {
    const sorted = [...dates].sort();
    return mode === 'multiple-ranged' && sorted.length > 1 ? [sorted[0], sorted[sorted.length - 1]] : sorted;
  };
  const save = function (dates) {
    store.value = dates.join(',');
    field.value = show(dates);
    field.setCustomValidity('');
    field.dispatchEvent(new Event('change', {bubbles: true}));
    const event = new CustomEvent('change.bs.datepicker', {bubbles: true});
    event.dates = dates;
    field.dispatchEvent(event);
  };

  const selected = store.value ? store.value.split(',').map(date => date.trim()).filter(date => isoDate.test(date)) : [];
  const months = parseInt(attribute('display-months-count') || '1', 10);
  let extra = {};
  try {
    extra = JSON.parse(attribute('vcp-options') || '{}');
  } catch (problem) {
    console.error('datePicker: data-bs-vcp-options', problem);
  }
  const calendar = new VanillaCalendarPro.Calendar(field, Object.assign({
    inputMode: true,
    positionToInput: attribute('placement') || 'auto',
    type: months > 1 ? 'multiple' : 'default',
    displayMonthsCount: months,
    firstWeekday: parseInt(attribute('first-weekday') ?? '1', 10),
    locale: locale,
    selectionDatesMode: mode === 'single' ? 'single' : mode,
    selectedDates: mode === 'multiple-ranged' && selected.length === 2 ? [selected[0] + ':' + selected[1]] : selected,
    selectedMonth: selected.length ? parseInt(selected[0].slice(5, 7), 10) - 1 : undefined,
    selectedYear: selected.length ? parseInt(selected[0].slice(0, 4), 10) : undefined,
    dateMin: attribute('date-min') || undefined,
    dateMax: attribute('date-max') || undefined,
    enableJumpToSelectedDate: true,
    themeAttrDetect: 'html[data-bs-theme]',
    onChangeToInput: function (self) {
      const dates = kept(self.context.selectedDates);
      save(dates);
      // One date, or both ends of a range: done
      if (mode === 'single' && dates.length || mode === 'multiple-ranged' && self.context.selectedDates.length > 1) self.hide();
    },
  }, extra));
  calendar.init();
  field.datePicker = calendar;
  if (selected.length) field.value = show(kept(selected));

  // Typed: YYYY-MM-DD (several separated by commas) is taken; nothing clears it
  field.addEventListener('input', function () {
    const typed = field.value.split(/\s*[,–]\s*/).map(part => part.trim()).filter(part => part !== '');
    if (!typed.length) {
      store.value = '';
      field.setCustomValidity('');
    } else if (typed.every(part => isoDate.test(part) && !isNaN(new Date(part + 'T00:00:00')))) {
      store.value = kept(typed).join(',');
      field.setCustomValidity('');
      calendar.set({selectedDates: mode === 'multiple-ranged' && typed.length === 2 ? [typed[0] + ':' + typed[1]] : typed}, {dates: true, month: true, year: true});
    } else {
      field.setCustomValidity('Choose a date in the calendar, or type it as YYYY-MM-DD');
    }
  });
  // The calendar icon beside the field opens it too
  const group = field.closest('.input-group');
  if (group) {
    for (const icon of group.querySelectorAll('.input-group-text')) {
      icon.style.cursor = 'pointer';
      icon.addEventListener('click', function () {
        field.focus();
        calendar.show();
      });
    }
  }
}

// Multi-selects (BS\MultiSelect), like CoreUI's: the <select> stays as what the form posts (hidden), and a field in
// its place shows what's chosen as chips (data-bs-selection="counter": "3 selected") with a search to type in; the
// list opens under it with Select all and Clear, its groups as headings. Keys: up and down move through the list,
// Enter chooses, Escape closes, Backspace in an empty search takes off the last chip. data-bs-max limits how many,
// data-bs-single="true" makes it a one-choice search (a combobox), data-bs-search="false" leaves the search out,
// data-bs-select-all="false" Select all. The select's change event fires on every change. data-bs-source="url": the
// list comes from the server as you type (GET url&q=..., JSON [{value, text}]); data-bs-creatable="true": what's typed
// can be added as a value of its own (Enter when nothing matches, or "Add ..." in the list).
function multiSelect(select) {
  if (select.multiSelect) return;
  const single = select.getAttribute('data-bs-single') === 'true';
  const counter = select.getAttribute('data-bs-selection') === 'counter';
  const searchable = select.getAttribute('data-bs-search') !== 'false';
  const selectAll = !single && select.getAttribute('data-bs-select-all') !== 'false';
  const max = parseInt(select.getAttribute('data-bs-max') || '0', 10);
  const placeholder = select.getAttribute('data-bs-placeholder') || (searchable ? 'Search…' : '');
  const source = select.getAttribute('data-bs-source');
  const sourceMin = parseInt(select.getAttribute('data-bs-source-min-length') || '1', 10);
  const creatable = select.getAttribute('data-bs-creatable') === 'true';
  const fold = text => text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
  const element = (tag, className, text) => {
    const made = document.createElement(tag);
    if (className) made.className = className;
    if (text !== undefined) made.textContent = text;
    return made;
  };

  select.classList.add('visually-hidden');
  select.tabIndex = -1;
  const box = element('div', 'form-control multi-select');
  const chips = element('div', 'multi-select-chips');
  const search = element('input', 'multi-select-search');
  search.type = 'text';
  search.autocomplete = 'off';
  search.setAttribute('role', 'combobox');
  search.setAttribute('aria-expanded', 'false');
  search.setAttribute('aria-autocomplete', 'list');
  if (select.id) search.setAttribute('aria-labelledby', select.id + '_label');
  const label = select.id ? select.parentElement.querySelector('label[for="' + select.id + '"]') : null;
  if (label) {
    label.id = select.id + '_label';
    label.htmlFor = '';
    label.addEventListener('click', () => search.focus());
  }
  if (!searchable) search.readOnly = true;
  const clear = element('button', 'btn-close multi-select-clear');
  clear.type = 'button';
  clear.setAttribute('aria-label', 'Clear');
  chips.appendChild(search);
  box.append(chips, clear);

  const menu = element('div', 'dropdown-menu multi-select-menu');
  menu.id = (select.id || 'ms' + Math.random().toString(36).slice(2)) + '_list';
  menu.setAttribute('role', 'listbox');
  if (!single) menu.setAttribute('aria-multiselectable', 'true');
  search.setAttribute('aria-controls', menu.id);
  const tools = element('div', 'multi-select-tools');
  const all = element('button', 'btn btn-link btn-sm', 'Select all');
  const none = element('button', 'btn btn-link btn-sm', 'Clear');
  all.type = none.type = 'button';
  if (selectAll) tools.appendChild(all);
  if (!single) tools.appendChild(none);
  if (tools.children.length) menu.appendChild(tools);
  const list = element('div', 'multi-select-options');
  const empty = element('div', 'dropdown-item-text text-body-secondary multi-select-empty', 'No results');
  const create = element('button', 'dropdown-item multi-select-create');
  create.type = 'button';
  create.tabIndex = -1;
  create.hidden = true;
  menu.append(create, list, empty);
  select.after(box, menu);

  // The list: a heading for each group, an entry for each option
  const entries = [];
  const add = function (option, group) {
    const item = element('button', 'dropdown-item multi-select-option');
    item.type = 'button';
    item.tabIndex = -1;
    item.setAttribute('role', 'option');
    if (!single) {
      const check = element('input', 'form-check-input me-2');
      check.type = 'checkbox';
      check.tabIndex = -1;
      check.setAttribute('aria-hidden', 'true');
      item.appendChild(check);
    }
    item.appendChild(element('span', '', option.textContent));
    list.appendChild(item);
    const entry = {option: option, item: item, group: group, text: fold(option.textContent)};
    entries.push(entry);
    item.addEventListener('mousedown', event => event.preventDefault()); // the search keeps the focus
    item.addEventListener('click', () => choose(entry));
    return entry;
  };
  // A value not among the options yet (from the server, or typed): an option of the select, an entry of the list
  const addOption = function (value, text) {
    const known = entries.find(entry => entry.option.value === value);
    if (known) return known;
    const option = document.createElement('option');
    option.value = value;
    option.textContent = text;
    select.appendChild(option);
    return add(option, null);
  };
  for (const child of select.children) {
    if (child.tagName === 'OPTGROUP') {
      const heading = element('h6', 'dropdown-header', child.label);
      list.appendChild(heading);
      for (const option of child.children) add(option, heading);
    } else if (child.tagName === 'OPTION' && child.value !== '') {
      // After a group, a line, so it doesn't look like that group's
      if (entries.length && entries[entries.length - 1].group) list.appendChild(element('hr', 'dropdown-divider'));
      add(child, null);
    }
  }

  let active = -1;
  const visible = () => entries.filter(entry => !entry.item.hidden);
  const chosen = () => entries.filter(entry => entry.option.selected);
  const changed = function () {
    select.dispatchEvent(new Event('change', {bubbles: true}));
    box.classList.remove('is-invalid');
    render();
  };
  const choose = function (entry) {
    if (entry.option.disabled || entry.item.disabled) return;
    if (single) {
      for (const other of entries) other.option.selected = other === entry;
      changed();
      close();
      return;
    }
    entry.option.selected = !entry.option.selected;
    changed();
    search.value = '';
    filter();
  };
  const createTyped = function () {
    const text = search.value.trim();
    if (!creatable || text === '') return;
    const entry = entries.find(each => each.text === fold(text)) || addOption(text, text);
    if (!entry.option.selected) choose(entry);
    search.value = '';
    filter();
  };
  create.addEventListener('mousedown', event => event.preventDefault());
  create.addEventListener('click', createTyped);
  // The server's options for what's typed, a moment after the typing stops (the last answer wins)
  let asking = null, asked = 0;
  const ask = function () {
    clearTimeout(asking);
    const typed = search.value.trim();
    if (!source || typed.length < sourceMin) {
      found = null;
      empty.textContent = 'No results';
      return;
    }
    empty.textContent = 'Searching…';
    asking = setTimeout(function () {
      const number = ++asked;
      fetch(source + (source.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(typed), {
        credentials: 'same-origin',
        headers: {'Accept': 'application/json', 'X-Requested-by': 'sc-webframe'},
      }).then(response => response.ok ? response.json() : Promise.reject(response.status)).then(function (rows) {
        if (number !== asked) return;
        found = new Set();
        for (const row of Array.isArray(rows) ? rows : []) {
          const value = String(row.value ?? '');
          if (value === '') continue;
          const entry = addOption(value, String(row.text ?? value));
          found.add(entry.option.value);
        }
        empty.textContent = 'No results';
        render();
        filter();
      }).catch(function (problem) {
        if (number !== asked) return;
        empty.textContent = 'The search failed';
        console.error('multiSelect: ' + source, problem);
      });
    }, 250);
  };
  const render = function () {
    const picked = chosen();
    const full = max > 0 && picked.length >= max;
    for (const entry of entries) {
      entry.item.classList.toggle('active', single && entry.option.selected);
      entry.item.setAttribute('aria-selected', entry.option.selected ? 'true' : 'false');
      entry.item.disabled = entry.option.disabled || (full && !entry.option.selected);
      const check = entry.item.querySelector('input');
      if (check) check.checked = entry.option.selected;
    }
    for (const chip of chips.querySelectorAll('.multi-select-chip, .multi-select-count')) chip.remove();
    if (single) {
      search.placeholder = picked.length ? '' : placeholder;
      if (document.activeElement !== search) search.value = picked.length ? picked[0].option.textContent : '';
    } else if (counter) {
      if (picked.length) chips.insertBefore(element('span', 'multi-select-count', picked.length + ' selected'), search);
      search.placeholder = picked.length ? '' : placeholder;
    } else {
      for (const entry of picked) {
        const chip = element('span', 'badge rounded-pill text-bg-secondary multi-select-chip', entry.option.textContent);
        if (!entry.option.disabled && !select.disabled) {
          const remove = element('button', 'btn-close btn-close-white ms-1');
          remove.type = 'button';
          remove.tabIndex = -1;
          remove.setAttribute('aria-label', 'Remove ' + entry.option.textContent);
          remove.addEventListener('mousedown', event => event.preventDefault());
          remove.addEventListener('click', function (event) {
            event.stopPropagation();
            entry.option.selected = false;
            changed();
          });
          chip.appendChild(remove);
        }
        chips.insertBefore(chip, search);
      }
      search.placeholder = picked.length ? '' : placeholder;
    }
    clear.hidden = !picked.length || select.disabled || select.required && single;
    all.disabled = max > 0;
    box.classList.toggle('disabled', select.disabled);
    search.disabled = select.disabled;
  };
  let found = null; // the values the server gave for what's typed (data-bs-source)
  const filter = function () {
    const raw = single && search.value === (chosen()[0] || {option: {}}).option.textContent ? '' : search.value.trim();
    const typed = fold(raw);
    for (const entry of entries) {
      entry.item.hidden = typed !== '' && (source && found ? !found.has(entry.option.value) && !entry.option.selected : !entry.text.includes(typed));
    }
    create.hidden = !creatable || raw === '' || entries.some(entry => entry.text === typed);
    create.textContent = 'Add “' + raw + '”';
    for (const heading of list.querySelectorAll('.dropdown-header')) {
      heading.hidden = !entries.some(entry => entry.group === heading && !entry.item.hidden);
    }
    empty.hidden = visible().length > 0 || !create.hidden;
    highlight(visible().length ? 0 : -1);
  };
  const highlight = function (index) {
    const shown = visible();
    for (const entry of entries) entry.item.classList.remove('focus');
    active = index;
    if (index >= 0 && index < shown.length) {
      shown[index].item.classList.add('focus');
      shown[index].item.scrollIntoView({block: 'nearest'});
      search.setAttribute('aria-activedescendant', shown[index].item.id || (shown[index].item.id = menu.id + '_' + entries.indexOf(shown[index])));
    } else {
      search.removeAttribute('aria-activedescendant');
    }
  };
  const open = function () {
    if (select.disabled || menu.classList.contains('show')) return;
    menu.classList.add('show');
    box.classList.add('focus');
    search.setAttribute('aria-expanded', 'true');
    filter();
  };
  const close = function () {
    menu.classList.remove('show');
    box.classList.remove('focus');
    search.setAttribute('aria-expanded', 'false');
    search.value = '';
    render();
  };

  box.addEventListener('mousedown', function (event) {
    if (event.target !== search) event.preventDefault();
    search.focus();
    open();
  });
  search.addEventListener('focus', open);
  search.addEventListener('blur', close);
  search.addEventListener('input', function () {
    open();
    if (source) {
      found = null;
      ask();
    }
    filter();
  });
  search.addEventListener('keydown', function (event) {
    const shown = visible();
    if (event.key === 'ArrowDown') {
      open();
      highlight(Math.min(active + 1, shown.length - 1));
    } else if (event.key === 'ArrowUp') {
      highlight(Math.max(active - 1, 0));
    } else if (event.key === 'Enter') {
      // Typed text that matches nothing exactly is added (creatable); else the entry highlighted is chosen
      if (creatable && !create.hidden && !shown.some(entry => entry.text === fold(search.value.trim()))) createTyped();
      else if (menu.classList.contains('show') && shown[active]) choose(shown[active]);
    } else if (event.key === 'Escape') {
      if (!menu.classList.contains('show')) return;
      close();
    } else if (event.key === 'Backspace' && search.value === '' && !single) {
      const last = chosen().filter(entry => !entry.option.disabled).pop();
      if (last) {
        last.option.selected = false;
        changed();
      }
      return;
    } else {
      return;
    }
    event.preventDefault();
    event.stopPropagation();
  });
  clear.addEventListener('mousedown', event => event.preventDefault());
  clear.addEventListener('click', function (event) {
    event.stopPropagation();
    for (const entry of entries) if (!entry.option.disabled) entry.option.selected = false;
    changed();
  });
  for (const [button, value] of [[all, true], [none, false]]) {
    button.addEventListener('mousedown', event => event.preventDefault());
    button.addEventListener('click', function () {
      for (const entry of visible()) if (!entry.option.disabled) entry.option.selected = value;
      changed();
    });
  }
  // A form check (required) marks the field; the next choice clears it
  select.addEventListener('invalid', () => box.classList.add('is-invalid'));

  select.multiSelect = {refresh: render, open: open, close: close};
  render();
}

// Time fields (BS\TimePicker), like CoreUI's time picker: under the field a column of hours, one of minutes (in steps
// of data-bs-minute-step), of seconds (data-bs-seconds="true") and AM/PM for a 12-hour clock (data-bs-hour12, else
// the page's language decides), with Now, Clear and Done. data-bs-time-min and -max (HH:MM) grey out what's outside.
// The field shows the time written out; the form posts it 24-hour (HH:MM or HH:MM:SS) in the hidden field
// data-bs-value-input names. A typed time counts too (14:30, 2:30 pm); up and down change the minutes by the step.
// Fires change on the field, and change.bs.timepicker with time.
function timePicker(field) {
  if (field.timePicker) return;
  const store = document.querySelector(field.getAttribute('data-bs-value-input')) || field;
  const attribute = name => field.getAttribute('data-bs-' + name);
  const locale = document.documentElement.lang || navigator.language || 'default';
  const seconds = attribute('seconds') === 'true';
  const step = Math.max(1, parseInt(attribute('minute-step') || '1', 10));
  const hour12 = attribute('hour12') !== null ? attribute('hour12') === 'true'
    : !!new Intl.DateTimeFormat(locale, {hour: 'numeric'}).resolvedOptions().hour12;
  const toSeconds = text => {
    const parts = (text || '').split(':').map(Number);
    return parts.length >= 2 && parts.every(n => !isNaN(n)) ? parts[0] * 3600 + parts[1] * 60 + (parts[2] || 0) : null;
  };
  const min = toSeconds(attribute('time-min')) ?? 0;
  const max = toSeconds(attribute('time-max')) ?? 86399;
  const two = n => String(n).padStart(2, '0');
  const written = new Intl.DateTimeFormat(locale, Object.assign({hour: '2-digit', minute: '2-digit'}, seconds ? {second: '2-digit'} : {},
    hour12 ? {hour12: true} : {hourCycle: 'h23'}));
  let time = null; // {h, m, s}

  const posted = t => two(t.h) + ':' + two(t.m) + (seconds ? ':' + two(t.s) : '');
  const allowed = t => {
    const at = t.h * 3600 + t.m * 60 + (seconds ? t.s : 0);
    return at >= min && at <= max;
  };
  const parse = function (text) {
    const found = text.trim().toLowerCase().match(/^(\d{1,2})(?:[:h.](\d{2}))?(?::(\d{2}))?\s*([ap])?\.?\s*m?\.?$/);
    if (!found) return null;
    let h = parseInt(found[1], 10);
    const m = parseInt(found[2] || '0', 10), sec = parseInt(found[3] || '0', 10);
    if (found[4]) {
      if (h < 1 || h > 12) return null;
      h = h % 12 + (found[4] === 'p' ? 12 : 0);
    }
    return h < 24 && m < 60 && sec < 60 ? {h: h, m: m, s: seconds ? sec : 0} : null;
  };

  const menu = document.createElement('div');
  menu.className = 'dropdown-menu time-picker-menu';
  const columns = document.createElement('div');
  columns.className = 'time-picker-columns';
  const column = function (name, values, label) {
    const list = document.createElement('div');
    list.className = 'time-picker-column';
    list.setAttribute('role', 'listbox');
    list.setAttribute('aria-label', name);
    for (const value of values) {
      const cell = document.createElement('button');
      cell.type = 'button';
      cell.className = 'time-picker-cell';
      cell.tabIndex = -1;
      cell.dataset.value = value;
      cell.textContent = label(value);
      cell.setAttribute('role', 'option');
      cell.addEventListener('mousedown', event => event.preventDefault());
      cell.addEventListener('click', () => pick(name, value));
      list.appendChild(cell);
    }
    columns.appendChild(list);
    return list;
  };
  const hours = hour12 ? [12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11] : Array.from({length: 24}, (_, i) => i);
  const hourList = column('Hours', hours, two);
  const minuteList = column('Minutes', Array.from({length: Math.ceil(60 / step)}, (_, i) => i * step), two);
  const secondList = seconds ? column('Seconds', Array.from({length: 60}, (_, i) => i), two) : null;
  const dayParts = new Intl.DateTimeFormat(locale, {hour: 'numeric', hour12: true}).formatToParts(new Date(2000, 0, 1, 15))
    .find(part => part.type === 'dayPeriod');
  const ampm = hour12 ? column('AM or PM', ['am', 'pm'], v => v === 'am' ? 'AM' : (dayParts ? dayParts.value : 'PM')) : null;
  const footer = document.createElement('div');
  footer.className = 'time-picker-footer';
  const button = (text, className, action) => {
    const made = document.createElement('button');
    made.type = 'button';
    made.className = className;
    made.textContent = text;
    made.addEventListener('mousedown', event => event.preventDefault());
    made.addEventListener('click', action);
    footer.appendChild(made);
  };
  button('Now', 'btn btn-link btn-sm', function () {
    const now = new Date();
    set({h: now.getHours(), m: seconds ? now.getMinutes() : Math.floor(now.getMinutes() / step) * step, s: seconds ? now.getSeconds() : 0});
  });
  button('Clear', 'btn btn-link btn-sm', () => set(null));
  button('Done', 'btn btn-primary btn-sm ms-auto', () => close());
  menu.append(columns, footer);
  (field.closest('.input') || field.parentElement).appendChild(menu);

  const render = function () {
    const mark = (list, selected, disabled) => {
      if (!list) return;
      for (const cell of list.children) {
        const value = isNaN(cell.dataset.value) ? cell.dataset.value : Number(cell.dataset.value);
        cell.classList.toggle('active', selected === value);
        cell.setAttribute('aria-selected', selected === value ? 'true' : 'false');
        cell.disabled = disabled(value);
      }
    };
    const base = time || {h: 0, m: 0, s: 0};
    const hourOf = value => hour12 ? value % 12 + (base.h >= 12 ? 12 : 0) : value;
    // An hour is out when none of it is allowed, a minute when none of that minute is
    mark(hourList, time ? (hour12 ? (time.h % 12 || 12) : time.h) : null,
      value => hourOf(value) * 3600 + 3599 < min || hourOf(value) * 3600 > max);
    mark(minuteList, time ? time.m : null,
      value => !!time && (time.h * 3600 + value * 60 + 59 < min || time.h * 3600 + value * 60 > max));
    mark(secondList, time ? time.s : null, value => !!time && !allowed({h: time.h, m: time.m, s: value}));
    mark(ampm, time ? (time.h >= 12 ? 'pm' : 'am') : null, value => {
      const h = (time ? time.h % 12 : 0) + (value === 'pm' ? 12 : 0);
      return h * 3600 + 3599 < min || h * 3600 > max;
    });
  };
  const show = function () {
    field.value = time ? written.format(new Date(2000, 0, 1, time.h, time.m, time.s)) : '';
    field.setCustomValidity('');
  };
  const set = function (value, keepText) {
    time = value;
    store.value = time ? posted(time) : '';
    if (!keepText) show();
    render();
    field.dispatchEvent(new Event('change', {bubbles: true}));
    const event = new CustomEvent('change.bs.timepicker', {bubbles: true});
    event.time = store.value;
    field.dispatchEvent(event);
  };
  const pick = function (part, value) {
    const next = Object.assign({h: Math.floor(min / 3600), m: 0, s: 0}, time || {});
    if (part === 'Hours') next.h = hour12 ? value % 12 + (next.h >= 12 ? 12 : 0) : value;
    else if (part === 'Minutes') next.m = value;
    else if (part === 'Seconds') next.s = value;
    else next.h = next.h % 12 + (value === 'pm' ? 12 : 0);
    // Outside the limits: to the nearest allowed time
    const at = next.h * 3600 + next.m * 60 + next.s;
    if (at < min || at > max) {
      const clamped = Math.min(max, Math.max(min, at));
      next.h = Math.floor(clamped / 3600);
      next.m = Math.floor(clamped % 3600 / 60);
      next.s = seconds ? clamped % 60 : 0;
    }
    set(next);
  };
  const scroll = function () {
    for (const list of [hourList, minuteList, secondList, ampm]) {
      const active = list && list.querySelector('.active');
      if (active) list.scrollTop = active.offsetTop - list.clientHeight / 2 + active.offsetHeight / 2;
    }
  };
  const open = function () {
    if (menu.classList.contains('show') || field.disabled || field.readOnly) return;
    menu.classList.add('show');
    render();
    scroll();
  };
  const close = () => menu.classList.remove('show');

  field.addEventListener('focus', open);
  field.addEventListener('click', open);
  field.addEventListener('blur', function () {
    // Typed and left: written out as the picker writes it
    if (time && !field.validationMessage) show();
    setTimeout(function () {
      if (!menu.contains(document.activeElement) && document.activeElement !== field) close();
    }, 0);
  });
  field.addEventListener('input', function () {
    if (field.value.trim() === '') {
      field.setCustomValidity('');
      set(null, true);
      return;
    }
    const typed = parse(field.value);
    if (typed && allowed(typed)) {
      field.setCustomValidity('');
      set(typed, true);
      scroll();
    } else {
      store.value = '';
      field.setCustomValidity(typed ? 'Choose a time between ' + (attribute('time-min') || '00:00') + ' and ' + (attribute('time-max') || '23:59')
        : 'Choose a time in the list, or type it as 14:30');
    }
  });
  field.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' || event.key === 'Enter') {
      if (!menu.classList.contains('show')) return;
      close();
    } else if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
      open();
      const now = time ? time.h * 3600 + time.m * 60 : min;
      const next = Math.min(max, Math.max(min, now + (event.key === 'ArrowUp' ? 1 : -1) * step * 60));
      set({h: Math.floor(next / 3600), m: Math.floor(next % 3600 / 60), s: 0});
      scroll();
    } else {
      return;
    }
    event.preventDefault();
  });
  document.addEventListener('mousedown', function (event) {
    if (!menu.contains(event.target) && !field.closest('.input').contains(event.target)) close();
  });
  const group = field.closest('.input-group');
  if (group) {
    for (const icon of group.querySelectorAll('.input-group-text')) {
      icon.style.cursor = 'pointer';
      icon.addEventListener('mousedown', event => event.preventDefault());
      icon.addEventListener('click', function () {
        field.focus();
        open();
      });
    }
  }

  const first = parse(store.value);
  time = first;
  if (first) show();
  render();
  field.timePicker = {open: open, close: close, set: value => set(value ? parse(value) : null)};
}

// File fields (BS\FileInput): the <input type="file"> lies over its drop zone, so a click on the zone chooses files and a
// drop puts them in (with multiple, added to those chosen already). The files chosen are listed under it, each with
// its size and a button to take it off, a thumbnail for pictures with data-bs-preview="true". A file over
// data-bs-max-size bytes, or not of the types accept names, makes the field invalid. Posts as any file field.
function fileInput(input) {
  if (input.fileInput) return;
  const zone = input.closest('.file-drop') || input.parentElement;
  const list = input.closest('.file-input')?.querySelector('.file-list');
  const maxSize = parseInt(input.getAttribute('data-bs-max-size') || '0', 10);
  const preview = input.getAttribute('data-bs-preview') === 'true';
  const accept = (input.getAttribute('accept') || '').split(',').map(type => type.trim().toLowerCase()).filter(type => type !== '');
  const size = function (bytes) {
    const units = ['bytes', 'kB', 'MB', 'GB'];
    let at = 0;
    while (bytes >= 1024 && at < units.length - 1) {
      bytes /= 1024;
      at++;
    }
    return (at ? bytes.toFixed(bytes < 10 ? 1 : 0) : bytes) + ' ' + units[at];
  };
  const accepted = file => !accept.length || accept.some(type =>
    type.startsWith('.') ? file.name.toLowerCase().endsWith(type)
      : type.endsWith('/*') ? file.type.toLowerCase().startsWith(type.slice(0, -1)) : file.type.toLowerCase() === type);
  let urls = [];
  const setFiles = function (files) {
    const transfer = new DataTransfer();
    for (const file of files) transfer.items.add(file);
    input.files = transfer.files;
    input.dispatchEvent(new Event('change', {bubbles: true}));
  };
  const render = function () {
    for (const url of urls) URL.revokeObjectURL(url);
    urls = [];
    const files = Array.from(input.files || []);
    const problems = [];
    if (list) list.replaceChildren();
    files.forEach(function (file, index) {
      const tooBig = maxSize > 0 && file.size > maxSize;
      const wrongType = !accepted(file);
      if (tooBig) problems.push(file.name + ' is bigger than ' + size(maxSize) + '.');
      if (wrongType) problems.push(file.name + ' isn\'t a kind of file this takes.');
      if (!list) return;
      const item = document.createElement('li');
      item.className = 'file-item' + (tooBig || wrongType ? ' text-danger' : '');
      if (preview && file.type.startsWith('image/')) {
        const thumb = document.createElement('img');
        thumb.className = 'file-thumb';
        thumb.alt = '';
        thumb.src = urls[urls.push(URL.createObjectURL(file)) - 1];
        item.appendChild(thumb);
      }
      const name = document.createElement('span');
      name.className = 'file-name';
      name.textContent = file.name;
      const note = document.createElement('small');
      note.className = 'file-size text-body-secondary';
      note.textContent = size(file.size);
      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'btn-close file-remove';
      remove.setAttribute('aria-label', 'Remove ' + file.name);
      remove.addEventListener('click', () => setFiles(files.filter((other, at) => at !== index)));
      item.append(name, note, remove);
      list.appendChild(item);
    });
    input.setCustomValidity(problems.join(' '));
    zone.classList.toggle('has-files', files.length > 0);
  };
  input.addEventListener('change', render);
  for (const name of ['dragenter', 'dragover']) {
    zone.addEventListener(name, function (event) {
      event.preventDefault();
      zone.classList.add('dragging');
    });
  }
  for (const name of ['dragleave', 'dragend']) zone.addEventListener(name, () => zone.classList.remove('dragging'));
  zone.addEventListener('drop', function (event) {
    event.preventDefault();
    zone.classList.remove('dragging');
    const dropped = Array.from(event.dataTransfer?.files || []);
    if (!dropped.length || input.disabled) return;
    setFiles(input.multiple ? Array.from(input.files || []).concat(dropped) : dropped.slice(0, 1));
  });
  input.fileInput = {render: render, setFiles: setFiles};
  render();
}

// Groups of checkboxes (BS\Choices) with data-bs-required="true": at least one ticked, else the first box says so,
// which keeps the form from being posted (radios: their own required does it).
function choices(group) {
  if (group.choices) return;
  const boxes = Array.from(group.querySelectorAll('input[type="checkbox"]'));
  const check = function () {
    if (group.getAttribute('data-bs-required') !== 'true' || !boxes.length) return;
    boxes[0].setCustomValidity(boxes.some(box => box.checked) ? '' : 'Choose at least one.');
  };
  for (const box of boxes) box.addEventListener('change', check);
  group.choices = {check: check};
  check();
}

// The show/hide button of a password field (BS\PasswordInput)
function passwordToggle(button) {
  if (button.passwordToggle) return;
  const field = document.querySelector(button.getAttribute('data-bs-target'));
  if (!field) return;
  button.addEventListener('click', function () {
    const show = field.type === 'password';
    field.type = show ? 'text' : 'password';
    button.setAttribute('aria-pressed', show ? 'true' : 'false');
    button.setAttribute('aria-label', show ? 'Hide the password' : 'Show the password');
    const icon = button.querySelector('.bi');
    if (icon) {
      icon.classList.toggle('bi-eye', !show);
      icon.classList.toggle('bi-eye-slash', show);
    }
    field.focus();
  });
  button.passwordToggle = true;
}

// Password strength meters (BS\PasswordInput::Strength), as Bootstrap 6's Strength: .strength[data-bs-strength] with
// its .strength-segments, the field in data-bs-input, scored 0 to 8 as Bootstrap does (the minimum length, 4 more,
// lower case, upper case, digits, a special character, several, 16 or more) into weak (1-2), fair (3-4), good (5-6)
// and strong (7-8). The level goes in data-bs-strength, the segments up to it get .active, a .strength-text after the
// meter says it in words (data-bs-messages, JSON). Fires strengthChange.bs.strength (strength, score) on a change.
function passwordStrength(meter) {
  if (meter.strength) return;
  const field = document.querySelector(meter.getAttribute('data-bs-input')) || meter.parentElement.querySelector('input[type="password"]');
  if (!field) return;
  const minLength = parseInt(meter.getAttribute('data-bs-min-length') || '8', 10);
  let messages = {weak: 'Weak', fair: 'Could be better', good: 'Good', strong: 'Strong'};
  try {
    messages = Object.assign(messages, JSON.parse(meter.getAttribute('data-bs-messages') || '{}'));
  } catch (problem) {
    console.error('passwordStrength: data-bs-messages', problem);
  }
  const levels = ['weak', 'fair', 'good', 'strong'];
  const segments = meter.querySelectorAll('.strength-segment');
  const words = meter.nextElementSibling && meter.nextElementSibling.classList.contains('strength-text') ? meter.nextElementSibling : null;
  const score = function (password) {
    const special = (password.match(/[^A-Za-z0-9]/g) || []).length;
    return [password.length >= minLength, password.length >= minLength + 4, /[a-z]/.test(password), /[A-Z]/.test(password),
      /[0-9]/.test(password), special > 0, special > 1, password.length >= 16].filter(Boolean).length;
  };
  let last = null;
  const evaluate = function () {
    const points = score(field.value);
    const level = field.value === '' ? null : points <= 2 ? 'weak' : points <= 4 ? 'fair' : points <= 6 ? 'good' : 'strong';
    meter.setAttribute('data-bs-strength', level || '');
    segments.forEach((segment, index) => segment.classList.toggle('active', level !== null && index <= levels.indexOf(level)));
    if (words) words.textContent = level ? messages[level] : '';
    if (level !== last) {
      last = level;
      const event = new CustomEvent('strengthChange.bs.strength', {bubbles: true});
      event.strength = level;
      event.score = points;
      meter.dispatchEvent(event);
    }
  };
  field.addEventListener('input', evaluate);
  meter.strength = {evaluate: evaluate, getStrength: () => last};
  evaluate();
}

// A date and a time in one (BS\DateTimePicker): its DatePicker's and TimePicker's hidden fields stop posting, and
// .date-time-picker-value posts the two joined, YYYY-MM-DD HH:MM (a date without a time at 00:00; no date: nothing).
// Fires change.bs.datetimepicker with value.
function dateTimePicker(wrapper) {
  if (wrapper.dateTimePicker) return;
  const store = wrapper.querySelector('.date-time-picker-value');
  const date = wrapper.querySelector('input[data-bs-toggle="datepicker"]');
  const time = wrapper.querySelector('input[data-bs-toggle="timepicker"]');
  if (!store || !date || !time) return;
  const dateStore = document.querySelector(date.getAttribute('data-bs-value-input')) || date;
  const timeStore = document.querySelector(time.getAttribute('data-bs-value-input')) || time;
  dateStore.removeAttribute('name');
  timeStore.removeAttribute('name');
  const join = function () {
    const day = dateStore.value.split(',')[0].trim();
    const value = day ? day + ' ' + (timeStore.value || '00:00') : '';
    if (store.value === value) return;
    store.value = value;
    const event = new CustomEvent('change.bs.datetimepicker', {bubbles: true});
    event.value = value;
    wrapper.dispatchEvent(event);
  };
  wrapper.addEventListener('change', join);
  wrapper.addEventListener('input', join);
  wrapper.dateTimePicker = {join: join};
  join();
}

// Number fields (BS\NumberInput): shown as the page's language writes numbers, posted plain in the hidden field
// data-bs-value-input names. Typing takes either decimal mark (and group separators); − and + (.number-step, or
// the up and down keys) take off or add data-bs-step, within data-bs-min and data-bs-max; data-bs-decimals rounds.
function numberInput(field) {
  if (field.numberInput) return;
  const store = document.querySelector(field.getAttribute('data-bs-value-input')) || field;
  const attribute = name => field.getAttribute('data-bs-' + name);
  const locale = document.documentElement.lang || navigator.language || 'default';
  const decimals = attribute('decimals') !== null ? parseInt(attribute('decimals'), 10) : null;
  const step = parseFloat(attribute('step') || '1');
  const min = attribute('min') !== null ? parseFloat(attribute('min')) : null;
  const max = attribute('max') !== null ? parseFloat(attribute('max')) : null;
  const localDecimal = (new Intl.NumberFormat(locale).formatToParts(1.5).find(part => part.type === 'decimal') || {value: '.'}).value;
  const places = number => (String(number).split('.')[1] || '').length;
  const format = number => new Intl.NumberFormat(locale, decimals !== null
    ? {minimumFractionDigits: decimals, maximumFractionDigits: decimals} : {maximumFractionDigits: 10}).format(number);
  // A typed number: with both marks the last is the decimal one; one mark is decimal unless it is followed by exactly
  // three digits and isn't this language's decimal mark (1,234 in English); several of one kind group the thousands
  const parse = function (text) {
    let typed = text.replace(/[\s  ']/g, '');
    if (typed === '') return null;
    const commas = (typed.match(/,/g) || []).length, dots = (typed.match(/\./g) || []).length;
    let mark = null;
    if (commas && dots) {
      mark = typed.lastIndexOf(',') > typed.lastIndexOf('.') ? ',' : '.';
    } else if (commas + dots === 1) {
      const one = commas ? ',' : '.';
      mark = one === localDecimal || typed.split(one)[1].length !== 3 ? one : null;
    }
    for (const separator of [',', '.']) if (separator !== mark) typed = typed.split(separator).join('');
    if (mark) typed = typed.replace(mark, '.');
    return /^[-+]?(\d+\.?\d*|\.\d+)$/.test(typed) ? parseFloat(typed) : NaN;
  };
  const check = function (number) {
    let problem = '';
    if (Number.isNaN(number)) problem = 'Type a number.';
    else if (number !== null && (min !== null && number < min || max !== null && number > max)) {
      problem = min !== null && max !== null ? 'Between ' + format(min) + ' and ' + format(max) + '.'
        : min !== null ? 'At least ' + format(min) + '.' : 'At most ' + format(max) + '.';
    }
    field.setCustomValidity(problem);
  };
  const set = function (number, show = true) {
    if (number !== null && !Number.isNaN(number) && decimals !== null) number = Math.round(number * 10 ** decimals) / 10 ** decimals;
    store.value = number === null || Number.isNaN(number) ? '' : decimals !== null ? number.toFixed(decimals) : String(number);
    if (show && !Number.isNaN(number)) field.value = number === null ? '' : format(number);
    check(number);
  };
  const stepBy = function (direction) {
    if (field.disabled || field.readOnly) return;
    const current = parse(field.value);
    let next = current === null || Number.isNaN(current) ? (min ?? 0) : current + direction * step;
    next = parseFloat(next.toFixed(Math.max(places(step), current === null || Number.isNaN(current) ? 0 : places(current))));
    if (min !== null && next < min) next = min;
    if (max !== null && next > max) next = max;
    set(next);
    field.dispatchEvent(new Event('change', {bubbles: true}));
  };
  field.addEventListener('input', () => set(parse(field.value), false));
  field.addEventListener('blur', function () {
    const number = parse(field.value);
    if (!Number.isNaN(number)) set(number);
  });
  field.addEventListener('keydown', function (event) {
    if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') return;
    event.preventDefault();
    stepBy(event.key === 'ArrowUp' ? 1 : -1);
  });
  for (const button of field.closest('.input-group')?.querySelectorAll('.number-step') || []) {
    button.addEventListener('click', () => stepBy(button.getAttribute('data-bs-step-direction') === '-' ? -1 : 1));
  }
  field.numberInput = {set: set, step: stepBy};
  set(store.value === '' ? null : parseFloat(store.value));
}

// One-time codes (BS\OtpInput), as Bootstrap 6's OTP input: .otp[data-bs-otp] around one .otp-input, which stays the
// real field (typing, pasting, a phone's suggested code) over a box drawn for each character (data-bs-length, else
// its maxlength), in groups (data-bs-groups, data-bs-separator), dots with data-bs-mask="true". data-bs-type: numeric
// (the default), alphanumeric or alpha; anything else typed is dropped. Fires input.bs.otpInput and, once every box
// is filled, complete.bs.otpInput, each with value.
function otpInput(box) {
  if (box.otpInput) return;
  const input = box.querySelector('.otp-input');
  if (!input) return;
  const length = parseInt(box.getAttribute('data-bs-length') || input.getAttribute('maxlength') || '6', 10);
  const type = box.getAttribute('data-bs-type') || 'numeric';
  const mask = box.getAttribute('data-bs-mask') === 'true';
  const separator = box.getAttribute('data-bs-separator') || '·';
  let groups = [];
  try {
    groups = JSON.parse(box.getAttribute('data-bs-groups') || '[]');
  } catch (problem) {
    console.error('otpInput: data-bs-groups', problem);
  }
  const unwanted = type === 'alpha' ? /[^A-Za-z]/g : type === 'alphanumeric' ? /[^A-Za-z0-9]/g : /[^0-9]/g;
  input.maxLength = length;
  input.autocomplete = 'one-time-code';
  input.inputMode = type === 'numeric' ? 'numeric' : 'text';
  const slots = document.createElement('div');
  slots.className = 'otp-slots';
  slots.setAttribute('aria-hidden', 'true');
  const cells = [];
  let group = 0, inGroup = 0;
  for (let index = 0; index < length; index++) {
    if (group < groups.length && inGroup === groups[group]) {
      const gap = document.createElement('span');
      gap.className = 'otp-separator';
      gap.textContent = separator;
      slots.appendChild(gap);
      group++;
      inGroup = 0;
    }
    const cell = document.createElement('div');
    cell.className = 'otp-slot';
    slots.appendChild(cell);
    cells.push(cell);
    inGroup++;
  }
  box.appendChild(slots);
  box.classList.add('otp-ready');
  const fire = function (name) {
    const event = new CustomEvent(name, {bubbles: true});
    event.value = input.value;
    box.dispatchEvent(event);
  };
  const render = function () {
    const value = input.value;
    const focused = document.activeElement === input;
    cells.forEach(function (cell, index) {
      cell.textContent = index < value.length ? (mask ? '•' : value[index]) : '';
      cell.classList.toggle('filled', index < value.length);
      cell.classList.toggle('active', focused && index === Math.min(value.length, length - 1));
    });
  };
  input.addEventListener('input', function () {
    const clean = input.value.replace(unwanted, '').slice(0, length);
    if (clean !== input.value) input.value = clean;
    box.classList.remove('is-invalid');
    render();
    fire('input.bs.otpInput');
    if (clean.length === length) fire('complete.bs.otpInput');
  });
  for (const name of ['focus', 'blur', 'keyup', 'click']) input.addEventListener(name, render);
  input.addEventListener('invalid', () => box.classList.add('is-invalid'));
  box.otpInput = {
    getValue: () => input.value,
    setValue: function (value) {
      input.value = value;
      input.dispatchEvent(new Event('input', {bubbles: true}));
    },
    clear: function () {
      input.value = '';
      render();
      input.focus();
    },
    focus: () => input.focus(),
  };
  render();
}

// The items of a list editor (BS\ListEditor)
function listEditorItems(editor) {
  return editor.find('.list-editor-items .list-editor-text').map(function () {
    return $(this).text();
  }).get();
}

// Check what is typed in a list editor's add field: the first item that doesn't fit the pattern makes the field invalid
function listEditorCheck(editor) {
  const field = editor.find('.list-editor-new')[0];
  const pattern = editor.attr('data-pattern') ? new RegExp(editor.attr('data-pattern')) : null;
  // Separated by spaces, but a shell expression like $(uname -r) stays whole
  const words = field.value.match(/(\$\([^()]*\)|\S)+/g) || [];
  const wrong = pattern ? words.find(word => !pattern.test(word)) : undefined;
  field.setCustomValidity(wrong === undefined ? '' : '"' + wrong + '" isn\'t a valid name');
  editor.find('.list-editor-note').text(field.validationMessage);
  return wrong === undefined ? words : null;
}

// Add what is typed in a list editor's add field, skipping ones it has already and ones it gets from elsewhere
// (on leaving the field, quietly keep an invalid entry for the user to fix; saving the form points at it)
function listEditorAdd(editor, report = true) {
  const field = editor.find('.list-editor-new');
  const words = listEditorCheck(editor);
  if (words === null)
  {
    if (report)
    {
      field[0].reportValidity();
    }
    return;
  }
  const items = listEditorItems(editor);
  const existing = JSON.parse(editor.attr('data-existing') || '[]');
  const skipped = [];
  for (const word of words)
  {
    if (items.includes(word))
    {
      continue;
    }
    if (existing.includes(word))
    {
      skipped.push(word);
      continue;
    }
    const item = $(editor.children('template')[0].content.firstElementChild.cloneNode(true));
    item.find('.list-editor-text').text(word);
    item.find('[data-list-remove]').attr('aria-label', 'Remove ' + word);
    editor.find('.list-editor-items').append(item);
    items.push(word);
  }
  field.val('');
  listEditorSync(editor);
  editor.find('.list-editor-note').text(skipped.length ? 'Not added, ' + editor.attr('data-existing-why') + ': ' + skipped.join(' ') : '');
}

// Put a list editor's items in the value it posts
function listEditorSync(editor) {
  editor.children('input[type="hidden"]').val(listEditorItems(editor).join(' '));
}

// Upload the photo chosen in a file input (this) and show the response in its popup.
// The photo is shrunk in the browser first, as the server takes uploads of up to 2 MB.
function uploadPhoto(url) {
  const file = this.files[0];
  if (!file)
  {
    return;
  }
  const target = $(this).closest('.modal').length ? $(this).closest('.modal') : $('#main-modal');
  const send = function (photo) {
    const data = new FormData();
    data.append('photo', photo, 'photo.jpg');
    $.ajax({
      url: url,
      method: 'POST',
      data: data,
      processData: false,
      contentType: false,
      context: target,
      success: ajaxSuccess,
      error: ajaxFailure,
    });
  };
  const image = new Image();
  image.onload = function () {
    const scale = Math.min(1, 1024 / Math.max(image.width, image.height));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(image.width * scale);
    canvas.height = Math.round(image.height * scale);
    canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
    URL.revokeObjectURL(image.src);
    canvas.toBlob(send, 'image/jpeg', 0.9);
  };
  // A format the browser can't draw: send it as it is, the server says whether it works
  image.onerror = function () {
    URL.revokeObjectURL(image.src);
    send(file);
  };
  image.src = URL.createObjectURL(file);
}

// List the form's invalid fields in the popup, named by their placeholder or label
function showInvalidInputs(form) {
  let data = {};
  $(form).find(':input:visible').each(function () {
    if (!this.checkValidity())
    {
      let name = $(this).attr('placeholder') || $('label[for="' + this.id + '"]').first().text() || this.name;
      data[this.id] = {'name': name, 'message': this.validationMessage};
    }
  });
  $.ajax({
    url: framework + '/invalidinputs.php',
    method: "POST",
    context: $('#main-modal'),
    data: data,
    success: ajaxSuccess,
    error: ajaxFailure,
  });
}

function ajaxSuccess(content) {
  if (!content)
  {
    return;
  }
  // Prevent progress indicator from showing
  clearTimeout(longAction);
  // Show result in target element on success; a cut cell's popover (cutCells()) doesn't outlive its cell
  $('.popover.cell-popover').remove();
  $(this).html(content)
  initComponents(this);
  restoreTabs(this);
  // How a save went shows in the toast corner. A notice can name the page to show next (Toast::Then()), loaded in
  // the same place, on the tab it was on; else a popup or side panel that held nothing else closes.
  const notices = showNotices(this);
  if (notices.then)
  {
    loadPage.call(this, notices.then, this);
    return;
  }
  if (notices.empty)
  {
    getPanel($(this)) ? getPanel($(this)).hide() : null;
    return;
  }
  // Reveal panel if needed
  showPanel.call($(this));
}

// Notices (BS\Toast::Notice()) in loaded content move to the toast corner, and go after their delay. Returns the page
// a notice names to show next (then), and whether they left a popup or side panel with nothing else in its body (empty).
function showNotices(target) {
  const notices = $(target).find('.toast.notice');
  if (!notices.length)
  {
    return {then: null, empty: false};
  }
  const then = notices.filter('[data-then]').first().attr('data-then') || null;
  notices.each(function () {
    toastSuccess($(this).detach());
  });
  const body = $(target).find('.modal-body, .offcanvas-body');
  return {
    then: then,
    empty: $(target).is('.modal, .offcanvas') && body.length > 0
      && body.text().trim() === '' && body.find('input, select, textarea, button, img, table').length === 0,
  };
}

// A page's tabs are remembered per target (the popup, the side panel or the main page) by the page they're on, its path
// with its id and level, so a popup's Back to the page, or its refresh, opens the tab it was on. Each set of tabs on the
// page is remembered by its place among them.
function tabPage(url) {
  if (!url)
  {
    return null;
  }
  const address = new URL(url, window.location.origin);
  return [address.pathname, address.searchParams.get('level'), address.searchParams.get('id')].join('|');
}

function rememberTab() {
  const target = $(this).closest('.modal, .offcanvas, .menu-target');
  const page = tabPage(target.data('url'));
  if (!page)
  {
    return;
  }
  const tabs = target.data('tabs') || {};
  const lists = target.find('[role=tablist]');
  tabs[page] = tabs[page] || {};
  tabs[page][lists.index($(this).closest('[role=tablist]'))] = $(this).attr('data-tab-key');
  target.data('tabs', tabs);
}

function restoreTabs(target) {
  const shown = ($(target).data('tabs') || {})[tabPage($(target).data('url'))];
  if (!shown)
  {
    return;
  }
  $(target).find('[role=tablist]').each(function (index) {
    const tab = $(this).find('[data-tab-key]').filter(function () {
      return $(this).attr('data-tab-key') === shown[index];
    }).first();
    if (tab.length && !tab.hasClass('active'))
    {
      bootstrap.Tab.getOrCreateInstance(tab[0]).show();
    }
  });
}

function ajaxFailure(request, status) {
  if (status === 'abort') // Action was cancelled, not a real failure.
  {
    clearTimeout(longAction);
    $(this).html('Cancelled');
    return;
  }
  // Failed to load the page, incorrect URL or PHP fatal error.
  // Somehow the user experience feels better if the error isn't shown too quickly.
  // Users perceive it as if the system at least tried...
  setTimeout(showError, 250, $(this));
}

function toast(url) {
  $(this).data('val', $(this).data('checkbox') ? $(this).is(':checked') : $(this).val());
  $.ajax({
    url: url,
    method: "POST",
    data: $(this).data(),
    success: toastSuccess,
    error: toastFailure,
  });
}

function toastSuccess(content) {
  if (content)
  {
    // Get a copy of the toast element template
    const toast = $(content);
    // Add a trigger handler to delete the copy when it is hidden
    toast.on('hidden.bs.toast', function (e) {
      $(this).remove();
    });
    // Put the AJAX result in the body
    // toast.find('.toast-body').html(content);
    // Add to the DOM inside the toast stack container
    toast.appendTo("#main-toast");
    // Create a new toast instance and show it
    bootstrap.Toast.getOrCreateInstance(toast).show();
  }
}

function toastFailure(request, status) {
  // Just ignore failures for now
  // TODO: Log to application log or send notice to admin.
  return;
}

// The refresh button next to a popup's Close: load its page again, or the URL the page names (ModalDialog::RefreshURL()),
// without the action (or the confirmation) it was loaded with, so refreshing never does it again
function refreshModal() {
  let url = $(this).closest('.modal-dialog').attr('data-refresh-url') || $('#main-modal').data('url');
  if (!url)
  {
    return;
  }
  let address = new URL(url, window.location.origin);
  ['action', 'sure'].forEach(name => address.searchParams.delete(name));
  // Turns until the page comes back and replaces the header, so the click shows even when nothing changed
  $(this).addClass('busy');
  loadPage.call(this, address.pathname + address.search);
}


function closePanel(caller) {
  let panel = caller.closest('.modal, .offcanvas');
  if (panel.length)
  {
    getPanel(panel) ? getPanel(panel).hide() : null;
  }
}

function showPanel() {
  // Label the popup or side panel with the loaded content's title, for screen readers
  let title = $(this).find('.modal-title, .offcanvas-title').first();
  if ($(this).is('.modal, .offcanvas') && title.attr('id'))
  {
    $(this).attr('aria-labelledby', title.attr('id'));
  }
  getPanel(this) ? getPanel(this).show() : null;
}

function getPanel(element) {
  return element.is('.modal') ? getModal(element[0])
    : element.is('.offcanvas') ? getOffcanvas(element[0])
      : null;
}

function getModal(element) {
  return bootstrap.Modal.getOrCreateInstance(element);
}

function getOffcanvas(element) {
  return bootstrap.Offcanvas.getOrCreateInstance(element);
}

function getTarget(caller) {
  return offCanvas(caller)
    || modal(caller)
    || menuTarget(caller)
    || closestTarget(caller)
    || defaultTarget(caller)
    || $('body');
}

function offCanvas(caller) {
  return caller.is('.oc-link') ? $('#main-offcanvas') : null;
}

function modal(caller) {
  return caller.is('.modal-link') ? $('#main-modal') : null;
}

function menuTarget(caller) {
  if (caller.is('.nav-link, nav .dropdown-item'))
  {
    let target = $('#' + caller.closest('nav').attr('id') + '-target');
    return target.length ? target : null;
  }
  return null;
}

function closestTarget(caller) {
  let target = caller.closest('.menu-target');
  return target.length ? target : null;
}

function defaultTarget(caller) {
  let target = $('#main').children('.menu-target');
  return target.length ? target.first() : null;
}

function showBusy(target) {
  target.html(
    '<div class="modal-dialog card m-4">\n' +
    '  <div class="card-body">' +
    '    <h5 class="card-title placeholder-glow">\n' +
    '      <div class="spinner-border spinner-border-sm" role="status">\n' +
    '        <span class="visually-hidden">Loading...</span>' +
    '      </div>' +
    '      <span class="placeholder col-6"></span>\n' +
    '    </h5>\n' +
    '    <p class="card-text placeholder-glow">\n' +
    '      <span class="placeholder col-5"></span>\n' +
    '      <span class="placeholder col-4"></span>\n' +
    '      <span class="placeholder col-4"></span>\n' +
    '      <span class="placeholder col-6"></span>\n' +
    '      <span class="placeholder col-8"></span>\n' +
    '    </p>\n' +
    '  </div>\n' +
    '</div>');
  if (target.is('.modal'))
  {
    bootstrap.Modal.getOrCreateInstance(target[0]).show();
  }
}

function showError(target) {
  clearTimeout(longAction);
  target.html('<div class="alert alert-warning m-4 p-4" role="alert">' +
    '<h2><i class="bi-exclamation-triangle-fill float-start" style="font-size: 24pt;"></i>&nbsp;&nbsp;' +
    'There was a problem loading the content' +
    '</h2><p>Please try again later...</p></div>');
}

function checkCookieDisclaimer() {

  if (localStorage.getItem('cookies-disclaimed') === 'Yes')
  {
    return;
  }

  let cookieDisclaimer = $('#cookie-disclaimer');
  if (!cookieDisclaimer.length)
  {
    $.get(framework + '/cookies.php', (response) => {
      $('body').append(response);
      bootstrap.Modal.getOrCreateInstance('#cookie-disclaimer').show();
    });
  }
}

function acceptCookies(sender) {
  localStorage.setItem('cookies-disclaimed', 'Yes');
  $(sender).closest('.modal-content').html('<h4 class="alert alert-warning m-0">You have accepted the use of cookies, continuing to the site...</h4>');
  setTimeout(function () {
    bootstrap.Modal.getInstance('#cookie-disclaimer').hide();
  }, 500);
}

function preferredTheme() {
  // If a theme is saved use that,
  const theme = localStorage.getItem('theme');
  if (theme && theme !== 'default')
  {
    return theme;
  }
  // Otherwise use the system theme
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

function setTheme(theme) {
  if (!theme)
  {
    theme = preferredTheme();
  }
  else
  {
    localStorage.setItem('theme', theme);
  }
  $('html').attr('data-bs-theme', theme);
}

function enableThemes() {
  setTheme();
  // Add event listener if system theme changes
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    setTheme();
  })
}

function loadServiceWorker() {
  if (navigator.serviceWorker && !navigator.serviceWorker.controller)
  {
    navigator.serviceWorker.register(framework + '/js/serviceworker.js');
  }
}

function init() {
  loadServiceWorker();
  // Notices that came with the whole page
  showNotices(document.body);
  scrollTables(document.body);
  cutCells(document.body);
  enableThemes();
  // Mark all requests coming from the app (Server::IsAJAX()).
  // Can then check if it is loaded from Ajax or directly in browser address (from an external link), in which case the framework needs to be set up
  $(document).on("ajaxSend", function (event, request) {
    // request.setRequestHeader('Authorization', 'Bearer ' + localStorage.getItem('token'));
    request.setRequestHeader('X-Requested-by', 'sc-webframe');
  });

  // Elements acting as buttons (role="button" without being a <button>, e.g. menu items without an href)
  // respond to Enter and Space like buttons do.
  $(document).on('keydown', '[role="button"]:not(button)', function (e) {
    if (e.key === 'Enter' || e.key === ' ')
    {
      e.preventDefault();
      this.click();
    }
  });

  // List editors (BS\ListEditor): add the typed items, remove one, keep the posted value up to date
  $(document).on('click', '.list-editor [data-list-add]', function () {
    listEditorAdd($(this).closest('.list-editor'));
  });
  $(document).on('keydown', '.list-editor-new', function (e) {
    if (e.key === 'Enter')
    {
      e.preventDefault();
      listEditorAdd($(this).closest('.list-editor'));
    }
  });
  // Typed but not added yet: add it when leaving the field, so it isn't lost on saving
  $(document).on('input', '.list-editor-new', function () {
    listEditorCheck($(this).closest('.list-editor'));
  });
  $(document).on('change', '.list-editor-new', function () {
    listEditorAdd($(this).closest('.list-editor'), false);
  });
  $(document).on('click', '.list-editor [data-list-remove]', function () {
    const editor = $(this).closest('.list-editor');
    $(this).closest('.list-editor-item').remove();
    listEditorSync(editor);
    editor.find('.list-editor-new').trigger('focus');
  });

  // $(document).on("ajaxComplete", function (event, request) {
  //   const newtoken = request.getResponseHeader('Authorization');
  //   if (newtoken)
  //   {
  //     localStorage.setItem('token', newtoken.substring(7));
  //   }
  // })
  main();
}

// Run when all scripts are loaded and ready
$(document).ready(init);
