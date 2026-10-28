<?php

  // The tests' helpers: the source tree's classes on the include_path (as installed in /usr/share/php), and Check()
  set_include_path(__DIR__ . '/include' . PATH_SEPARATOR . get_include_path());
  define('WEBFRAME_CONFIG', __DIR__ . '/config.json');
  require_once 'sc-webframe/autoload.php';

  $Checks = 0;
  $Failures = 0;

  function Check(bool $Passed, string $What): void
  {
    global $Checks, $Failures;
    $Checks++;
    if (!$Passed)
    {
      $Failures++;
      echo "FAILED: $What\n";
    }
  }

  function Same(mixed $Expected, mixed $Actual, string $What): void
  {
    Check($Expected === $Actual, $What . ($Expected === $Actual ? '' : ': expected ' . var_export($Expected, true) . ', got ' . var_export($Actual, true)));
  }

  register_shutdown_function(function () {
    global $Checks, $Failures;
    echo "$Checks checks, $Failures failed\n";
    if ($Failures > 0)
    {
      exit(1);
    }
  });
