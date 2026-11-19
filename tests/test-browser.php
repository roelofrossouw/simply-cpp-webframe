<?php

  // Chart/standalone.js in a real browser (headless Chrome or Chromium, where there is one; skipped otherwise): a series
  // hidden in the legend drops out of a stack (lines, percent, bars) and comes back as drawn, and a table legend sorts
  // and highlights. The page and its checks are tests/browser/charts.php and charts.js.
  require __DIR__ . '/check.inc';

  $Browser = null;
  foreach (['/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', '/Applications/Chromium.app/Contents/MacOS/Chromium'] as $Path)
  {
    $Browser ??= is_executable($Path) ? $Path : null;
  }
  foreach (['google-chrome', 'chromium', 'chromium-browser'] as $Name)
  {
    $Browser ??= trim((string)shell_exec('command -v ' . $Name . ' 2>/dev/null')) ?: null;
  }
  if ($Browser === null)
  {
    echo "No Chrome or Chromium here: skipped\n";
    Check(true, 'skipped');
    exit;
  }

  $Folder = sys_get_temp_dir() . '/sc-webframe-browser-' . getmypid();
  mkdir($Folder);
  register_shutdown_function(fn() => exec('rm -rf ' . escapeshellarg($Folder)));
  ob_start();
  require __DIR__ . '/browser/charts.php';
  file_put_contents("$Folder/page.html", ob_get_clean());
  $Dom = (string)shell_exec(escapeshellarg($Browser) . ' --headless=new --disable-gpu --no-first-run --no-default-browser-check --user-data-dir=' .
    escapeshellarg("$Folder/profile") . ' --virtual-time-budget=3000 --dump-dom ' . escapeshellarg("file://$Folder/page.html") . ' 2>/dev/null');
  if (!preg_match('/<pre id="out">(.*?)<\/pre>/s', $Dom, $Found) || trim($Found[1]) === '')
  {
    Check(false, 'the browser ran the checks');
    exit;
  }
  foreach (explode("\n", html_entity_decode(trim($Found[1]))) as $Line)
  {
    Check(str_starts_with($Line, 'ok '), trim(preg_replace('/^(ok|FAIL|ERROR)\s+/', '', $Line)));
  }
