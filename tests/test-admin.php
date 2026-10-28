<?php

  // The package's admin pages and profile, as an admin who is signed in sees them (loaded by js/app.js): each page in a
  // process of its own, as PHP runs it
  require __DIR__ . '/check.inc';
  require __DIR__ . '/memory.inc';

  $Page = $argv[1] ?? null;
  if ($Page !== null)
  {
    // One page: signed in as user 7, an admin
    $Checks = -1; // This process only prints the page
    $_SERVER['HTTP_HOST'] = 'www.example.com';
    $_SERVER['HTTP_X_REQUESTED_BY'] = 'sc-webframe';
    $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = Config::FrameworkURL($Page);
    parse_str($argv[2] ?? '', $_POST);
    Config::Load();
    Storage\Store::Use($Storage = new MemoryStorage());
    $Storage->Grants[7] = ['public', 'admin'];
    $_COOKIE['auth_token'] = (string)new Auth\JWT(['userid' => '7', 'access' => ['public', 'admin'], 'provider' => 'github'], 600);
    $_COOKIE['auth_provider'] = 'github';
    include Config::FrameworkFolder() . '/' . $Page;
    return;
  }

  function Render(string $Page, array $Post = []): string
  {
    return (string)shell_exec(escapeshellarg(PHP_BINARY) . ' -d error_reporting=-1 -d display_errors=1 ' . escapeshellarg(__FILE__) . ' '
      . escapeshellarg($Page) . ' ' . escapeshellarg(http_build_query($Post)) . ' 2>&1');
  }

  $Users = Render('admin/users.php');
  Check(str_contains($Users, 'ann@example.com') && str_contains($Users, 'form-switch'), 'Users lists the users with their switches');
  Check(str_contains($Users, '/framework/admin/usergrant.php'), 'and saves through usergrant.php');
  Check(!preg_match('/Warning|Deprecated|Fatal|Something Went Wrong/', $Users), 'Users without errors' . (preg_match('/(Warning|Deprecated|Fatal)[^\n]*/', $Users, $M) ? ': ' . $M[0] : ''));

  $Menu = Render('admin/menu.php');
  Check(str_contains($Menu, 'Main menu') && str_contains($Menu, '/framework/admin/menuupdate.php'), 'Menu shows the sections, saving through menuupdate.php');
  Check(!preg_match('/Warning|Deprecated|Fatal|Something Went Wrong/', $Menu), 'Menu without errors' . (preg_match('/(Warning|Deprecated|Fatal)[^\n]*/', $Menu, $M) ? ': ' . $M[0] : ''));

  $Profile = Render('profile.php');
  Check(str_contains($Profile, 'Your profile') && str_contains($Profile, 'Ann'), 'the profile');
  Check(!preg_match('/Warning|Deprecated|Fatal|Something Went Wrong/', $Profile), 'the profile without errors' . (preg_match('/(Warning|Deprecated|Fatal)[^\n]*/', $Profile, $M) ? ': ' . $M[0] : ''));

  $Own = Render('admin/usergrant.php', ['userid' => '7', 'code' => 'admin', 'val' => 'false']);
  Check(str_contains($Own, 'You can\'t remove your own admin access.'), 'an admin can\'t revoke their own admin');
  $Block = Render('admin/usergrant.php', ['userid' => '7', 'code' => 'public', 'val' => 'false']);
  Check(str_contains($Block, 'You can\'t block yourself.'), 'or block themselves');
