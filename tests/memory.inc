<?php

  use Storage\Backend;

  // A storage in memory: the schema's menu, and one user
  class MemoryStorage implements Backend
  {
    public array $Grants = [7 => ['public']];

    public function SignIn(string $Provider, string $Subject, ?string $Email, ?string $FirstName, ?string $LastName): array
    {
      return ['user_id' => '7', 'email' => $Email, 'first_name' => $FirstName, 'last_name' => $LastName, 'access' => $this->Grants[7]];
    }
    public function Access(int $UserID): array { return $this->Grants[$UserID] ?? []; }
    public function Levels(): array { return ['public' => 'Any signed in user', 'admin' => 'System administrator']; }
    public function Users(): array
    {
      return [['user_id' => '7', 'provider' => 'github', 'email' => 'ann@example.com', 'first_name' => 'Ann', 'last_name' => 'Example',
        'created' => '2026-10-28 10:00:00+02', 'last_login' => '2026-10-28 10:00:00+02', 'access' => $this->Grants[7]]];
    }
    public function SetAccess(int $UserID, string $Code, bool $Granted): ?string { return null; }
    public function Profile(int $UserID): ?array
    {
      return ['user_id' => (string)$UserID, 'provider' => 'github', 'email' => 'ann@example.com', 'first_name' => 'Ann', 'last_name' => 'Example',
        'display_as' => 'first', 'nickname' => null, 'names_from_user' => 'f', 'photo' => null, 'wants_provider_photo' => 'f',
        'created' => '2026-10-28 10:00:00+02', 'last_login' => null, 'photo_source' => null];
    }
    public function SetDisplay(int $UserID, string $DisplayAs, ?string $Nickname): ?string { return null; }
    public function SetName(int $UserID, ?string $FirstName, ?string $LastName): ?string { return null; }
    public function SetPhoto(int $UserID, string $Jpeg, string $Source): ?string { return null; }
    public function RemovePhoto(int $UserID): ?string { return null; }
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
        11 => $Item(11, 'profile', 'Your profile', '/framework/profile.php', false, 'signed_in', 'status', Access: ['admin', 'public']),
      ];
    }
    public function AddMenuItem(string $Name, ?int $ParentID, string $Code, ?string $Target, string $Icon, string $Section): int|string { return 8; }
    public function SetMenuField(int $ID, string $Field, string|int|bool|null $Value, ?string $Editor): ?string { return null; }
    public function SetMenuRight(int $ID, string $Code, string $Right, bool $Allowed, ?string $Editor): ?string { return null; }
    public function DeleteMenuItem(int $ID, ?string $Editor): ?string { return null; }
  }

