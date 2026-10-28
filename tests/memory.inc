<?php

  use Storage\Backend;

  // A storage in memory: the schema's menu, and one user
  class MemoryStorage implements Backend
  {
    public array $Grants = [7 => ['public']];

    public function SignIn(string $Provider, string $Subject, ?string $Email, ?string $FirstName, ?string $LastName): array
    {
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
        'created' => '2026-10-28 10:00:00+02', 'last_login' => '2026-10-28 10:00:00+02', 'access' => $this->Grants[7]]]
        + array_map(fn($ID) => ['user_id' => (string)$ID, 'provider' => 'password', 'username' => $this->Passwords[$ID]['subject'],
          'email' => $this->Passwords[$ID]['email'], 'first_name' => 'Bob', 'last_name' => null, 'created' => '2026-10-28 10:00:00+02',
          'last_login' => null, 'access' => $this->Grants[$ID]], array_combine(array_keys($this->Passwords), array_keys($this->Passwords)));
    }
    public function SetAccess(int $UserID, string $Code, bool $Granted): ?string { return null; }
    public function Profile(int $UserID): ?array
    {
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
    // Password users by id: username, email, hash, failures, last_failure; and wrong passwords by address (times)
    public array $Passwords = [];
    public array $AddressFailures = [];
    public function PasswordUserID(string $Name): ?int
    {
      $Name = mb_strtolower(trim($Name));
      foreach ($this->Passwords as $ID => $User)
      {
        if ($User['subject'] === $Name || mb_strtolower((string)$User['email']) === $Name)
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
    public function AddPasswordUser(string $Username, ?string $Email, ?string $FirstName, ?string $LastName, string $Hash): int|string
    {
      if ($this->PasswordUserID($Username) !== null)
      {
        return "Someone already has the username $Username.";
      }
      $ID = 100 + count($this->Passwords);
      $this->Passwords[$ID] = ['subject' => mb_strtolower(trim($Username)), 'email' => $Email ?: null, 'hash' => $Hash, 'failures' => '0', 'last_failure' => null];
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
    public function SetEmail(int $UserID, ?string $Email): ?string
    {
      if (!isset($this->Passwords[$UserID]))
      {
        return 'The sign-in provider gives this user\'s email address.';
      }
      $this->Passwords[$UserID]['email'] = $Email;
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
    }
    public function AddressFailures(string $Address, int $Seconds): int
    {
      return count(array_filter($this->AddressFailures[$Address] ?? [], fn($At) => $At > time() - $Seconds));
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
        11 => $Item(11, 'profile', 'Your profile', '/framework/profile.php', false, 'signed_in', 'status', Access: ['admin', 'public']),
      ];
    }
    public function AddMenuItem(string $Name, ?int $ParentID, string $Code, ?string $Target, string $Icon, string $Section): int|string { return 8; }
    public function SetMenuField(int $ID, string $Field, string|int|bool|null $Value, ?string $Editor): ?string { return null; }
    public function SetMenuRight(int $ID, string $Code, string $Right, bool $Allowed, ?string $Editor): ?string { return null; }
    public function DeleteMenuItem(int $ID, ?string $Editor): ?string { return null; }
  }

