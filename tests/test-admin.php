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
    if (!empty($_POST['site_settings']))
    {
      Config::Set('admin.settings', 'TestSiteSettings');
      $_POST = [];
    }
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

  $Config = Render('admin/config.php');
  Check(preg_match('/class="sticky-bottom[^"]*"[^>]*>.*?Save/s', $Config) === 1, 'Config\'s Save stays in sight at the foot');
  Check(preg_match('#<details[^>]*open>\s*<summary[^>]*>Application#', $Config) === 1, 'Config\'s groups are sections, open when something is set');
  Check(preg_match('#<details class="mt-3">\s*<summary[^>]*>Sign in with Google#', $Config) === 1, 'and folded when nothing is');
  Check(str_contains($Config, 'app.name') && str_contains($Config, 'Test site') && str_contains($Config, 'auth.github.client_id'), 'Config shows the settings');
  Check(!str_contains($Config, 'test secret, not for use anywhere') && !str_contains($Config, 'test-secret') && !str_contains($Config, 'secret\\'),
    'but never a secret');
  Check(!preg_match('/Warning|Deprecated|Fatal|Something Went Wrong/', $Config), 'Config without errors' . (preg_match('/(Warning|Deprecated|Fatal)[^\n]*/', $Config, $M) ? ': ' . $M[0] : ''));

  // A site's own settings (admin.settings): its groups after the package's, editable, secrets hidden, its content after
  $Site = Render('admin/config.php', ['site_settings' => '1']);
  Check(str_contains($Site, '>Agent<') && str_contains($Site, 'setting[agent:url]'), 'a site\'s own group, editable');
  Check(preg_match('/name="setting\[agent:token\]" type="password"/', $Site) === 1, 'its secret as a secret');
  Check(str_contains($Site, 'setting[app:helpdesk]') && strpos($Site, 'app.helpdesk') > strpos($Site, 'app.name'), 'a path added to the package\'s group');
  Check(str_contains($Site, 'Registered agents: none'), 'and its content under its group');
  Check(($At = strpos($Site, 'Not listed here')) === false || !str_contains(substr($Site, $At), 'agent.url'), 'not under Not listed here');
  Check(!preg_match('/Warning|Deprecated|Fatal|Something Went Wrong/', $Site), 'Config with a site\'s settings without errors' . (preg_match('/(Warning|Deprecated|Fatal)[^\n]*/', $Site, $M) ? ': ' . $M[0] : ''));

  $Profile = Render('profile.php');
  Check(str_contains($Profile, 'Your profile') && str_contains($Profile, 'Ann'), 'the profile');
  Check(!preg_match('/Warning|Deprecated|Fatal|Something Went Wrong/', $Profile), 'the profile without errors' . (preg_match('/(Warning|Deprecated|Fatal)[^\n]*/', $Profile, $M) ? ': ' . $M[0] : ''));

  $Own = Render('admin/usergrant.php', ['userid' => '7', 'code' => 'admin', 'val' => 'false']);
  Check(str_contains($Own, 'You can\'t remove your own admin access.'), 'an admin can\'t revoke their own admin');
  $Block = Render('admin/usergrant.php', ['userid' => '7', 'code' => 'public', 'val' => 'false']);
  Check(str_contains($Block, 'You can\'t block yourself.'), 'or block themselves');
