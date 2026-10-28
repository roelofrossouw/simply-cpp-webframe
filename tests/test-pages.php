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
  $Menu = new BS\Menu(new HTML\Text('Test'));
  MenuLayout::Build($Menu);
  Check(!str_contains((string)$Menu, 'Sign in') && str_contains((string)$Menu, 'Home'), 'no providers: Sign in is left out');
  Config::Set('auth.github', $Providers);

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
