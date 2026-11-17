<?php

  /**
   * sc-webframe's charts for another app: loads only the Chart\, HTML\ and BS\ classes, nothing with a global name
   * (no Config, Page, DB or User), so it sits beside an app's own classes of those names. Installed in
   * /usr/share/php/sc-webframe, on Debian's include_path:
   *
   *   require_once 'sc-webframe/charts.php';
   *   echo (new Chart\BarChart('Claims'))->FromGraphRows($Rows, ['Opened', 'Closed']);
   *
   * Outside sc-webframe's app the charts' tooltips are the browser's own and their links plain SVG links; Chart's
   * ItemAttributes() gives the items what the app's own click handling reads.
   */

  if (PHP_VERSION_ID < 80300)
  {
    throw new RuntimeException('sc-webframe needs PHP 8.3 or later, this is ' . PHP_VERSION);
  }

  spl_autoload_register(function (string $Class): void {
    if (preg_match('/^(Chart|HTML|BS)\\\\[A-Za-z0-9_\\\\]+$/', $Class))
    {
      $File = __DIR__ . '/' . str_replace('\\', '/', $Class) . '.php';
      if (is_file($File))
      {
        require $File;
      }
    }
  });
