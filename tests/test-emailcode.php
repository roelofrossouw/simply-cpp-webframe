<?php

  // Signing in with emailed codes (Auth\EmailCode) and passkeys (Auth\Passkey): the codes, the locks, the hooks, the
  // pages, and the passkey checks against a stand-in SC\Passkey when sc-phplib isn't there. Each page runs in a process
  // of its own, as PHP runs it; they share the storage through a file (SC_WEBFRAME_TEST_STATE) and their emails are
  // caught in another.
  require __DIR__ . '/check.inc';
  require __DIR__ . '/memory.inc';

  use Auth\{EmailCode, Passkey};

  // A stand-in for sc-phplib's SC\Passkey: its "answers" are JSON naming the challenge they sign and the key they use
  if (!class_exists('SC\Passkey'))
  {
    eval('namespace SC; class Passkey {
      public function __construct(public string $RpId, public string $RpName, public array $Origins) {}
      public function authenticationOptions(array $allow = []): array
      { return ["challenge" => bin2hex(random_bytes(8)), "rpId" => $this->RpId, "allowCredentials" => $allow, "timeout" => 300000]; }
      public function registrationOptions(string $id, string $name, string $display = "", array $exclude = []): array
      { return ["challenge" => bin2hex(random_bytes(8)), "rp" => ["id" => $this->RpId, "name" => $this->RpName],
          "user" => ["id" => $id, "name" => $name, "displayName" => $display], "excludeCredentials" => $exclude]; }
      public function verifyRegistration(string $response, string $challenge): array
      { $a = json_decode($response, true); if (($a["challenge"] ?? "") !== $challenge) throw new \Exception("challenge");
        return ["id" => $a["id"], "publicKey" => "key-" . $a["id"], "signCount" => 0, "transports" => ["internal"], "backedUp" => true, "aaguid" => ""]; }
      public function verifyAuthentication(string $response, string $challenge, string $publicKey, int $signCount): array
      { $a = json_decode($response, true); if (($a["challenge"] ?? "") !== $challenge) throw new \Exception("challenge");
        if ($publicKey !== "key-" . $a["id"]) throw new \Exception("signature");
        return ["id" => $a["id"], "userHandle" => null, "signCount" => $signCount + 1, "userVerified" => true, "backupEligible" => true, "backedUp" => true]; }
      public static function credentialId(string $response): string
      { $a = json_decode($response, true); if (!isset($a["id"])) throw new \Exception("format"); return $a["id"]; }
    }');
  }

  // A site's hooks: codes go to a file instead of an email, and each address shown to work is noted there too
  class TestEmailHooks implements Auth\EmailCodeHooks
  {
    public function SendCode(string $Email, string $Code, int $Minutes): ?bool
    {
      file_put_contents(getenv('SC_WEBFRAME_TEST_HOOKS'), "code $Email $Code $Minutes\n", FILE_APPEND);
      return true;
    }

    public function Verified(int $UserID, string $Email, bool $NewAccount): void
    {
      file_put_contents(getenv('SC_WEBFRAME_TEST_HOOKS'), "verified $UserID $Email " . ($NewAccount ? 'new' : 'old') . "\n", FILE_APPEND);
    }
  }

  $Page = $argv[1] ?? null;
  if ($Page !== null)
  {
    // One page: posted values, cookies, the query string and settings as JSON, signed in as $Cookies['userid'] when given
    $Checks = -1; // This process only prints the page
    $_SERVER['HTTP_HOST'] = 'www.example.com';
    $_SERVER['REMOTE_ADDR'] = '192.0.2.1';
    $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = Config::FrameworkURL($Page);
    [$_POST, $_COOKIE, $_GET, $Settings] = json_decode($argv[2], true);
    if (!empty($_COOKIE['ajax']))
    {
      $_SERVER['HTTP_X_REQUESTED_BY'] = 'sc-webframe';
    }
    Config::Load();
    foreach ($Settings as $Path => $Value)
    {
      Config::Set($Path, $Value);
    }
    if (!empty($_COOKIE['PHPSESSID']))
    {
      session_id($_COOKIE['PHPSESSID']); // The passkey challenges are kept in the session
    }
    Storage\Store::Use($Storage = new MemoryStorage());
    if (!empty($_COOKIE['userid']))
    {
      $ID = (int)$_COOKIE['userid'];
      $_COOKIE['auth_token'] = (string)new Auth\JWT(['userid' => (string)$ID, 'access' => ['public'], 'provider' => 'email'], 600);
      $_COOKIE['auth_provider'] = 'email';
    }
    include Config::FrameworkFolder() . '/' . $Page;
    return;
  }

  // Shared by the pages: the storage, the hooks' notes, the emails (through a sendmail of our own), the sessions
  $State = tempnam(sys_get_temp_dir(), 'sc-webframe-state');
  $HookFile = tempnam(sys_get_temp_dir(), 'sc-webframe-hooks');
  $MailFile = tempnam(sys_get_temp_dir(), 'sc-webframe-mail');
  $Sessions = sys_get_temp_dir() . '/sc-webframe-sessions-' . getmypid();
  mkdir($Sessions);
  $Sendmail = "$MailFile.sh";
  file_put_contents($Sendmail, "#!/bin/sh\ncat >> " . escapeshellarg($MailFile) . "\n");
  chmod($Sendmail, 0700);
  putenv("SC_WEBFRAME_TEST_STATE=$State");
  putenv("SC_WEBFRAME_TEST_HOOKS=$HookFile");
  register_shutdown_function(function () use ($State, $HookFile, $MailFile, $Sendmail, $Sessions) {
    array_map('unlink', array_merge([$State, $HookFile, $MailFile, $Sendmail], glob("$Sessions/*") ?: []));
    rmdir($Sessions);
  });

  // The settings the pages get: emailed codes on, with sign-up; passkeys on
  $On = ['auth.email' => ['enabled' => true, 'signup' => true, 'code_minutes' => 10, 'resend_seconds' => 60],
    'auth.passkey' => ['enabled' => true, 'rp_id' => 'www.example.com', 'origins' => ['https://www.example.com']]];

  function Render(string $Page, array $Post = [], array $Cookies = [], array $Query = [], array $Settings = []): string
  {
    global $Sendmail, $MailFile, $On, $Sessions;
    file_put_contents($MailFile, '');
    return (string)shell_exec(escapeshellarg(PHP_BINARY) . ' -d error_reporting=-1 -d display_errors=1 -d sendmail_path=' . escapeshellarg($Sendmail)
      . ' -d session.save_path=' . escapeshellarg($Sessions) . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($Page) . ' '
      . escapeshellarg(json_encode([$Post, $Cookies, $Query, $Settings + $On])) . ' 2>&1');
  }

  // The code in the last email, or ''
  function MailedCode(): string
  {
    global $MailFile;
    return preg_match('/code: (\d{6})/', quoted_printable_decode((string)file_get_contents($MailFile)), $Found) ? $Found[1] : '';
  }

  // A page's JSON, without the line the page's process ends with
  function Json(string $Output): mixed
  {
    return json_decode(preg_replace('/-1 checks, 0 failed\s*$/', '', $Output), true);
  }

  function Stored(): MemoryStorage
  {
    return new MemoryStorage();
  }

  function NoErrors(string $Html, string $What): void
  {
    Check(!preg_match('/Warning|Deprecated|Fatal|Something Went Wrong/', $Html), "$What without errors"
      . (preg_match('/(Warning|Deprecated|Fatal)[^\n]*/', $Html, $M) ? ': ' . $M[0] : ''));
  }

  $Login = fn(array $Post = [], array $Cookies = [], array $Settings = []) => Render('login.php', $Post, $Cookies, ['provider' => 'email'], $Settings);
  $Signed = fn(string $Email, string $Code, array $Settings = []) => $Login(['state' => 'email.0123456789abcdef', 'email' => $Email, 'code' => $Code],
    ['oauth_state' => 'email.0123456789abcdef'], $Settings);
  $Wrong = fn(string $Code) => str_pad((string)(((int)$Code + 1) % 1000000), 6, '0', STR_PAD_LEFT);

  // The first step: the email address
  $First = $Login();
  Check(str_contains($First, 'name="email"') && str_contains($First, 'Next') && !str_contains($First, 'name="code"'), 'the first step asks for the email address');
  Check(str_contains($First, 'The code makes one'), 'and says a code makes an account');
  NoErrors($First, 'the first step');
  $Choice = Render('login.php');
  Check(str_contains($Choice, 'provider=email') && str_contains($Choice, 'Sign in with a passkey') && str_contains($Choice, 'js/sc-passkey.js')
    && str_contains($Choice, 'passkeySignIn('), 'the choice: the email step, and the passkey button with its scripts');
  NoErrors($Choice, 'the choice');

  // A new address: a code by email, the code step
  $Sent = $Login(['email' => ' New@Example.com ']);
  Check(str_contains($Sent, 'Check your email') && str_contains($Sent, 'new@example.com') && str_contains($Sent, 'name="code"')
    && str_contains($Sent, 'data-bs-otp'), 'an address gets a code, and the code step');
  Check(str_contains($Sent, 'Send another code in') && str_contains($Sent, 'disabled') && str_contains($Sent, 'name="resend"'),
    'Send another code waits, counting down');
  Check(($Code = MailedCode()) !== '' && str_contains(quoted_printable_decode((string)file_get_contents($MailFile)), 'To: new@example.com'), 'the code is emailed to it');
  NoErrors($Sent, 'the code step');
  Check(Stored()->EmailCode('new@example.com') !== null && !password_verify($Code, '') && password_verify($Code, Stored()->EmailCode('new@example.com')['hash']),
    'the code is kept as a hash');
  $Again = $Login(['email' => 'new@example.com', 'resend' => '1']);
  Check(str_contains($Again, 'ask for another in') && MailedCode() === '', 'another code only after the wait');

  // The code: a wrong one, then the right one, which makes the account and shows the address works
  $Bad = $Signed('new@example.com', $Wrong($Code));
  Check(str_contains($Bad, 'isn&#039;t right') || str_contains($Bad, 'isn\'t right'), 'a wrong code says so');
  Check(!str_contains($Bad, 'window.location') && str_contains($Bad, 'name="code"'), 'and asks again');
  Same('1', Stored()->EmailCode('new@example.com')['failures'], 'it\'s counted');
  NoErrors($Bad, 'a wrong code');
  $Forged = $Login(['state' => 'email.0123456789abcdef', 'email' => 'new@example.com', 'code' => $Code], ['oauth_state' => 'email.other']);
  Check(str_contains($Forged, 'expired') && !str_contains($Forged, 'window.location'), 'a form from elsewhere doesn\'t sign in');
  $Right = $Signed('new@example.com', $Code);
  Check(str_contains($Right, 'window.location'), 'the right code signs in');
  NoErrors($Right, 'the right code');
  $Storage = Stored();
  Check(($NewID = $Storage->EmailCodeUserID('new@example.com')) !== null, 'and makes the account');
  Check(isset($Storage->Verified[$NewID]), 'whose address is shown to work');
  Same(null, $Storage->EmailCode('new@example.com'), 'the code is used up');
  $Reused = $Signed('new@example.com', $Code);
  Check(!str_contains($Reused, 'window.location'), 'and works once');

  // Too many wrong codes lock the address, the right code too
  $Login(['email' => 'lock@example.com']);
  $Code = MailedCode();
  for ($Try = 0; $Try < EmailCode::LOCK_AFTER; $Try++)
  {
    $Signed('lock@example.com', $Wrong($Code));
  }
  $Locked = $Signed('lock@example.com', $Code);
  Check(str_contains($Locked, 'Too many wrong codes') && !str_contains($Locked, 'window.location'), 'locked after ' . EmailCode::LOCK_AFTER . ' wrong codes');
  Check(str_contains($Login(['email' => 'lock@example.com']), 'Too many wrong codes') && MailedCode() === '', 'and gets no new code meanwhile');
  $Storage = Stored();
  $Storage->Codes['lock@example.com']['last_failure'] = (string)(time() - EmailCode::LOCK_SECONDS - 1);
  $Storage->Save();
  Check(str_contains($Signed('lock@example.com', $Code), 'window.location'), 'unlocked once the time is up');

  // An expired code
  $Storage = Stored();
  $Storage->Codes = [];
  $Storage->AddressFailures = [];
  $Storage->Save();
  $Login(['email' => 'late@example.com']);
  $Code = MailedCode();
  $Storage = Stored();
  $Storage->Codes['late@example.com']['expires'] = (string)(time() - 1);
  $Storage->Save();
  Check(str_contains($Signed('late@example.com', $Code), 'expired'), 'an expired code says so');

  // Without sign-up: an unknown address gets the same page, but no email
  $Closed = ['auth.email' => ['enabled' => true, 'signup' => false]];
  $Quiet = $Login(['email' => 'stranger@example.com'], [], $Closed);
  Check(str_contains($Quiet, 'Check your email') && str_contains($Quiet, 'name="code"'), 'without sign-up an unknown address gets the same page');
  Check(MailedCode() === '' && Stored()->EmailCode('stranger@example.com') === null, 'but no code');
  Check(str_contains($Login([], [], $Closed), 'We&#039;ll email you a code to sign in.') || str_contains($Login([], [], $Closed), 'email you a code to sign in.'),
    'and doesn\'t offer to make an account');
  $Known = $Login(['email' => 'new@example.com'], [], $Closed);
  Check(MailedCode() !== '', 'an account still gets its code');

  // Too many tries from one IP address
  $Storage = Stored();
  $Storage->AddressFailures['192.0.2.1'] = array_fill(0, Auth\Password::ADDRESS_LIMIT, time());
  $Storage->Save();
  Check(str_contains($Login(['email' => 'any@example.com']), 'Too many tries from here') && MailedCode() === '', 'an IP address with too many tries gets no code');
  $Storage->AddressFailures = [];
  $Storage->Save();

  // The site's hooks: they send the code, and hear once when an address is shown to work
  $Hooks = ['auth.email' => ['enabled' => true, 'signup' => true, 'hooks' => 'TestEmailHooks']];
  $Login(['email' => 'hooked@example.com'], [], $Hooks);
  Check(MailedCode() === '' && preg_match('/^code hooked@example\.com (\d{6}) 10$/m', (string)file_get_contents($HookFile), $Found) === 1,
    'the hooks send the code instead of the framework');
  Check(str_contains($Signed('hooked@example.com', $Found[1], $Hooks), 'window.location'), 'which signs in');
  Check(preg_match('/^verified \d+ hooked@example\.com new$/m', (string)file_get_contents($HookFile)) === 1, 'and the hooks hear the new address works');
  file_put_contents($HookFile, '');
  $Storage = Stored();
  $Storage->AddressFailures = [];
  $Storage->Save();
  $Login(['email' => 'hooked@example.com', 'resend' => '1'], [], $Hooks);
  Check(preg_match('/^code hooked@example\.com (\d{6})/m', (string)file_get_contents($HookFile), $Found) === 1, 'a second sign-in gets a code');
  $Signed('hooked@example.com', $Found[1], $Hooks);
  Check(!str_contains((string)file_get_contents($HookFile), 'verified'), 'but the hooks hear of the address once');

  // The session cookie's token
  Config::Load();
  $_SERVER['HTTP_HOST'] = 'www.example.com';
  $Session = (string)EmailCode::SessionToken(205);
  Same(205, EmailCode::SessionUser($Session), 'a session token names its account');
  Same(null, EmailCode::SessionUser(substr($Session, 0, -2) . 'xx'), 'a changed one doesn\'t');
  Same(null, EmailCode::SessionUser((string)new Auth\JWT(['purpose' => 'password', 'uid' => 205], 600)), 'nor another kind');

  // Your profile: an email account's name is its own, and the passkeys part
  $Profile = Render('profile.php', Cookies: ['userid' => (string)$NewID, 'ajax' => '1']);
  Check(str_contains($Profile, 'a code emailed to you') && str_contains($Profile, 'Passkeys') && str_contains($Profile, 'Add a passkey')
    && str_contains($Profile, 'passkeyAdd(') && str_contains($Profile, 'passkey-save'), 'the profile: emailed codes, and Add a passkey');
  NoErrors($Profile, 'the profile');

  // Passkeys: options, a new one, signing in with it, each challenge once
  $Session = ['PHPSESSID' => 'scwebframetest' . getmypid(), 'userid' => (string)$NewID, 'ajax' => '1'];
  $Options = Json(Render('passkey.php', Cookies: $Session, Query: ['action' => 'register']));
  Check(isset($Options['challenge']) && $Options['user']['name'] === 'new@example.com', 'options for a new passkey, for the account');
  $Added = Render('profile.php', ['passkey' => json_encode(['id' => 'cred-1', 'challenge' => $Options['challenge']]), 'passkey_name' => 'My phone'],
    $Session, ['action' => 'addpasskey']);
  Check(str_contains($Added, 'Passkey added') && str_contains($Added, 'My phone'), 'a new passkey is kept, and listed');
  NoErrors($Added, 'adding a passkey');
  $Twice = Render('profile.php', ['passkey' => json_encode(['id' => 'cred-2', 'challenge' => $Options['challenge']])], $Session, ['action' => 'addpasskey']);
  Check(str_contains($Twice, 'Passkey not added') && str_contains($Twice, 'expired'), 'a challenge works once');
  Same(1, count(Stored()->Passkeys($NewID)), 'one passkey kept');

  $Anonymous = ['PHPSESSID' => 'scwebframesignin' . getmypid(), 'ajax' => '1'];
  $SignIn = Json(Render('passkey.php', Cookies: $Anonymous, Query: ['action' => 'signin']));
  Check(isset($SignIn['challenge']) && $SignIn['rpId'] === 'www.example.com' && $SignIn['allowCredentials'] === [], 'sign-in options: any passkey for the site');
  $In = Render('login.php', ['passkey' => json_encode(['id' => 'cred-1', 'challenge' => $SignIn['challenge']])], $Anonymous, ['provider' => 'passkey']);
  Check(str_contains($In, 'window.location'), 'the passkey signs in');
  NoErrors($In, 'signing in with a passkey');
  Same('1', Stored()->Passkey('cred-1')['sign_count'], 'its sign count is kept');
  $Replay = Render('login.php', ['passkey' => json_encode(['id' => 'cred-1', 'challenge' => $SignIn['challenge']])], $Anonymous, ['provider' => 'passkey']);
  Check(str_contains($Replay, 'expired') && !str_contains($Replay, 'window.location'), 'an answer works once');
  $Again = Json(Render('passkey.php', Cookies: $Anonymous, Query: ['action' => 'signin']));
  $Unknown = Render('login.php', ['passkey' => json_encode(['id' => 'cred-9', 'challenge' => $Again['challenge']])], $Anonymous, ['provider' => 'passkey']);
  Check(str_contains($Unknown, 'isn&#039;t one of this site') || str_contains($Unknown, 'isn\'t one of this site'), 'an unknown passkey says so');
  Check(str_contains($Unknown, 'Sign in with a passkey'), 'with the button to try again');
  NoErrors($Unknown, 'an unknown passkey');
  Same(401, Json(Render('passkey.php', Cookies: $Anonymous, Query: ['action' => 'register'])) === ['error' => 'Please sign in first.'] ? 401 : 0,
    'a new passkey needs a signed in user');

  $KeyID = (int)Stored()->Passkeys($NewID)[0]['id'];
  $Removed = Render('profile.php', Cookies: $Session, Query: ['action' => 'removepasskey', 'id' => (string)$KeyID]);
  Check(str_contains($Removed, 'Passkey removed') && Stored()->Passkeys($NewID) === [], 'a passkey is removed');
  $Others = Render('profile.php', Cookies: ['userid' => '7', 'ajax' => '1'], Query: ['action' => 'removepasskey', 'id' => '99']);
  Check(str_contains($Others, 'Not removed'), 'but only the user\'s own');

  // Off: no passkey button, and passkey.php says so
  $Off = ['auth.passkey' => ['enabled' => false]];
  Check(!str_contains(Render('login.php', Settings: $Off), 'Sign in with a passkey'), 'off, no passkey button');

  // BS\Stepper
  $Stepper = new BS\Stepper(['Your details', 'Firearm', 'Cover', 'Confirm'], 3);
  $Stepper->Links(['/apply.php?step=1', '/apply.php?step=2']);
  $Html = (string)$Stepper;
  Check(str_contains($Html, 'Step 3 of 4: Cover') && str_contains($Html, 'd-sm-none'), 'the stepper says the step on a phone');
  Same(2, substr_count($Html, 'stepper-done'), 'two steps done');
  Check(str_contains($Html, 'aria-current="step"') && str_contains($Html, 'href="/apply.php?step=2"') && !str_contains($Html, 'step=3'),
    'the current step, and links back to the ones done');
  Check(str_contains($Html, 'aria-valuenow="3"') && str_contains($Html, 'aria-valuemax="4"'), 'over a progress bar');
  Same($Html, (string)$Stepper, 'shown twice, the same');
