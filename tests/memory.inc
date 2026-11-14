<?php

  use Storage\Backend;

  // A storage in memory: the schema's menu, and one user. Emailed codes, email accounts and passkeys are kept in the file
  // SC_WEBFRAME_TEST_STATE names, when it's set, so a test's pages (each a process of its own) share them.
  class MemoryStorage implements Backend, Storage\EmailCodes, Storage\Passkeys
  {
    public array $Grants = [7 => ['public']];
    // Email accounts by id (email), codes by address, verified accounts, passkeys by id
    public array $EmailAccounts = [];
    public array $Codes = [];
    public array $Verified = [];
    public array $Keys = [];

    public function __construct()
    {
      $File = getenv('SC_WEBFRAME_TEST_STATE');
      if ($File !== false && is_file($File) && is_array($State = json_decode((string)file_get_contents($File), true)))
      {
        foreach (['EmailAccounts', 'Codes', 'Verified', 'Keys', 'AddressFailures'] as $Key)
        {
          $this->$Key = $State[$Key] ?? [];
        }
        foreach ($this->EmailAccounts as $ID => $Email)
        {
          $this->Grants[$ID] = ['public'];
        }
      }
    }

    public function Save(): void
    {
      if (($File = getenv('SC_WEBFRAME_TEST_STATE')) !== false)
      {
        file_put_contents($File, json_encode(['EmailAccounts' => $this->EmailAccounts, 'Codes' => $this->Codes, 'Verified' => $this->Verified,
          'Keys' => $this->Keys, 'AddressFailures' => $this->AddressFailures]));
      }
    }

    public function SignIn(string $Provider, string $Subject, ?string $Email, ?string $FirstName, ?string $LastName): array
    {
      if ($Provider == 'email')
      {
        $ID = $this->EmailCodeUserID($Subject) ?? 200 + count($this->EmailAccounts);
        $this->EmailAccounts[$ID] = mb_strtolower($Subject);
        $this->Grants[$ID] ??= ['public'];
        $this->Save();
        return ['user_id' => (string)$ID, 'email' => $this->EmailAccounts[$ID], 'first_name' => null, 'last_name' => null, 'access' => $this->Grants[$ID]];
      }
      if ($Provider == 'password' && ($ID = $this->PasswordUserID($Subject)) !== null)
      {
        return ['user_id' => (string)$ID, 'email' => $this->Passwords[$ID]['email'], 'first_name' => $FirstName, 'last_name' => $LastName,
          'access' => $this->Grants[$ID]];
      }
      return ['user_id' => '7', 'email' => $Email, 'first_name' => $FirstName, 'last_name' => $LastName, 'access' => $this->Grants[7]];
    }
    public function Access(int $UserID): array { return $this->Grants[$UserID] ?? []; }
    public function Levels(): array { return ['public' => 'Any signed in user', 'admin' => 'System administrator']; }
    public function Users(): array
    {
      return [['user_id' => '7', 'provider' => 'github', 'email' => 'ann@example.com', 'first_name' => 'Ann', 'last_name' => 'Example',
        'created' => '2026-10-28 10:00:00+02', 'last_login' => '2026-10-28 10:00:00+02', 'access' => $this->Grants[7], 'verified' => null]]
        + array_map(fn($ID) => ['user_id' => (string)$ID, 'provider' => 'password', 'verified' => $this->Passwords[$ID]['hash'] === null ? 'f' : 't',
          'email' => $this->Passwords[$ID]['email'], 'first_name' => 'Bob', 'last_name' => null, 'created' => '2026-10-28 10:00:00+02',
          'last_login' => null, 'access' => $this->Grants[$ID]], array_combine(array_keys($this->Passwords), array_keys($this->Passwords)));
    }
    public function SetAccess(int $UserID, string $Code, bool $Granted): ?string { return null; }
    public function Profile(int $UserID): ?array
    {
      if (isset($this->EmailAccounts[$UserID]))
      {
        return ['user_id' => (string)$UserID, 'provider' => 'email', 'email' => $this->EmailAccounts[$UserID], 'first_name' => null,
          'last_name' => null, 'display_as' => 'first', 'nickname' => null, 'names_from_user' => 'f', 'photo' => null, 'wants_provider_photo' => 'f',
          'created' => '2026-11-14 10:00:00+02', 'last_login' => null, 'photo_source' => null];
      }
      if (isset($this->Passwords[$UserID]))
      {
        return ['user_id' => (string)$UserID, 'provider' => 'password', 'email' => $this->Passwords[$UserID]['email'], 'first_name' => 'Bob',
          'last_name' => null, 'display_as' => 'first', 'nickname' => null, 'names_from_user' => 't', 'photo' => null, 'wants_provider_photo' => 'f',
          'created' => '2026-10-28 10:00:00+02', 'last_login' => null, 'photo_source' => null];
      }
      return ['user_id' => (string)$UserID, 'provider' => 'github', 'email' => 'ann@example.com', 'first_name' => 'Ann', 'last_name' => 'Example',
        'display_as' => 'first', 'nickname' => null, 'names_from_user' => 'f', 'photo' => null, 'wants_provider_photo' => 'f',
        'created' => '2026-10-28 10:00:00+02', 'last_login' => null, 'photo_source' => null];
    }
    public function SetDisplay(int $UserID, string $DisplayAs, ?string $Nickname): ?string { return null; }
    public function SetName(int $UserID, ?string $FirstName, ?string $LastName): ?string { return null; }
    public function SetPhoto(int $UserID, string $Jpeg, string $Source): ?string { return null; }
    public function RemovePhoto(int $UserID): ?string { return null; }
    // Password accounts by id: email, hash (null until chosen), failures, last_failure; and wrong passwords by address (times)
    public array $Passwords = [];
    public array $AddressFailures = [];
    public function PasswordUserID(string $Email): ?int
    {
      $Email = mb_strtolower(trim($Email));
      foreach ($this->Passwords as $ID => $User)
      {
        if ($User['email'] === $Email)
        {
          return $ID;
        }
      }
      return null;
    }
    public function Password(int $UserID): ?array
    {
      return isset($this->Passwords[$UserID]) ? ['user_id' => (string)$UserID] + $this->Passwords[$UserID] : null;
    }
    public function AddPasswordUser(string $Email, ?string $FirstName, ?string $LastName, ?string $Hash): int|string
    {
      $Email = mb_strtolower(trim($Email));
      if ($this->PasswordUserID($Email) !== null)
      {
        return "There is an account for $Email already.";
      }
      $ID = 100 + count($this->Passwords);
      $this->Passwords[$ID] = ['email' => $Email, 'hash' => $Hash, 'failures' => '0', 'last_failure' => null];
      $this->Grants[$ID] = ['public'];
      return $ID;
    }
    public function SetPassword(int $UserID, string $Hash): ?string
    {
      if (!isset($this->Passwords[$UserID]))
      {
        return 'This user doesn\'t sign in with a password.';
      }
      $this->Passwords[$UserID] = ['hash' => $Hash, 'failures' => '0', 'last_failure' => null] + $this->Passwords[$UserID];
      return null;
    }
    public function PasswordFailed(?int $UserID, string $Address): void
    {
      if ($UserID !== null && isset($this->Passwords[$UserID]))
      {
        $this->Passwords[$UserID]['failures'] = (string)($this->Passwords[$UserID]['failures'] + 1);
        $this->Passwords[$UserID]['last_failure'] = (string)time();
      }
      $this->AddressFailures[$Address][] = time();
      $this->Save();
    }
    public function AddressFailures(string $Address, int $Seconds): int
    {
      return count(array_filter($this->AddressFailures[$Address] ?? [], fn($At) => $At > time() - $Seconds));
    }
    public function EmailCodeUserID(string $Email): ?int
    {
      $ID = array_search(mb_strtolower(trim($Email)), $this->EmailAccounts, true);
      return $ID === false ? null : (int)$ID;
    }
    public function EmailCode(string $Email): ?array { return $this->Codes[mb_strtolower(trim($Email))] ?? null; }
    public function SaveEmailCode(string $Email, string $Hash, int $Expires): void
    {
      $Email = mb_strtolower(trim($Email));
      $this->Codes[$Email] = ['hash' => $Hash, 'expires' => (string)$Expires, 'sent' => (string)time()]
        + ($this->Codes[$Email] ?? []) + ['failures' => '0', 'last_failure' => null];
      $this->Save();
    }
    public function EmailCodeFailed(string $Email, string $Address): void
    {
      $Email = mb_strtolower(trim($Email));
      $Code = ($this->Codes[$Email] ?? []) + ['hash' => null, 'expires' => null, 'sent' => null, 'failures' => '0'];
      $this->Codes[$Email] = ['failures' => (string)($Code['failures'] + 1), 'last_failure' => (string)time()] + $Code;
      $this->PasswordFailed(null, $Address);
    }
    public function EmailCodeUsed(string $Email): void
    {
      unset($this->Codes[mb_strtolower(trim($Email))]);
      $this->Save();
    }
    public function EmailVerified(int $UserID): bool
    {
      if (isset($this->Verified[$UserID]))
      {
        return false;
      }
      $this->Verified[$UserID] = true;
      $this->Save();
      return true;
    }
    public function Passkeys(int $UserID): array
    {
      return array_values(array_filter(array_map(fn($ID, $Key) => ['id' => (string)$ID] + $Key, array_keys($this->Keys), $this->Keys),
        fn($Key) => (int)$Key['user_id'] === $UserID));
    }
    public function Passkey(string $CredentialID): ?array
    {
      foreach ($this->Keys as $ID => $Key)
      {
        if ($Key['credential_id'] === $CredentialID)
        {
          return ['id' => (string)$ID] + $Key;
        }
      }
      return null;
    }
    public function AddPasskey(int $UserID, array $Credential, string $Name): ?string
    {
      if ($this->Passkey($Credential['id']) !== null)
      {
        return 'That passkey is kept already.';
      }
      $this->Keys[count($this->Keys) + 1] = ['user_id' => (string)$UserID, 'credential_id' => $Credential['id'], 'public_key' => $Credential['publicKey'],
        'sign_count' => (string)$Credential['signCount'], 'transports' => $Credential['transports'] ?? [], 'name' => $Name ?: null,
        'created' => '2026-11-14 10:00:00+02', 'last_used' => null];
      $this->Save();
      return null;
    }
    public function PasskeyUsed(int $ID, int $SignCount, bool $BackedUp): void
    {
      $this->Keys[$ID]['sign_count'] = (string)$SignCount;
      $this->Keys[$ID]['last_used'] = '2026-11-14 11:00:00+02';
      $this->Save();
    }
    public function RemovePasskey(int $UserID, int $ID): ?string
    {
      if (!isset($this->Keys[$ID]) || (int)$this->Keys[$ID]['user_id'] !== $UserID)
      {
        return 'Unknown passkey.';
      }
      unset($this->Keys[$ID]);
      $this->Save();
      return null;
    }
    public function UserSignIn(int $UserID): ?array
    {
      $Profile = $this->Profile($UserID);
      if ($UserID !== 7 && !isset($this->Passwords[$UserID]) && !isset($this->EmailAccounts[$UserID]))
      {
        return null;
      }
      return ['user_id' => (string)$UserID, 'provider' => $Profile['provider'], 'email' => $Profile['email'], 'first_name' => $Profile['first_name'],
        'last_name' => $Profile['last_name'], 'access' => $this->Grants[$UserID] ?? []];
    }
    public function Menu(): array
    {
      $Item = fn(int $ID, string $Code, string $Name, ?string $Target, bool $Public, string $Shown = 'always', string $Section = 'main', ?int $Parent = null,
        array $Access = ['admin']) => ['id' => (string)$ID, 'parent_id' => $Parent === null ? null : (string)$Parent, 'position' => '0', 'name' => $Name,
        'icon' => 'bi-circle', 'target' => $Target, 'popup' => 'f', 'code' => $Code, 'section' => $Section, 'public' => $Public ? 't' : 'f', 'shown' => $Shown,
        'depth' => $Parent === null ? '0' : '1', 'access' => $Access, 'edit' => ['admin']];
      return [
        1 => $Item(1, 'home', 'Home', '/home.php', true),
        2 => $Item(2, 'orders', 'Orders', '/app/orders.php', false, Access: ['admin', 'public']),
        3 => $Item(3, 'admin', 'Administer', null, false),
        4 => $Item(4, 'users', 'Users', '/app/users.php', false, Parent: 3),
        5 => $Item(5, 'login', 'Sign in', '/framework/login.php', true, 'signed_out'),
        6 => $Item(6, 'logout', 'Sign out', '/framework/logout.php', true, 'signed_in'),
        7 => $Item(7, 'privacy', 'Privacy Policy', '/framework/privacypolicy.php', true, Section: 'status'),
        8 => $Item(8, 'administer2', 'Administer', null, false),
        9 => $Item(9, 'users2', 'Users', '/framework/admin/users.php', false, Parent: 8),
        10 => $Item(10, 'menu2', 'Menu', '/framework/admin/menu.php', false, Parent: 8),
        12 => $Item(12, 'config2', 'Config', '/framework/admin/config.php', false, Parent: 8),
        13 => $Item(13, 'demos', 'Demos', '/framework/demos/index.php', false, Parent: 8),
        11 => $Item(11, 'profile', 'Your profile', '/framework/profile.php', false, 'signed_in', 'status', Access: ['admin', 'public']),
      ];
    }
    public function AddMenuItem(string $Name, ?int $ParentID, string $Code, ?string $Target, string $Icon, string $Section): int|string { return 8; }
    public function SetMenuField(int $ID, string $Field, string|int|bool|null $Value, ?string $Editor): ?string { return null; }
    public function SetMenuRight(int $ID, string $Code, string $Right, bool $Allowed, ?string $Editor): ?string { return null; }
    public function DeleteMenuItem(int $ID, ?string $Editor): ?string { return null; }
  }

  // A site's own settings on Administer → Config (admin.settings)
  class TestSiteSettings implements Admin\SiteSettings
  {
    public static function Groups(): array
    {
      return ['Agent' => ['agent.url' => ['Where the agent answers', false], 'agent.token' => ['Its token', true]],
        'Application' => ['app.helpdesk' => ['The helpdesk address', false]]];
    }

    public static function AfterGroup(string $Group): ?\HTML\Element
    {
      return $Group == 'Agent' ? new \HTML\Paragraph('Registered agents: none') : null;
    }
  }
