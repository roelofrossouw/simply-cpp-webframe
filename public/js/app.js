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
      if (menu[0].scrollWidth > menu.outerWidth())
      {
        menu.removeClass('navbar-expand');
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

function loadPage(url) {

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
  let target = getTarget(caller);
  // Remember what the target shows: for a popup's refresh button, and for the tab its page last showed
  target.data('url', url);

  // Check the form first, so an invalid form stays where it is (e.g. open in its popup)
  let form = caller.attr('data-skip-form') ? undefined : (caller.closest('form')[0]
    // A popup's footer buttons sit outside the form in its body, they post that form
    || caller.closest('.modal-footer').siblings('.modal-body').find('form')[0]);
  if (form)
  {
    // Show validation
    $(form).addClass('was-validated');
    if (!form.checkValidity())
    {
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
  $(root).find('[data-bs-toggle="tooltip"]').each(function () {
    bootstrap.Tooltip.getOrCreateInstance(this);
  });
  $(root).find('[data-bs-toggle="popover"]').each(function () {
    bootstrap.Popover.getOrCreateInstance(this);
  });
  $(root).find('[data-bs-ride="carousel"]').each(function () {
    bootstrap.Carousel.getOrCreateInstance(this).cycle();
  });
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
  // Show result in target element on success
  $(this).html(content)
  initComponents(this);
  restoreTabs(this);
  // Reveal panel if needed
  showPanel.call($(this));
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
