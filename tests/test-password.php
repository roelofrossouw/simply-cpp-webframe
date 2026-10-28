<?php

  // Signing in with a username and password (Auth\Password): the checks, the lock after wrong passwords, the session and
  // reset tokens, and the pages, each page in a process of its own as PHP runs it
  require __DIR__ . '/check.inc';
  require __DIR__ . '/memory.inc';

  use Auth\Password;

  // The storage every process starts with: user 100, bob, with a password and an email address
  function Storage(): MemoryStorage
  {
    Storage\Store::Use($Storage = new MemoryStorage());
    // One hash for every process, so a token made in one works in the others
    if (getenv('SC_WEBFRAME_TEST_HASH') === false)
    {
      putenv('SC_WEBFRAME_TEST_HASH=' . Password::Hash('right horse battery'));
    }
    $Storage->AddPasswordUser('Bob', 'bob@example.com', 'Bob', null, getenv('SC_WEBFRAME_TEST_HASH'));
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

  function Render(string $Page, array $Post = [], array $Cookies = [], array $Query = []): string
  {
    return (string)shell_exec(escapeshellarg(PHP_BINARY) . ' -d error_reporting=-1 -d display_errors=1 ' . escapeshellarg(__FILE__) . ' '
      . escapeshellarg($Page) . ' ' . escapeshellarg(json_encode([$Post, $Cookies, $Query])) . ' 2>&1');
  }

  function NoErrors(string $Html, string $What): void
  {
    Check(!preg_match('/Warning|Deprecated|Fatal|Something Went Wrong/', $Html), "$What without errors"
      . (preg_match('/(Warning|Deprecated|Fatal)[^\n]*/', $Html, $M) ? ': ' . $M[0] : ''));
  }

  Config::Load();
  $_SERVER['HTTP_HOST'] = 'www.example.com';
  $_SERVER['REMOTE_ADDR'] = '192.0.2.1';
  Check(Password::Configured(), 'on with auth.password.enabled');
  Same(8, Password::MinLength(), 'the shortest password, from the settings');

  // What a new password must be
  Same('A password needs at least 8 characters.', Password::Weakness('short'), 'too short');
  Same('The password can\'t be the username.', Password::Weakness('Bob-Smith', 'bob-smith '), 'not the username');
  Same(null, Password::Weakness('right horse battery', 'bob'), 'a good one');
  Check(Password::Weakness(str_repeat('x', Password::MAX_LENGTH + 1)) !== null, 'too long');

  // Signing in: by username or email, any case
  $Storage = Storage();
  Same(100, Password::Check('bob', 'right horse battery'), 'the right password');
  Same(100, Password::Check(' BOB@example.com', 'right horse battery'), 'by email address, any case');
  Same(null, Password::Check('bob', 'wrong horse battery'), 'a wrong password');
  Same(null, Password::Check('nobody', 'right horse battery'), 'an unknown user');
  Same('1', $Storage->Password(100)['failures'], 'the wrong password is counted');
  Same(100, Password::Check('bob', 'right horse battery'), 'the right one again');
  Same('0', $Storage->Password(100)['failures'], 'clears the count');
  Same(2, $Storage->AddressFailures('192.0.2.1', Password::LOCK_SECONDS), 'and the address\'s are kept, unknown users too');

  // Too many wrong passwords lock the user, the right one too, for a while
  for ($Try = 0; $Try < Password::LOCK_AFTER; $Try++)
  {
    Password::Check('bob', "wrong $Try");
  }
  Check(is_string(Password::Check('bob', 'right horse battery')), 'locked after ' . Password::LOCK_AFTER . ' wrong passwords');
  Check(!Password::Verify(100, 'right horse battery'), 'and a password change can\'t check it either');
  $Storage->Passwords[100]['last_failure'] = (string)(time() - Password::LOCK_SECONDS - 1);
  Same(100, Password::Check('bob', 'right horse battery'), 'unlocked once the time is up');

  // Too many from one address, across usernames
  $Storage->AddressFailures['192.0.2.1'] = array_fill(0, Password::ADDRESS_LIMIT, time());
  Check(is_string(Password::Check('bob', 'right horse battery')), 'an address with too many wrong passwords is refused');
  $Storage->AddressFailures = [];

  // Tokens work until the password changes
  $Session = (string)Password::Token(100, 'session', 600);
  Same(100, Password::TokenUser($Session, 'session'), 'a session token');
  Same(null, Password::TokenUser($Session, 'reset'), 'is no reset token');
  Same(null, Password::TokenUser(substr($Session, 0, -2) . 'xx', 'session'), 'a changed one doesn\'t work');
  Same(null, Password::TokenUser((string)new Auth\JWT(['purpose' => 'session', 'uid' => 100, 'pv' => 'guess'], 600), 'session'), 'nor a guessed fingerprint');
  Same(null, Password::Token(7, 'session', 600), 'a GitHub user has none');
  Same(null, Password::Set(100, 'a new horse battery'), 'set a new password');
  Same(null, Password::TokenUser($Session, 'session'), 'which ends the session');
  Check(Password::Verify(100, 'a new horse battery') && !Password::Verify(100, 'right horse battery'), 'the new password is the one');
  Same('This user doesn\'t sign in with a password.', Password::Set(7, 'a new horse battery'), 'a GitHub user\'s can\'t be set');
  Same('Someone already has the username bob.', $Storage->AddPasswordUser('bob', null, null, null, Password::Hash('xxxxxxxxxx')), 'usernames are unique');

  // The login page: the form, and posting it
  $Form = Render('login.php', Query: ['provider' => 'password']);
  Check(str_contains($Form, 'name="username"') && str_contains($Form, 'name="password"') && str_contains($Form, 'value="password.'), 'the sign-in form');
  Check(str_contains($Form, 'resetpassword.php'), 'with Forgot your password?');
  NoErrors($Form, 'the sign-in form');
  $Choice = Render('login.php');
  Check(str_contains($Choice, 'name="username"') && str_contains($Choice, 'Sign in with GitHub'), 'without a provider: the form, and the other providers');

  $State = ['state' => 'password.0123456789abcdef'];
  $Right = Render('login.php', $State + ['username' => 'bob', 'password' => 'right horse battery'], ['oauth_state' => $State['state']]);
  Check(str_contains($Right, 'window.location'), 'the right password signs in');
  NoErrors($Right, 'signing in');
  $Wrong = Render('login.php', $State + ['username' => 'bob', 'password' => 'wrong'], ['oauth_state' => $State['state']]);
  Check(str_contains($Wrong, 'isn&#039;t right') || str_contains($Wrong, 'isn\'t right'), 'a wrong one shows the form again, saying so');
  Check(!str_contains($Wrong, 'window.location'), 'and doesn\'t sign in');
  NoErrors($Wrong, 'a wrong password');
  $Forged = Render('login.php', $State + ['username' => 'bob', 'password' => 'right horse battery'], ['oauth_state' => 'password.other']);
  Check(str_contains($Forged, 'expired') && !str_contains($Forged, 'window.location'), 'a form from elsewhere doesn\'t sign in');

  // Forgot your password?
  $Reset = Render('resetpassword.php');
  Check(str_contains($Reset, 'name="name"'), 'the reset page asks for the username');
  NoErrors($Reset, 'the reset page');
  $Sent = Render('resetpassword.php', ['name' => 'nobody']);
  Check(str_contains($Sent, 'Check your email'), 'the same answer for an unknown user');
  NoErrors($Sent, 'asking for a link');
  // The other processes start from the first password again
  Storage();
  $Link = (string)Password::Token(100, 'reset', 600);
  $Choose = Render('resetpassword.php', Query: ['token' => $Link]);
  Check(str_contains($Choose, 'Choose a new password for bob') && str_contains($Choose, 'name="token"'), 'the link asks for a new password');
  NoErrors($Choose, 'the link');
  $Changed = Render('resetpassword.php', ['token' => $Link, 'password' => 'brand new battery', 'repeat' => 'brand new battery']);
  Check(str_contains($Changed, 'Password changed'), 'and changes it');
  NoErrors($Changed, 'changing it');
  $Old = Render('resetpassword.php', Query: ['token' => (string)new Auth\JWT(['purpose' => 'reset', 'uid' => 100, 'pv' => 'old'], 600)]);
  Check(str_contains($Old, 'doesn&#039;t work') || str_contains($Old, 'doesn\'t work'), 'a used or old link doesn\'t work');

  // Administer → Users: add a user, set a password
  $Signed = ['userid' => '7', 'ajax' => '1'];
  $Users = Render('admin/users.php', Cookies: $Signed);
  Check(str_contains($Users, 'Add a user') && str_contains($Users, 'password.php"'), 'Users can add a user');
  Check(str_contains($Users, 'Password (bob)') && str_contains($Users, 'password.php?userid=100'), 'and set a password user\'s password');
  NoErrors($Users, 'Users');
  $Add = Render('admin/password.php', Cookies: $Signed);
  Check(str_contains($Add, 'Add a user') && str_contains($Add, 'name="username"'), 'the Add a user popup');
  NoErrors($Add, 'Add a user');
  $Added = Render('admin/password.php', ['username' => 'carol', 'email' => 'carol@example.com', 'first_name' => 'Carol', 'last_name' => '',
    'password' => 'carols battery', 'repeat' => 'carols battery'], $Signed, ['action' => 'save']);
  Check(str_contains($Added, 'User added'), 'adds a user');
  NoErrors($Added, 'adding a user');
  $Taken = Render('admin/password.php', ['username' => 'BOB', 'email' => '', 'password' => 'bobs battery', 'repeat' => 'bobs battery'], $Signed, ['action' => 'save']);
  Check(str_contains($Taken, 'already has the username'), 'but not a username that\'s taken');
  $Set = Render('admin/password.php', ['password' => 'new battery', 'repeat' => 'new battery'], $Signed, ['action' => 'save', 'userid' => '100']);
  Check(str_contains($Set, 'Password set'), 'sets a user\'s password');
  NoErrors($Set, 'setting a password');
  $Short = Render('admin/password.php', ['password' => 'short', 'repeat' => 'short'], $Signed, ['action' => 'save', 'userid' => '100']);
  Check(str_contains($Short, 'at least 8 characters'), 'not a short one');

  // Your profile, signed in with a password
  $Own = ['userid' => '100', 'ajax' => '1', 'password_session' => (string)Password::Token(100, 'session', 600)];
  $Profile = Render('profile.php', Cookies: $Own);
  Check(str_contains($Profile, 'Your password') && str_contains($Profile, 'name="current_password"'), 'the profile changes your password');
  NoErrors($Profile, 'the profile');
  $Change = Render('profile.php', ['current_password' => 'right horse battery', 'new_password' => 'my own battery', 'repeat_password' => 'my own battery'],
    $Own, ['action' => 'password']);
  Check(str_contains($Change, 'Password changed'), 'with the current one');
  NoErrors($Change, 'changing your password');
  $Guess = Render('profile.php', ['current_password' => 'a guess', 'new_password' => 'my own battery', 'repeat_password' => 'my own battery'],
    $Own, ['action' => 'password']);
  Check(str_contains($Guess, 'current password isn'), 'not without it');
