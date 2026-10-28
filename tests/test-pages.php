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
  $Words = new HTML\Paragraph('Two');
  $Words->AddChild('words');
  Same("<p>Two words</p>\n", (string)$Words, 'text children still get a space between them');

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
