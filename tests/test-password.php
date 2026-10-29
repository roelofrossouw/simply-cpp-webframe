<?php

  // Signing in with an email address and password (Auth\Password): the checks, the lock after wrong passwords, the
  // tokens, and the pages, each page in a process of its own as PHP runs it, its emails caught in a file
  require __DIR__ . '/check.inc';
  require __DIR__ . '/memory.inc';

  use Auth\Password;

  // The storage every process starts with: account 100, bob@example.com, with a password; 101, ann@example.com, invited
  function Storage(): MemoryStorage
  {
    Storage\Store::Use($Storage = new MemoryStorage());
    // One hash for every process, so a token made in one works in the others
    if (getenv('SC_WEBFRAME_TEST_HASH') === false)
    {
      putenv('SC_WEBFRAME_TEST_HASH=' . Password::Hash('right horse battery'));
    }
    $Storage->AddPasswordUser('Bob@Example.com', 'Bob', null, getenv('SC_WEBFRAME_TEST_HASH'));
    $Storage->AddPasswordUser('ann@example.com', 'Ann', null, null);
    return $Storage;
  }

  $Page = $argv[1] ?? null;
  if ($Page !== null)
  {
    // One page: posted values, cookies and the query string as JSON, signed in as $Cookies['userid'] when it's given
    $Checks = -1; // This process only prints the page
    $_SERVER['HTTP_HOST'] = 'www.example.com';
    $_SERVER['REMOTE_ADDR'] = '192.0.2.1';
    $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = Config::FrameworkURL($Page);
    [$_POST, $_COOKIE, $_GET] = json_decode($argv[2], true);
    if (!empty($_COOKIE['ajax']))
    {
      $_SERVER['HTTP_X_REQUESTED_BY'] = 'sc-webframe';
    }
    Config::Load();
    $Storage = Storage();
    if (!empty($_COOKIE['userid']))
    {
      $Storage->Grants[100] = $Storage->Grants[7] = ['public', 'admin'];
      $_COOKIE['auth_token'] = (string)new Auth\JWT(['userid' => $_COOKIE['userid'], 'access' => ['public', 'admin'], 'provider' => 'password'], 600);
      $_COOKIE['auth_provider'] = 'password';
    }
    include Config::FrameworkFolder() . '/' . $Page;
    return;
  }

  // The pages' emails go to a file instead, through a sendmail of our own (PHP's sendmail_path)
  $MailFile = tempnam(sys_get_temp_dir(), 'sc-webframe-mail');
  $Sendmail = "$MailFile.sh";
  file_put_contents($Sendmail, "#!/bin/sh\ncat >> " . escapeshellarg($MailFile) . "\n");
  chmod($Sendmail, 0700);
  register_shutdown_function(fn() => array_map('unlink', [$MailFile, $Sendmail]));

  function Render(string $Page, array $Post = [], array $Cookies = [], array $Query = []): string
  {
    global $Sendmail, $MailFile;
    file_put_contents($MailFile, '');
    return (string)shell_exec(escapeshellarg(PHP_BINARY) . ' -d error_reporting=-1 -d display_errors=1 -d sendmail_path=' . escapeshellarg($Sendmail)
      . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($Page) . ' ' . escapeshellarg(json_encode([$Post, $Cookies, $Query])) . ' 2>&1');
  }

  // What the last page emailed
  function Emailed(): string
  {
    global $MailFile;
    return quoted_printable_decode((string)file_get_contents($MailFile));
  }

  // The link to a page in the last email, or ''
  function MailLink(string $Page): string
  {
    return preg_match('/' . preg_quote($Page, '/') . '\?token=([\w.-]+)/', Emailed(), $Found) ? $Found[1] : '';
  }

  function NoErrors(string $Html, string $What): void
  {
    Check(!preg_match('/Warning|Deprecated|Fatal|Something Went Wrong/', $Html), "$What without errors"
      . (preg_match('/(Warning|Deprecated|Fatal)[^\n]*/', $Html, $M) ? ': ' . $M[0] : ''));
  }

  Config::Load();
  $_SERVER['HTTP_HOST'] = 'www.example.com';
  $_SERVER['REMOTE_ADDR'] = '192.0.2.1';
  Check(Password::Configured() && Password::CanEmail(), 'on with auth.password.enabled, and mail');
  Same(8, Password::MinLength(), 'the shortest password, from the settings');
  Same('bob@example.com', Password::Address(' Bob@Example.COM '), 'addresses are kept trimmed, lower case');
  Same(null, Password::Address('bob'), 'not one that isn\'t');

  // Where a try came from: the address alone without an ipgeolocation.io key, or for a private address
  Same('198.51.100.7', Password::Whereabouts('198.51.100.7'), 'no key: the address alone');
  Config::Set('ipgeolocation.api_key', 'not used for private addresses');
  Same('192.168.1.2', Password::Whereabouts('192.168.1.2'), 'a private address isn\'t looked up');
  Config::Set('ipgeolocation.api_key', null);
  Same('an unknown address', Password::Whereabouts(''), 'none at all');

  // What a new password must be
  Same('A password needs at least 8 characters.', Password::Weakness('short'), 'too short');
  Same('The password can\'t be the email address.', Password::Weakness('Bob@example.com', 'bob@example.com'), 'not the email address');
  Same(null, Password::Weakness('right horse battery', 'bob@example.com'), 'a good one');
  Check(Password::Weakness(str_repeat('x', Password::MAX_LENGTH + 1)) !== null, 'too long');

  // Signing in: by email address, any case; an invited account can't until it chose a password
  $Storage = Storage();
  Same(100, Password::Check(' BOB@example.com', 'right horse battery'), 'the right password');
  Same(null, Password::Check('bob', 'right horse battery'), 'not a name, only the email address');
  Same(null, Password::Check('bob@example.com', 'wrong horse battery'), 'a wrong password');
  Same(null, Password::Check('nobody@example.com', 'right horse battery'), 'an unknown address');
  Same(null, Password::Check('ann@example.com', ''), 'an invited account, without a password yet');
  Same('1', $Storage->Password(100)['failures'], 'the wrong password is counted');
  Same(100, Password::Check('bob@example.com', 'right horse battery'), 'the right one again');
  Same('0', $Storage->Password(100)['failures'], 'clears the count');
  Same(4, $Storage->AddressFailures('192.0.2.1', Password::LOCK_SECONDS), 'and the address\'s are kept, unknown addresses too');

  // Too many wrong passwords lock the account, the right one too, for a while
  for ($Try = 0; $Try < Password::LOCK_AFTER; $Try++)
  {
    Password::Check('bob@example.com', "wrong $Try");
  }
  Check(is_string(Password::Check('bob@example.com', 'right horse battery')), 'locked after ' . Password::LOCK_AFTER . ' wrong passwords');
  $Storage->Passwords[100]['last_failure'] = (string)(time() - Password::LOCK_SECONDS - 1);
  Same(100, Password::Check('bob@example.com', 'right horse battery'), 'unlocked once the time is up');

  // Too many from one address, across accounts
  $Storage->AddressFailures['192.0.2.1'] = array_fill(0, Password::ADDRESS_LIMIT, time());
  Check(is_string(Password::Check('bob@example.com', 'right horse battery')), 'an address with too many wrong passwords is refused');
  Check(!Password::MayTry(), 'and may not ask for links either');
  $Storage->AddressFailures = [];

  // Tokens work until the password changes
  $Session = (string)Password::Token(100, 'session', 600);
  Same(100, Password::TokenUser($Session, 'session'), 'a session token');
  Same(null, Password::TokenUser($Session, 'password'), 'is no password link');
  Same(null, Password::TokenUser(substr($Session, 0, -2) . 'xx', 'session'), 'a changed one doesn\'t work');
  Same(null, Password::TokenUser((string)new Auth\JWT(['purpose' => 'session', 'uid' => 100, 'pv' => 'guess'], 600), 'session'), 'nor a guessed fingerprint');
  Same(null, Password::TokenUser((string)Password::Token(101, 'session', 600), 'session'), 'no session without a password');
  Same(101, Password::TokenUser((string)Password::Token(101, 'password', 600), 'password'), 'but an invitation link');
  Same(null, Password::Token(7, 'session', 600), 'a GitHub user has none');
  $Link = (string)Password::Token(100, 'password', 600);
  Same(null, Password::Set(100, 'a new horse battery'), 'set a new password');
  Same(null, Password::TokenUser($Session, 'session'), 'which ends the session');
  Same(null, Password::TokenUser($Link, 'password'), 'and its link');
  Same('This user doesn\'t sign in with a password.', Password::Set(7, 'a new horse battery'), 'a GitHub user\'s can\'t be set');
  Same('There is an account for bob@example.com already.', $Storage->AddPasswordUser('BOB@example.com', null, null, null), 'one account per address');

  // The login page, a step at a time: the email address first, with Next in the foot
  $Step = fn(array $Post, array $Cookies = []) => Render('login.php', $Post, $Cookies, ['provider' => 'password']);
  $Form = Render('login.php', Query: ['provider' => 'password']);
  Check(str_contains($Form, 'name="email"') && !str_contains($Form, 'name="password"'), 'the first step asks for the email address only');
  Check(preg_match('/<form [^>]*id="(signin_\w+)"/', $Form, $Id) === 1 && str_contains($Form, 'form="' . $Id[1] . '"') && str_contains($Form, 'Next'),
    'with Next in the foot, posting it');
  Check(str_contains($Form, 'card-footer') && str_contains($Form, 'bi-house') && str_contains($Form, 'https://www.example.com"'), 'beside Home, as a page of its own');
  Check(str_contains($Form, 'method="post"'), 'which the browser posts');
  NoErrors($Form, 'the first step');
  $Choice = Render('login.php');
  Check(str_contains($Choice, 'name="email"') && str_contains($Choice, 'Sign in with GitHub') && substr_count($Choice, 'bi-house') == 1,
    'without a provider: the first step, the other providers, one Home');
  $InApp = Render('login.php', Cookies: ['ajax' => '1'], Query: ['provider' => 'password']);
  Check(!str_contains($InApp, 'bi-house') && str_contains($InApp, 'Next'), 'in the app: Next, but no Home (the menu is there)');
  Check(!str_contains($InApp, 'method="post"') && str_contains($InApp, "on('submit'") && str_contains($InApp, 'loadPage.call(this'),
    'and the app posts it, showing the answer under the menu');

  // An account: the password next
  $Password = $Step(['email' => 'BOB@example.com']);
  Check(str_contains($Password, 'name="password"') && str_contains($Password, 'value="bob@example.com"') && str_contains($Password, 'readonly'),
    'an account with a password: the password step, with its address');
  Check(str_contains($Password, 'Sign in') && str_contains($Password, 'value="password.') && str_contains($Password, 'resetpassword.php'),
    'Sign in, and Forgot your password?');
  Check(!str_contains($Password, 'Not you?'), 'and no Not you?');
  NoErrors($Password, 'the password step');
  // No account, or an invited one: an email
  $None = $Step(['email' => 'nobody@example.com']);
  Check(str_contains($None, 'No account yet') && MailLink('signup.php') !== '', 'no account: the address gets a link to make one');
  Check(str_contains($None, 'alert-info') && str_contains($None, 'bi-envelope'), 'said with an envelope, as news rather than a warning');
  Check(str_contains($None, 'Use another address') && !str_contains($None, 'name="password"'), 'and no password step');
  NoErrors($None, 'no account');
  $Invited = $Step(['email' => 'ann@example.com']);
  Check(str_contains($Invited, 'waiting for its password') && MailLink('resetpassword.php') !== '' && str_contains(Emailed(), 'You have an account'),
    'an invited account gets its invitation again');
  Check(str_contains($Step(['email' => 'not an address']), 'look right'), 'an address that isn\'t one');

  // The password
  $State = ['state' => 'password.0123456789abcdef'];
  $Right = $Step($State + ['email' => 'Bob@example.com', 'password' => 'right horse battery'], ['oauth_state' => $State['state']]);
  Check(str_contains($Right, 'window.location'), 'the right password goes on to the site');
  Check(Emailed() === '', 'without an email');
  NoErrors($Right, 'signing in');
  $Wrong = $Step($State + ['email' => 'bob@example.com', 'password' => 'wrong'], ['oauth_state' => $State['state']]);
  Check(str_contains($Wrong, 'isn&#039;t right') || str_contains($Wrong, 'isn\'t right'), 'a wrong one says so');
  Check(!str_contains($Wrong, 'window.location') && !str_contains($Wrong, 'name="password"'), 'and doesn\'t sign in');
  Check(preg_match('/<button[^>]*disabled[^>]*>.*Try again in 5…/s', $Wrong) === 1 && str_contains($Wrong, 'setInterval'), 'Try again counts down from 5, disabled');
  Check(str_contains(Emailed(), 'To: bob@example.com') && str_contains(Emailed(), 'wrong password') && MailLink('resetpassword.php') !== '',
    'the owner gets an email, with a link to choose a new password');
  NoErrors($Wrong, 'a wrong password');
  $WrongNobody = $Step($State + ['email' => 'nobody@example.com', 'password' => 'x'], ['oauth_state' => $State['state']]);
  Check(Emailed() === '', 'a password for no account emails nobody');
  $Forged = $Step($State + ['email' => 'bob@example.com', 'password' => 'right horse battery'], ['oauth_state' => 'password.other']);
  Check(str_contains($Forged, 'expired') && str_contains($Forged, 'name="password"') && !str_contains($Forged, 'window.location'),
    'a form from elsewhere doesn\'t sign in');
  $Ajax = $Step($State + ['email' => 'bob@example.com', 'password' => 'right horse battery'], ['oauth_state' => $State['state'], 'ajax' => '1']);
  Check(str_contains($Ajax, 'window.location'), 'signing in from the app');
  NoErrors($Ajax, 'signing in from the app');
  $AjaxReset = Render('resetpassword.php', ['email' => 'bob@example.com'], ['ajax' => '1']);
  Check(str_contains($AjaxReset, 'Check your email') && !str_contains($AjaxReset, '<html'), 'Forgot your password? in the app');

  // Forgot your password?: the link, then the password, signed in
  $Reset = Render('resetpassword.php');
  Check(str_contains($Reset, 'name="email"'), 'the reset page asks for the email address');
  NoErrors($Reset, 'the reset page');
  $Sent = Render('resetpassword.php', ['email' => 'nobody@example.com']);
  Check(str_contains($Sent, 'Check your email') && Emailed() === '', 'the same answer for an unknown address, and no email');
  $Sent = Render('resetpassword.php', ['email' => 'BOB@example.com']);
  Check(str_contains($Sent, 'Check your email') && ($Link = MailLink('resetpassword.php')) !== '', 'an account gets the link');
  NoErrors($Sent, 'asking for a link');
  $Choose = Render('resetpassword.php', Query: ['token' => $Link]);
  Check(str_contains($Choose, 'A password for bob@example.com') && str_contains($Choose, 'name="token"'), 'the link asks for a password');
  NoErrors($Choose, 'the link');
  $Short = Render('resetpassword.php', ['token' => $Link, 'password' => 'short', 'repeat' => 'short']);
  Check(str_contains($Short, 'at least 8 characters'), 'not a short one');
  $Changed = Render('resetpassword.php', ['token' => $Link, 'password' => 'brand new battery', 'repeat' => 'brand new battery']);
  Check(str_contains($Changed, 'Password saved') && str_contains($Changed, 'window.location'), 'saves it, and signs in');
  NoErrors($Changed, 'saving it');
  $Old = Render('resetpassword.php', Query: ['token' => (string)new Auth\JWT(['purpose' => 'password', 'uid' => 100, 'pv' => 'old'], 600)]);
  Check(str_contains($Old, 'doesn&#039;t work') || str_contains($Old, 'doesn\'t work'), 'a used or old link doesn\'t work');

  // Administer → Users: add an account (an invitation), and email a password link
  $Signed = ['userid' => '7', 'ajax' => '1'];
  $Users = Render('admin/users.php', Cookies: $Signed);
  Check(str_contains($Users, 'Add a user') && str_contains($Users, 'password.php"'), 'Users can add a user');
  Check(str_contains($Users, 'Password (invited)') && str_contains($Users, 'password.php?userid=100'), 'shows who is invited, and sends links');
  NoErrors($Users, 'Users');
  $Add = Render('admin/password.php', Cookies: $Signed);
  Check(str_contains($Add, 'Add a user') && str_contains($Add, 'name="email"') && !str_contains($Add, 'name="password"'), 'Add a user asks for no password');
  NoErrors($Add, 'Add a user');
  $Added = Render('admin/password.php', ['email' => 'Carol@example.com', 'first_name' => 'Carol', 'last_name' => ''], $Signed, ['action' => 'save']);
  Check(str_contains($Added, 'User added') && MailLink('resetpassword.php') !== '', 'adds the account, and emails it an invitation');
  Check(str_contains(Emailed(), 'carol@example.com') && str_contains(Emailed(), 'You have an account'), 'to its address');
  NoErrors($Added, 'adding a user');
  $Taken = Render('admin/password.php', ['email' => 'BOB@example.com'], $Signed, ['action' => 'save']);
  Check(str_contains($Taken, 'There is an account for bob@example.com already'), 'but not one for an address that has one');
  $Ask = Render('admin/password.php', Cookies: $Signed, Query: ['userid' => '100']);
  Check(str_contains($Ask, 'Send the link'), 'the key button asks before it sends');
  $Send = Render('admin/password.php', Cookies: $Signed, Query: ['userid' => '100', 'action' => 'send']);
  Check(str_contains($Send, 'Sent') && MailLink('resetpassword.php') !== '', 'and sends a password link');
  NoErrors($Send, 'sending a link');
  Render('admin/password.php', Cookies: $Signed, Query: ['userid' => '101', 'action' => 'send']);
  Check(str_contains(Emailed(), 'You have an account'), 'an invited account gets its invitation again');

  // Your profile: the password changes through a link; the email address never changes
  $Own = ['userid' => '100', 'ajax' => '1'];
  $Profile = Render('profile.php', Cookies: $Own);
  Check(str_contains($Profile, 'Your sign-in') && str_contains($Profile, 'create a new account') && !str_contains($Profile, 'name="new_email"'),
    'the profile keeps the email address: another address is another account');
  Check(preg_match('/<input[^>]*value="bob@example.com"[^>]*readonly/', $Profile) === 1, 'the address shows read only');
  NoErrors($Profile, 'the profile');
  $PasswordLink = Render('profile.php', Cookies: $Own, Query: ['action' => 'passwordlink']);
  Check(str_contains($PasswordLink, 'Check your email') && MailLink('resetpassword.php') !== '', 'emails a password link');
  Check(preg_match('/class="toast[^"]*notice/', $PasswordLink) === 1 && !str_contains($PasswordLink, 'alert-success'), 'saying so in a toast');
  $Move = Render('profile.php', ['new_email' => 'robert@example.com', 'email' => 'robert@example.com'], $Own, ['action' => 'save']);
  Check(Emailed() === '' && $Storage->Password(100)['email'] === 'bob@example.com', 'posting another address changes nothing');
  Check(!is_file(Config::FrameworkFolder() . '/confirmemail.php'), 'there\'s no page that changes it');

  // Create an account: the email's link, then names and a password make it, signed in
  $Ask = Render('signup.php');
  Check(str_contains($Ask, 'name="email"'), 'sign-up asks for an email address');
  NoErrors($Ask, 'sign-up');
  $Asked = Render('signup.php', ['email' => 'Dave@Example.com']);
  Check(str_contains($Asked, 'Check your email') && ($Link = MailLink('signup.php')) !== '', 'it emails a link');
  NoErrors($Asked, 'asking for a sign-up link');
  Same('dave@example.com', Password::SignUpEmail($Link), 'for that address');
  $Finish = Render('signup.php', Query: ['token' => $Link]);
  Check(str_contains($Finish, 'name="first_name"') && str_contains($Finish, 'name="password"'), 'the link asks for names and a password');
  NoErrors($Finish, 'the sign-up link');
  $Made = Render('signup.php', ['token' => $Link, 'first_name' => 'Dave', 'last_name' => '', 'password' => 'daves battery', 'repeat' => 'daves battery']);
  Check(str_contains($Made, 'Welcome!') && str_contains($Made, 'window.location'), 'makes the account and signs in');
  NoErrors($Made, 'making the account');
  $Weak = Render('signup.php', ['token' => $Link, 'first_name' => 'Dave', 'password' => 'short', 'repeat' => 'short']);
  Check(str_contains($Weak, 'at least 8 characters') && !str_contains($Weak, 'Welcome'), 'not with a short password');
  Same(null, Password::SignUpEmail((string)new Auth\JWT(['purpose' => 'password', 'email' => 'dave@example.com'], 600)), 'a password link is no sign-up link');
  Same(null, Password::SignUpEmail(Password::SignUpToken('ann@example.com')), 'nor for an address that has an account');
  Check(str_contains(Render('signup.php', ['email' => 'ann@example.com']), 'Check your email'), 'which gets the same answer');
  Check(str_contains(Emailed(), 'resetpassword.php') && !str_contains(Emailed(), 'signup.php?token'), 'but an email to reset the password instead');
  Check(str_contains(Render('signup.php', ['email' => 'not an address']), 'look right'), 'an address that isn\'t one');
