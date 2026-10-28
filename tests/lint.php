<?php

  // Every PHP file in the package parses, with no deprecation either: php -l on each, warnings on
  $Root = $argv[1] ?? dirname(__DIR__);
  $Failed = 0;
  $Count = 0;
  foreach (['php', 'public', 'examples', 'tests'] as $Folder)
  {
    $Files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$Root/$Folder", FilesystemIterator::SKIP_DOTS));
    foreach ($Files as $File)
    {
      if ($File->getExtension() !== 'php')
      {
        continue;
      }
      $Count++;
      exec(escapeshellarg(PHP_BINARY) . ' -d error_reporting=-1 -d display_errors=1 -l ' . escapeshellarg($File->getPathname()) . ' 2>&1', $Output, $Status);
      $Text = implode("\n", $Output);
      $Output = [];
      if ($Status !== 0 || !str_starts_with($Text, 'No syntax errors'))
      {
        echo $Text, "\n";
        $Failed++;
      }
    }
  }
  echo "$Count files, $Failed with problems\n";
  exit($Failed === 0 ? 0 : 1);
