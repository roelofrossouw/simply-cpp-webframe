<?php

  /**
   * The site's settings, and the start of every page
   *
   * The settings are a JSON file with a section per part (see examples/hello/config.json):
   *
   *   app       name, description, company, country, email, timezone, home (the page Home opens), logo,
   *             framework_url (where the package's pages are served, default /framework)
   *   database  connection (a libpq connection string), or host, port, dbname, user, password, sslmode
   *   auth      secret (signs the sign-in tokens), keys_dir (key files, e.g. Apple's .p8), and a section per
   *             sign-in provider: google, microsoft, apple, github, facebook. A provider is on when its section is there.
   *   mail      from, redirect, disabled (see BS\Email)
   *   storage   class: where users, access and the menu are kept (default Storage\Postgres, see Storage\Backend)
   *   menu      editor: the page that manages the menu, which admins must always keep (optional)
   *
   * Where the file is, the first that is set: the WEBFRAME_CONFIG constant (define it before including autoload.php), the
   * WEBFRAME_CONFIG environment variable (Apache: SetEnv, which PHP-FPM gets too), the webframe.config php.ini setting, or
   * /etc/sc-webframe/config.json.
   */
  class Config
  {
    const string DEFAULT_FILE = '/etc/sc-webframe/config.json';
    static array $Data = [];
    private static bool $Loaded = false;
    private static bool $UserPage = false;
    private static bool $AppPage = false;

    public static function Anonymous(): bool
    {
      return !self::$UserPage;
    }

    public static function AppPage(): bool
    {
      return self::$AppPage;
    }

    // For Init(Access:): any signed in user may use the page, whatever the menu says
    const string ANY_USER = '*';

    /**
     * Start a page: the config, the sign-in, and whether the user may use the page
     *
     * Pages for signed in users are checked against the menu (framework.menu_access, see MenuLayout::Roles()), closed by
     * default: a page that isn't in the menu is refused unless it names the menu page it belongs to. A public menu item's
     * page is open to everyone.
     *
     *   Config::Init();                                                     // A page in the menu
     *   Config::Init(Access: '/app/orders.php', Shown: Dismiss::Modal)      // A popup opened from that page
     *   Config::Init(Access: Config::ANY_USER);                             // E.g. your profile
     *   Config::Init(Access: '/app/users.php', Edit: true, Shown: Dismiss::Toast)  // Saves changes for that page
     *
     * @param bool        $Anonymous Anyone may open the page, also without signing in
     * @param bool        $Landing   The page is opened directly (the site's index.php), not loaded by js/app.js
     * @param string|null $Access    The menu page whose roles apply (default: this page), or ANY_USER
     * @param bool        $Current   Check the grants in the database rather than the sign-in token (up to 30 minutes old),
     *                               for pages that change access, so access that was just revoked stops at once
     * @param \BS\Dismiss $Shown     How the page is shown, so a refusal looks the same: in the page, a popup, a side panel
     *                               or a toast
     * @param bool        $Edit      The page changes things, so it needs the menu's Edit right rather than View (always
     *                               checked against the database)
     */
    public static function Init(bool $Anonymous = false, bool $Landing = false, ?string $Access = null, bool $Current = false,
      \BS\Dismiss $Shown = \BS\Dismiss::None, bool $Edit = false): void
    {
      if (session_status() !== PHP_SESSION_ACTIVE)
      {
        session_start();
      }
      self::Load();
      \Auth\Base::Factory(\Auth\Base::ForRequest());
      self::$UserPage = !$Anonymous;
      self::$AppPage = !$Landing;

      if (self::$UserPage && !\Auth\Base::LoggedIn() && !MenuLayout::IsPublic($Access ?? Server::Page()))
      {
        http_response_code(401);
        echo 'Please sign in to open this page.<br>';
        exit;
      }

      if (self::$AppPage && !Server::IsAJAX())
      {
        echo 'This page is part of the application and can\'t be opened directly.<br>';
        exit;
      }

      if (self::$UserPage && $Access !== self::ANY_USER
        && !($Edit ? \Auth\Base::CanEdit($Access) : \Auth\Base::Allowed($Access, $Current)))
      {
        self::Refuse($Shown, $Edit);
      }
    }

    /**
     * Tell the user they may not use the page, the way the page would have been shown, and stop
     */
    private static function Refuse(\BS\Dismiss $Shown, bool $Edit = false): never
    {
      $Message = $Edit ? 'You may look, but not change things here.' : 'You don\'t have access to this page.';
      switch ($Shown)
      {
        case \BS\Dismiss::Modal:
          Page::AddContent($Dialog = new \BS\ModalDialog('No access', \BS\ScreenSize::Medium));
          $Dialog->AddChild(new \BS\AlertWarning('No access', $Message));
          break;
        case \BS\Dismiss::OffCanvas:
          Page::AddContent($Panel = new \BS\OffCanvas('No access'));
          $Panel->AddChild(new \BS\AlertWarning('No access', $Message));
          break;
        case \BS\Dismiss::Toast:
          Page::AddContent($Toast = new \BS\Toast('No access', $Message));
          $Toast->Color(\BS\Color::Danger);
          break;
        default:
          Page::AddContent(new \BS\AlertWarning('No access', $Message));
      }
      exit; // The shutdown function shows the page, see autoload.php
    }

    /**
     * The settings file in use, see the class comment
     */
    public static function File(): string
    {
      if (defined('WEBFRAME_CONFIG'))
      {
        return constant('WEBFRAME_CONFIG');
      }
      $File = $_SERVER['WEBFRAME_CONFIG'] ?? getenv('WEBFRAME_CONFIG');
      if (empty($File))
      {
        $File = get_cfg_var('webframe.config');
      }
      return empty($File) ? self::DEFAULT_FILE : (string)$File;
    }

    /**
     * Read the settings, once a request; Init() does it, scripts that don't start a page call it themselves
     */
    public static function Load(): void
    {
      if (self::$Loaded)
      {
        return;
      }
      $File = self::File();
      $Text = is_readable($File) ? file_get_contents($File) : false;
      $Data = $Text === false ? null : json_decode($Text, true);
      if (!is_array($Data))
      {
        throw new RuntimeException("The settings file $File is missing, unreadable or not valid JSON (see sc-webframe's README)");
      }
      self::$Data = $Data;
      self::$Loaded = true;
      if (!empty($Zone = self::Get('app.timezone')))
      {
        date_default_timezone_set($Zone);
      }
    }

    /**
     * A setting by its path, e.g. Get('app.name') or Get('auth.google'), or $Default when it isn't set
     */
    public static function Get(string $Path, mixed $Default = null): mixed
    {
      $Value = self::$Data;
      foreach (explode('.', $Path) as $Key)
      {
        if (!is_array($Value) || !array_key_exists($Key, $Value))
        {
          return $Default;
        }
        $Value = $Value[$Key];
      }
      return $Value;
    }

    /**
     * Whether a section, e.g. 'auth.github', is in the settings
     */
    public static function Has(string $Path): bool
    {
      return self::Get($Path) !== null;
    }

    /**
     * Change a setting for this request only (the file isn't written), e.g. in tests
     */
    public static function Set(string $Path, mixed $Value): void
    {
      $Keys = explode('.', $Path);
      $Node = &self::$Data;
      foreach ($Keys as $Key)
      {
        if (!isset($Node[$Key]) || !is_array($Node[$Key]))
        {
          $Node[$Key] = [];
        }
        $Node = &$Node[$Key];
      }
      $Node = $Value;
      self::$Loaded = true;
    }

    public static function LoadPage(?string $Page = null, bool $CanRedirect = true, int $Delay = 0): void
    {
      $Page = $Page ?? self::URI();
      if ($CanRedirect && !headers_sent() && empty($Delay))
      {
        header('Location: ' . $Page);
      }
      else
      {
        $Script = 'window.location = ' . json_encode($Page) . ';';
        if ($Delay > 0)
        {
          $Script = "window.setTimeout(function() { $Script }, {$Delay});";
        }
        echo new HTML\Script($Script);
      }
    }

    public static function URI(): string
    {
      return self::Origin() . self::Page();
    }

    public static function Origin(): string
    {
      return 'https://' . self::Host();
    }

    public static function Host(): string
    {
      return $_SERVER['HTTP_HOST'] ?? 'localhost';
    }

    public static function Page(): string
    {
      return $_SERVER['PHP_SELF'] ?? '';
    }

    /**
     * Where the package's own pages, scripts and styles are served (the Apache Alias to /usr/share/sc-webframe/public),
     * without a trailing slash: app.framework_url, default /framework
     */
    public static function FrameworkURL(string $Page = ''): string
    {
      return rtrim((string)self::Get('app.framework_url', '/framework'), '/') . ($Page === '' ? '' : '/' . ltrim($Page, '/'));
    }

    /**
     * The folder the package's public files are in, for reading them (e.g. to add a version to their links)
     */
    public static function FrameworkFolder(): string
    {
      // Installed: /usr/share/php/sc-webframe beside /usr/share/sc-webframe/public; in the source tree: php/ beside public/
      $Source = dirname(__DIR__) . '/public';
      return is_dir($Source) ? $Source : dirname(__DIR__, 2) . '/sc-webframe/public';
    }

    public static function MailFooter(): \HTML\Element
    {
      $Paragraph = new \HTML\Paragraph('For more information see our');
      $Paragraph->AddAnchor('web page', Config::Origin(), true);
      $Paragraph->AddChild(('. (You can view our '));
      $Paragraph->AddAnchor('Privacy Policy', Config::Origin() . '?load=privacy', true);
      $Paragraph->AddChild((' and our '));
      $Paragraph->AddAnchor('Terms of Use', Config::Origin() . '?load=terms', true);
      $Paragraph->AddChild((' there)'));
      $Paragraph->AddClass('text-sm');
      return $Paragraph;
    }

    public static function AppName(): string
    {
      return (string)self::Get('app.name', 'Webframe');
    }

    public static function AppDescription(): string
    {
      return (string)self::Get('app.description', '');
    }

    public static function CompanyName(): string
    {
      return (string)self::Get('app.company', self::AppName());
    }

    public static function CompanyCountry(): string
    {
      return (string)self::Get('app.country', '');
    }

    public static function CompanyEmail(): string
    {
      return (string)self::Get('app.email', '');
    }

    /**
     * The page Home opens, loaded into the site by js/app.js: app.home, default /home.php
     */
    public static function Home(): string
    {
      return (string)self::Get('app.home', '/home.php');
    }
  }
