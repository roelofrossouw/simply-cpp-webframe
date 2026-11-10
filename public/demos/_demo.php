<?php
  // The demos' shared start (it shows nothing itself): the package's classes, and Demo('page.php'), a demo page's URL (under the package's
  // public folder, wherever the site serves it: app.framework_url)
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README

  function Demo(string $Page): string
  {
    return Config::FrameworkURL('demos/' . $Page);
  }

  // The access every demo goes by: the Demos menu item's (Administer → Demos, admins only)
  const DEMOS = 'demos/index.php';
