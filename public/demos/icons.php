<?php
  require_once __DIR__ . '/_demo.php';

  // Every BS\Icon, in the Bootstrap Icons site's groups (icons.json, scripts/update-icon-categories.py), with a search
  // over their names and tags and the size to show them at. Clicking one copies BS\Icon::Name.
  Config::Init(Access: Config::FrameworkURL(DEMOS));

  use BS\{Card, Icon};

  Page::AddContent($Card = new Card());
  $Card->AddClass('icon-demo');
  $Card->AddHeader(number_format(count(Icon::cases())) . ' icons');
  $Card->AddParagraph('Use them as BS\Icon::Name, on buttons and menu items, or Icon::Name->Element() anywhere else. Click one to copy its name.');

  // The search and the size
  $Card->AddChild($Tools = new HTML\Div());
  $Tools->AddClasses(['d-flex', 'flex-wrap', 'gap-2', 'align-items-center', 'mb-3']);
  $Tools->AddChild($Search = new HTML\Input('', HTML\InputType::Search));
  $Search->AddClasses(['form-control', 'icon-search']);
  $Search->AddStyle('max-width', '20rem');
  $Search->AddAttribute('placeholder', 'Search icons…');
  $Search->AddAttribute('aria-label', 'Search icons');
  $Tools->AddChild($Sizes = new HTML\Div());
  $Sizes->AddClasses(['btn-group', 'icon-sizes']);
  $Sizes->AddAttribute('role', 'group');
  $Sizes->AddAttribute('aria-label', 'Size');
  foreach ([16, 24, 32] as $Size)
  {
    $Sizes->AddChild($Radio = new HTML\Input('icon-size', HTML\InputType::Radio));
    $Radio->AddClass('btn-check');
    $Radio->AddAttribute('id', $ID = uniqid('size'));
    $Radio->Value($Size);
    if ($Size === 24)
    {
      $Radio->AddAttribute('checked');
    }
    $Sizes->AddChild(new HTML\Label($Size . ' px', $ID))->AddClasses(['btn', 'btn-outline-secondary']);
  }
  $Tools->AddChild($Count = new HTML\Span());
  $Count->AddClasses(['text-body-secondary', 'icon-count', 'ms-auto']);

  // The groups (All first), filled in by the script below; more than fit go into More (BS\NavOverflow)
  $Card->AddChild($Groups = new BS\NavOverflow());
  $Groups->AddChild($Nav = new HTML\UnorderedList());
  $Nav->AddClasses(['nav', 'nav-pills', 'icon-groups', 'mb-3']);
  $Groups->Enable();

  $Card->AddChild($Grid = new HTML\Div());
  $Grid->AddClass('icon-grid');
  $Grid->AddChild(new BS\Spinner());

  $Source = json_encode(Demo('icons.json'));
  Page::AddContent(new HTML\Script(<<<JS
  (function () {
    // The newest icon demo on the page (run by jQuery when the page is loaded into the app, so no currentScript)
    const demos = document.querySelectorAll('.icon-demo:not([data-ready])');
    const root = demos[demos.length - 1];
    if (!root) return;
    root.dataset.ready = 'true';
    const search = root.querySelector('.icon-search'), grid = root.querySelector('.icon-grid'), nav = root.querySelector('.icon-groups');
    const count = root.querySelector('.icon-count');
    let icons = [], group = 'all', size = 24;
    const fold = text => text.normalize('NFD').replace(/[\\u0300-\\u036f]/g, '').toLowerCase();

    const show = function () {
      const words = fold(search.value.trim()).split(/\\s+/).filter(Boolean);
      let shown = 0;
      for (const icon of icons) {
        const match = (group === 'all' || icon.categories.includes(group)) && words.every(word => icon.words.includes(word));
        icon.tile.hidden = !match;
        if (match) shown++;
      }
      count.textContent = shown.toLocaleString() + ' of ' + icons.length.toLocaleString();
      grid.style.setProperty('--icon-size', size + 'px');
    };

    fetch($Source).then(response => response.json()).then(function (data) {
      const counts = {all: data.icons.length};
      for (const icon of data.icons) for (const category of icon.categories) counts[category] = (counts[category] || 0) + 1;
      for (const category of [{slug: 'all', name: 'All'}].concat(data.categories)) {
        const item = document.createElement('li');
        item.className = 'nav-item' + (category.slug === 'all' ? ' nav-overflow-keep' : '');
        const link = document.createElement('button');
        link.type = 'button';
        link.className = 'nav-link text-nowrap' + (category.slug === 'all' ? ' active' : '');
        link.dataset.group = category.slug;
        link.append(category.name + ' ');
        const badge = document.createElement('span');
        badge.className = 'badge rounded-pill text-bg-light';
        badge.textContent = counts[category.slug];
        link.appendChild(badge);
        link.addEventListener('click', function () {
          group = category.slug;
          for (const other of nav.querySelectorAll('.nav-link')) other.classList.toggle('active', other === link);
          show();
        });
        item.appendChild(link);
        // Before More, which the nav overflow may have added already
        nav.insertBefore(item, nav.querySelector(':scope > .nav-overflow-item'));
      }
      const wrapper = nav.parentElement;
      if (wrapper.navOverflow) navOverflowUpdate(wrapper); else navOverflow(wrapper);

      grid.replaceChildren();
      for (const icon of data.icons) {
        const tile = document.createElement('button');
        tile.type = 'button';
        tile.className = 'icon-tile';
        tile.title = 'BS\\\\Icon::' + icon.case + ' (bi-' + icon.name + ')';
        tile.innerHTML = '<i class="bi bi-' + icon.name + '" aria-hidden="true"></i><span class="icon-case"></span><span class="icon-class"></span>';
        tile.querySelector('.icon-case').textContent = icon.case;
        tile.querySelector('.icon-class').textContent = 'bi-' + icon.name;
        tile.addEventListener('click', function () {
          const text = 'Icon::' + icon.case;
          (navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject()).then(function () {
            tile.classList.add('copied');
            setTimeout(() => tile.classList.remove('copied'), 1200);
          }).catch(() => window.prompt('Copy:', text));
        });
        grid.appendChild(tile);
        icons.push({tile: tile, categories: icon.categories, words: fold([icon.case, icon.name, ...icon.tags, ...icon.categories].join(' '))});
      }
      show();
    }).catch(function () {
      grid.textContent = 'The icon list couldn\\'t be loaded.';
    });

    search.addEventListener('input', show);
    for (const radio of root.querySelectorAll('.icon-sizes input')) {
      radio.addEventListener('change', function () {
        size = parseInt(radio.value, 10);
        show();
      });
    }
  })();
JS));
