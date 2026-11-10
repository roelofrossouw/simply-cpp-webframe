<?php

  // Pages without a database: the HTML and Bootstrap classes, the menu built from a stand-in storage, and access
  require __DIR__ . '/check.inc';

  use Storage\{Backend, Store};

  require __DIR__ . '/memory.inc';

  Config::Load();
  Store::Use($Storage = new MemoryStorage());
  $_SERVER['HTTP_HOST'] = 'www.example.com';

  // Elements and Bootstrap components render as HTML, text escaped
  $Card = new BS\Card();
  $Card->AddChild(new HTML\Header(2, 'Hello <world>'));
  $Card->AddParagraph('Fish & chips');
  $Html = (string)$Card;
  Check(str_contains($Html, 'class="card'), 'a card has its class');
  Check(str_contains($Html, 'Hello &lt;world&gt;'), 'header text is escaped');
  Check(str_contains($Html, 'Fish &amp; chips'), 'paragraph text is escaped');
  $Button = new BS\Button('Go', BS\Icon::House, '/go.php');
  Check(str_contains((string)$Button, 'bi-house'), 'a button shows its icon');

  // The menu, by who looks: signed out, only the public items and Sign in
  Auth\Base::Factory(new Auth\GitHub());
  Check(MenuLayout::IsPublic('/home.php?x=1') && !MenuLayout::IsPublic('/app/orders.php'), 'public pages, the query string ignored');
  Same(['admin', 'public'], MenuLayout::Roles('/app/orders.php'), 'the codes that may view a page');
  Same([], MenuLayout::Roles('/app/unknown.php'), 'a page in no menu: nobody');
  Same(['admin'], MenuLayout::Roles('/app/users.php', true), 'the codes that may edit');
  $Menu = new BS\Menu(new HTML\Text('Test'));
  MenuLayout::Build($Menu);
  $Html = (string)$Menu;
  Check(str_contains($Html, 'Home') && str_contains($Html, 'Sign in'), 'signed out: Home and Sign in');
  Check(!str_contains($Html, 'Orders') && !str_contains($Html, 'Administer') && !str_contains($Html, 'Sign out'), 'signed out: nothing else');
  Check(str_contains($Html, 'login-github') && str_contains($Html, 'login.php?provider=github') && !str_contains($Html, 'login-google'), 'Sign in offers the providers that are set up');
  Check(!Auth\Base::Allowed('/app/orders.php') && Auth\Base::Allowed('/home.php'), 'signed out: only public pages are allowed');

  // No sign-in provider set up: no Sign in item at all
  $Providers = Config::Get('auth.github');
  Config::Set('auth.github', null);
  Config::Set('auth.password.enabled', false);
  $Menu = new BS\Menu(new HTML\Text('Test'));
  MenuLayout::Build($Menu);
  Check(!str_contains((string)$Menu, 'Sign in') && str_contains((string)$Menu, 'Home'), 'no providers: Sign in is left out');
  Config::Set('auth.github', $Providers);
  Config::Set('auth.password.enabled', true);

  // Emails are formatted by sc-phplib's SC\BootstrapEmail when it's loaded (a stand-in here, when it isn't)
  if (!class_exists('SC\BootstrapEmail'))
  {
    eval('namespace SC; final class BootstrapEmail { public static function format(string $Html): string { return "formatted:" . strlen($Html); } }');
  }
  $Email = new BS\Email();
  $Email->AddChild('Hello');
  $Formatted = (new ReflectionMethod(BS\Email::class, 'Format'))->invoke($Email, (string)$Email);
  Check(is_string($Formatted) && $Formatted !== '' && $Formatted !== (string)$Email, 'emails go through SC\BootstrapEmail when it\'s there');
  Same(SC\BootstrapEmail::format(BS\Email::ForMail((string)$Email)), $Email->Html(), 'Html() is what Send() sends: ForMail(), then formatted');

  // What mail clients can't show: icons left out, Bootstrap 5.3 classes as bootstrap-email's, lead text inline
  $Mail = BS\Email::ForMail((string)new BS\Badge('New') . (string)new BS\Button('Open', BS\Icon::House)
    . '<p class="lead">Big</p><p class="text-body-secondary small" style="color: red;">Quiet</p>');
  Check(!str_contains($Mail, 'bi-house') && str_contains($Mail, 'Open'), 'icons are left out of emails, their captions stay');
  Check(str_contains($Mail, 'badge-secondary') && !str_contains($Mail, ' bg-secondary'), 'badges keep their colour (badge-*)');
  $Card = new BS\Card();
  $Card->AddHeader('Head');
  $Card->AddChild($Amount = new HTML\Paragraph('R 10.00'));
  $Amount->AddClass('text-end');
  $Card->AddFooter('Foot');
  $Parts = BS\Email::ForMail((string)$Card . '<div class="text-bg-info text-start">Box</div>');
  Check(str_contains($Parts, 'card-header card-body bg-light') && str_contains($Parts, 'card-footer card-body bg-light'), 'a card\'s header and footer as padded parts');
  Check(str_contains($Parts, 'text-end text-right') && str_contains($Parts, 'text-start') && str_contains($Parts, 'text-left'), 'text-end and text-start as text-right and text-left');
  Check(str_contains($Parts, 'text-bg-info text-start bg-info'), 'text-bg-* on other elements as bg-*');
  Check(str_contains($Mail, 'class="lead" style="font-size: 20px; font-weight: 300;"'), 'lead text is styled inline');
  Check(str_contains($Mail, 'text-muted') && str_contains($Mail, 'style="color: red;'), 'muted text is text-muted, a style it had stays');

  // Text and the elements in it run together: no space before punctuation
  $Paragraph = new HTML\Paragraph('Read ');
  $Paragraph->AddChild(new HTML\Strong('this'));
  $Paragraph->AddChild(', then ');
  $Paragraph->AddChild(new HTML\Anchor('the link', 'https://example.com', IsLink: true));
  $Paragraph->AddChild('.');
  Same("<p>Read <strong>this</strong>, then <a href=\"https://example.com\">the link</a>.</p>\n", (string)$Paragraph, 'a paragraph with inline elements');
  $Menu = new BS\Menu(new HTML\Text('Test'));
  Check(str_contains((string)$Menu->AddItem(BS\Icon::House, 'Home', '/home.php', 'home'), '</i> <span>Home</span>'), 'a menu item: a space between its icon and caption');
  $Tabs = new BS\Tabs();
  $Tabs->AddTab('Settings', 'Content', true, BS\Icon::Gear);
  Check(preg_match('#</i> Settings#', (string)$Tabs) === 1, 'a tab: a space between its icon and caption');
  // Nav overflow: off, the wrapper is a plain nav-wrap; Overflow() turns it on with its options, kept items marked
  Check(str_contains((string)$Tabs, '<div class="nav-wrap"><ul class="nav nav-tabs"') && !str_contains((string)$Tabs, 'nav-overflow'),
    'tabs without Overflow(): a plain wrapper around the nav');
  $Many = (new BS\Tabs())->Overflow(false, 2, BS\ScreenSize::Medium);
  $Many->AddTab('First', 'One', Keep: true);
  $Many->AddTab('Second', 'Two');
  $Html = (string)$Many;
  Check(str_contains($Html, 'class="nav-wrap nav-overflow" data-bs-toggle="nav-overflow" data-bs-more-text data-bs-threshold="2" data-bs-collapse-below="768"'),
    'Tabs::Overflow(): the wrapper with its options');
  Check(substr_count($Html, 'nav-overflow-keep') === 1 && preg_match('#<li class="nav-item nav-overflow-keep"[^>]*><button[^>]*>First#', $Html) === 1,
    'a tab added with Keep stays out of More');
  $Bar = new BS\Menu(new HTML\Text('Test'));
  $Bar->RightAlign();
  $Bar->Overflow();
  $Bar->AddItem(BS\Icon::House, 'Home', '/home.php', 'home2')->Keep();
  $Html = (string)$Bar;
  Check(str_contains($Html, '<div class="nav-wrap nav-overflow" data-bs-toggle="nav-overflow"><ul class="navbar-nav justify-content-end"><li class="nav-item nav-overflow-keep"'),
    'Menu::Overflow(): the navbar in the wrapper, right aligned in it, a kept item');
  Check(!str_contains($Html, 'flex-grow-0'), 'an overflowing menu\'s item bar takes the room there is, right aligned or not');
  // A date field: what's shown has no name, the hidden field posts YYYY-MM-DD; its options as data-bs-* attributes
  $Due = new BS\DatePicker('due', 'Due date', '2026-11-30');
  $Due->Min('2026-11-01')->Months(2)->NoWeekends()->Required();
  $Html = (string)$Due;
  Check(preg_match('#<input id="(date_|input_)[^"]+" placeholder="Due date" class="form-control" data-bs-toggle="datepicker" autocomplete="off" data-bs-value-input="\#[^"]+_value" value="2026-11-30"#', $Html) === 1,
    'a date field shows the date and has no name');
  Check(preg_match('#<input id="[^"]+_value" name="due" type="hidden" value="2026-11-30">#', $Html) === 1, 'the hidden field posts it');
  Check(str_contains($Html, 'data-bs-date-min="2026-11-01"') && str_contains($Html, 'data-bs-display-months-count="2"')
    && str_contains($Html, 'data-bs-vcp-options="{&quot;disableWeekdays&quot;:[0,6]}"') && str_contains($Html, ' required'),
    'its limits, months and calendar options');
  Check(str_contains($Html, '<span class="input-group-text"><i class="bi bi-calendar3"'), 'a calendar icon beside it');
  $Range = new BS\DatePicker('period', 'Period', '2026-06-10,2026-06-18', BS\DateSelection::Range);
  Check(str_contains((string)$Range, 'data-bs-selection-mode="multiple-ranged"') && str_contains((string)$Range, 'value="2026-06-10, 2026-06-18"'),
    'a range: its mode, both ends');

  // A multi-select: a select multiple posting name[], groups, chosen and disabled options, its options
  $Cities = new BS\MultiSelect('cities', 'Cities', ['Gauteng' => ['jhb' => 'Johannesburg', 'pta' => 'Pretoria'], 'dbn' => 'Durban'], ['pta']);
  $Cities->Max(2)->Counter()->Disable(['jhb']);
  $Html = (string)$Cities;
  Check(str_contains($Html, 'name="cities[]" multiple class="form-select multi-select-source" data-bs-toggle="multi-select" data-bs-max="2" data-bs-selection="counter"'),
    'a multi-select posts name[] and carries its options');
  Check(str_contains($Html, '<optgroup label="Gauteng"><option value="jhb" disabled>Johannesburg</option>') && str_contains($Html, '<option value="pta" selected>Pretoria</option>'),
    'its groups, chosen and disabled options');
  $_POST['cities'] = ['dbn'];
  $Cities->ShowPostedValue();
  Check(str_contains((string)$Cities, '<option value="dbn" selected>') && !str_contains((string)$Cities, 'value="pta" selected'), 'the posted values chosen again');
  $One = (new BS\MultiSelect('country', 'Country', ['za' => 'South Africa']))->Single();
  Check(!str_contains((string)$One, ' multiple') && str_contains((string)$One, 'data-bs-single="true"'), 'Single(): one choice, still searchable');

  $Words = new HTML\Paragraph('Two');
  $Words->AddChild('words');
  Same("<p>Two words</p>\n", (string)$Words, 'text children still get a space between them');

  // How a save went: a toast for the toast corner, gone after 5 seconds (10 when it didn't work)
  $Saved = (string)BS\Toast::Notice('Saved', 'All done.');
  Check(str_contains($Saved, 'toast') && str_contains($Saved, 'notice') && str_contains($Saved, 'data-bs-delay="8000"') && str_contains($Saved, 'text-bg-success'),
    'a notice that it worked: green, 8 seconds');
  $Failed = BS\Toast::Notice('Not saved', 'The disk is full.', false);
  Check(str_contains((string)$Failed, 'data-bs-delay="15000"') && str_contains((string)$Failed, 'text-bg-warning') && !$Failed->IsGood(),
    'and that it didn\'t: yellow, 15 seconds');
  Check(str_contains(file_get_contents(Config::FrameworkFolder() . '/js/app.js'), "find('.toast.notice')"), 'js/app.js moves notices to the toast corner');
  Check(str_contains((string)BS\Toast::Notice('Added')->Then('/app/container.php?id=1'), 'data-then="/app/container.php?id=1"'), 'a notice can name the page to show next');
  Check(str_contains(file_get_contents(Config::FrameworkFolder() . '/js/app.js'), 'loadPage.call(this, notices.then, this)'), 'which js/app.js loads where the notice came');

  // Alerts: the icon in a column of its own beside the text (not floated, so the text doesn't wrap under it)
  $Alert = (string)new BS\AlertSuccess('Saved', 'All done.');
  Check(str_contains($Alert, 'bi-check-circle') && str_contains($Alert, 'alert-success') && str_contains($Alert, 'd-flex') && !str_contains($Alert, 'float-'),
    'a success alert: a check beside its text');
  Check(str_contains((string)new BS\AlertInfo('Sent', 'Check your email.', BS\Icon::Envelope), 'bi-envelope'), 'an alert takes another icon');
  Check(!str_contains((string)new BS\Alert('Plain', 'No icon.'), 'd-flex'), 'a plain alert stays as it is');

  // A page loads the package's script and style, with a version from their contents
  $Framework = new Framework();
  $Framework->AddScript(Config::FrameworkURL('js/app.js'));
  $Html = $Framework->FullHTML();
  Check(str_contains($Html, '/framework/js/app.js?v=' . md5_file(Config::FrameworkFolder() . '/js/app.js')), 'app.js with its version');
  Check(str_contains($Html, '/framework/css/style.css?v='), 'style.css with its version');
  Check(str_contains($Html, '<title>Test site</title>'), 'the title is the app name');

  // Sign-in tokens: signed with auth.secret, read back, and refused when changed
  $Token = new Auth\JWT(['userid' => '7', 'access' => ['public']], 60);
  $Read = Auth\JWT::FromToken((string)$Token);
  Same('7', $Read?->Get('userid'), 'a token reads back');
  Check(!$Read->Expired(), 'a new token has not expired');
  [$Header, $Payload, $Signature] = explode('.', (string)$Token);
  $Forged = Auth\JWT::FromToken($Header . '.' . rtrim(strtr(base64_encode(json_encode(['userid' => '1', 'access' => ['admin'], 'iss' => Config::Origin(),
      'exp' => time() + 60])), '+/', '-_'), '=') . '.' . $Signature);
  Check($Forged === null || $Forged->Get('userid') !== '1', 'a changed token is refused');

  // User's display names
  Same('Ann', User::DisplayName($Storage->Profile(7)), 'the first name by default');
  Same('Ann Example', User::DisplayName(['display_as' => 'full'] + $Storage->Profile(7)), 'the full name');
  Same('GitHub', User::ProviderTitle('github'), 'a provider\'s title');
  Check(User::Blocked([]) && !User::Blocked(['public']), 'blocked without public access');
