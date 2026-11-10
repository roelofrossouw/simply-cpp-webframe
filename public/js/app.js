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
    data: Object.fromEntries(new FormData(form).entries()),
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
