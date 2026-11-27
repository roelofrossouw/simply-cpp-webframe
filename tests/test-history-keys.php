<?php

  // A history glyph's token signed on one request and checked on a fresh one, with the key in a file (auth.keys_dir,
  // no auth.secret), as history.php checks it before its Config::Init(). 1.49.1 read the default key folder there.
  $Child = $argv[1] ?? null;
  if ($Child !== null)
  {
    // One request: the settings file named, nothing loaded yet
    define('WEBFRAME_CONFIG', $argv[2]);
    set_include_path(__DIR__ . '/include' . PATH_SEPARATOR . get_include_path());
    require_once 'sc-webframe/autoload.php';
    $_SERVER['HTTP_HOST'] = 'www.example.com';
    if ($Child === 'sign')
    {
      Config::Load();
      echo History::Token('/framework/admin/menu.php', ['framework.menu'], ['id' => 29], 'Sign in');
    }
    else
    {
      $Scope = History::Scope($argv[3]);
      echo $Scope === null ? 'refused' : 'ok ' . json_encode($Scope['match']);
    }
    return;
  }

  require __DIR__ . '/check.inc';
  $Folder = sys_get_temp_dir() . '/sc-webframe-keys-' . getmypid();
  mkdir($Folder . '/keys', 0700, true);
  file_put_contents($Folder . '/keys/jwt.key', bin2hex(random_bytes(32)));
  $Settings = json_decode(file_get_contents(__DIR__ . '/config.json'), true);
  unset($Settings['auth']['secret']);
  $Settings['auth']['keys_dir'] = $Folder . '/keys';
  file_put_contents($Folder . '/config.json', json_encode($Settings));
  $Run = fn(string ...$Arguments) => trim((string)shell_exec(escapeshellarg(PHP_BINARY) . ' -d display_errors=1 ' . escapeshellarg(__FILE__) . ' '
    . implode(' ', array_map('escapeshellarg', $Arguments)) . ' 2>&1'));
  $Token = $Run('sign', $Folder . '/config.json');
  Check(substr_count($Token, '.') === 2, 'a token signed with the key file' . (substr_count($Token, '.') === 2 ? '' : ": $Token"));
  $Checked = $Run('check', $Folder . '/config.json', $Token);
  Same('ok {"framework.menu":{"id":29}}', $Checked, 'checked on a fresh request before Config::Init(), with the same key file');
  file_put_contents($Folder . '/keys/jwt.key', bin2hex(random_bytes(32)));
  Same('refused', $Run('check', $Folder . '/config.json', $Token), 'refused with another key');
  array_map('unlink', [$Folder . '/keys/jwt.key', $Folder . '/config.json']);
  rmdir($Folder . '/keys');
  rmdir($Folder);
