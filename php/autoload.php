<?php

  /**
   * sc-webframe's start: the class autoloader and the error handler, run before every request
   *
   * Installed in /usr/share/php/sc-webframe, which is on Debian's include_path, so a site starts each page with
   *
   *   require_once 'sc-webframe/autoload.php';
   *
   * or has PHP do it for every page (php.ini or the pool: auto_prepend_file = sc-webframe/autoload.php). A site's own
   * classes load too: from the folders in WEBFRAME_CLASSES (a constant defined before this file, or an environment
   * variable; several separated by the path separator, ':'), else from the class/ folder beside the document root's
   * pages. Keep this free of output and sessions: pages call Config::Init() themselves.
   */

  if (PHP_VERSION_ID < 80300)
  {
    throw new RuntimeException('sc-webframe needs PHP 8.3 or later, this is ' . PHP_VERSION);
  }

  (function (): void {
    $Folders = [__DIR__];
    $Own = defined('WEBFRAME_CLASSES') ? constant('WEBFRAME_CLASSES') : ($_SERVER['WEBFRAME_CLASSES'] ?? getenv('WEBFRAME_CLASSES'));
    if (!empty($Own))
    {
      array_push($Folders, ...explode(PATH_SEPARATOR, (string)$Own));
    }
    elseif (!empty($_SERVER['DOCUMENT_ROOT']) && is_dir($_SERVER['DOCUMENT_ROOT'] . '/class'))
    {
      $Folders[] = $_SERVER['DOCUMENT_ROOT'] . '/class';
    }

    spl_autoload_register(function (string $Class) use ($Folders): void {
      $Name = str_replace('\\', '/', $Class) . '.php';
      foreach ($Folders as $Folder)
      {
        $File = rtrim($Folder, '/') . '/' . $Name;
        if (is_file($File))
        {
          require $File;
          if ($Class == 'Page')
          {
            register_shutdown_function(function () {
              Page::Show();
            });
          }
          return;
        }
      }
      // Unknown class: let class_exists() return false, or another autoloader try
    });
  })();

  if (PHP_SAPI !== 'cli')
  {
    new ErrorHandler();
  }

  // Times in UTC unless php.ini sets date.timezone, or the settings' app.timezone does (see Config::Load())
  if (ini_get('date.timezone') === '' || ini_get('date.timezone') === false)
  {
    date_default_timezone_set('UTC');
  }
