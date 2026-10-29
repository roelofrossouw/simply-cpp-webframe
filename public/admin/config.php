<?php
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README

  use BS\{AlertSuccess, AlertWarning, Button, Color, Icon, PadSize, ScreenSize, Side};

  // Administer → Config: the settings file (Config::File()), by section. Saving writes it back when the web server may.
  Config::Init();

  // The settings by group, as [path => [what it is, secret]]. Secrets are never shown or sent to the browser: their field
  // stays empty, and saving it empty keeps them.
  const SETTINGS = [
    'Application' => [
      'app.name' => ['Name', false],
      'app.description' => ['Description', false],
      'app.company' => ['Company, in the policies', false],
      'app.country' => ['Country, in the policies', false],
      'app.email' => ['Company email, in the policies', false],
      'app.timezone' => ['Timezone, e.g. Africa/Johannesburg', false],
      'app.home' => ['The page Home opens (default /home.php)', false],
      'app.logo' => ['The menu\'s logo, e.g. /img/logo.svg (default: the name)', false],
      'app.theme_color' => ['The browser\'s colour, e.g. #e9ecef', false],
      'app.framework_url' => ['Where the package\'s pages are served (default /framework)', false],
    ],
    'Database' => [
      'database.connection' => ['A libpq connection string; or the parts below', false],
      'database.host' => ['Host', false],
      'database.port' => ['Port', false],
      'database.dbname' => ['Database', false],
      'database.user' => ['User', false],
      'database.password' => ['Password', true],
      'database.sslmode' => ['SSL mode, e.g. require', false],
    ],
    'Signing in' => [
      'auth.secret' => ['Signs the sign-in tokens: a long random string (changing it signs everyone out)', true],
      'auth.first_admin' => ['While nobody is an admin: true (the first to sign in), an email address, or false', false],
      'auth.keys_dir' => ['Key files, e.g. Apple\'s .p8 (default /etc/sc-webframe/keys)', false],
      'auth.cache_dir' => ['The providers\' public keys (default /var/cache/sc-webframe/jwks)', false],
    ],
    'Email' => [
      'mail.from' => ['From address (default: the app\'s name <noreply@this host>)', false],
      'mail.redirect' => ['Send every email here instead, for testing', false],
      'mail.disabled' => ['Don\'t send email at all (true or false)', false],
    ],
    'Sign in with a password' => [
      'auth.password.enabled' => ['true to let people sign in with their email address and a password (needs mail)', false],
      'auth.password.min_length' => ['The shortest password allowed (default 10)', false],
      'auth.password.session_days' => ['Days a sign-in lasts before the password is asked again (default 30)', false],
      'auth.password.signup' => ['true to let anyone make their own account with an email address they can read (default false)', false],
    ],
    'Sign in with Google' => [
      'auth.google.client_id' => ['Client ID', false],
      'auth.google.client_secret' => ['Client secret', true],
    ],
    'Sign in with Microsoft' => [
      'auth.microsoft.client_id' => ['Client ID', false],
      'auth.microsoft.client_secret' => ['Client secret', true],
      'auth.microsoft.tenant' => ['Tenant (organizations, common or a tenant ID)', false],
    ],
    'Sign in with Apple' => [
      'auth.apple.client_id' => ['Services ID', false],
      'auth.apple.team_id' => ['Team ID', false],
      'auth.apple.key_id' => ['Key ID', false],
      'auth.apple.key_file' => ['Key file (.p8) in the keys folder', false],
    ],
    'Sign in with GitHub' => [
      'auth.github.client_id' => ['Client ID', false],
      'auth.github.client_secret' => ['Client secret', true],
    ],
    'Sign in with Facebook' => [
      'auth.facebook.app_id' => ['App ID', false],
      'auth.facebook.app_secret' => ['App secret', true],
    ],
    'Places of IP addresses' => [
      'ipgeolocation.api_key' => ['An ipgeolocation.io API key: wrong-password emails then say where the try came from', true],
    ],
    'Other' => [
      'admin.settings' => ['A class of the site\'s that adds its own settings to this page (an Admin\SiteSettings)', false],
      'storage.class' => ['Where users, access and the menu are kept (default Storage\Postgres)', false],
      'menu.editor' => ['The page that manages the menu (default: Administer → Menu)', false],
    ],
  ];

  // A setting in the file's data by its path, or null
  function Setting(array $Data, string $Path): mixed
  {
    foreach (explode('.', $Path) as $Key)
    {
      if (!is_array($Data) || !array_key_exists($Key, $Data))
      {
        return null;
      }
      $Data = $Data[$Key];
    }
    return $Data;
  }

  // Set a setting by its path, or take it out (null), dropping sections it leaves empty
  function SetSetting(array &$Data, string $Path, mixed $Value): void
  {
    $Keys = explode('.', $Path);
    $Key = array_shift($Keys);
    if (empty($Keys))
    {
      if ($Value === null)
      {
        unset($Data[$Key]);
      }
      else
      {
        $Data[$Key] = $Value;
      }
      return;
    }
    $Section = is_array($Data[$Key] ?? null) ? $Data[$Key] : [];
    SetSetting($Section, implode('.', $Keys), $Value);
    if (empty($Section))
    {
      unset($Data[$Key]);
    }
    else
    {
      $Data[$Key] = $Section;
    }
  }

  // Every path a file sets, for the ones this page doesn't list
  function Paths(array $Data, string $Prefix = ''): array
  {
    $Paths = [];
    foreach ($Data as $Key => $Value)
    {
      $Path = $Prefix . $Key;
      $Paths = array_merge($Paths, is_array($Value) && !array_is_list($Value) && !empty($Value) ? Paths($Value, "$Path.") : [$Path]);
    }
    return $Paths;
  }

  $File = Config::File();
  $Here = Config::FrameworkURL('admin/config.php');
  $Writable = is_writable($File);
  // The menu's Edit right on this page (Administer → Menu), viewers only see the settings
  $CanEdit = \Auth\Base::CanEdit();
  $Data = json_decode((string)file_get_contents($File), true) ?? [];
  // The site's own settings (admin.settings, an Admin\SiteSettings), after the package's; a group of the same name gets
  // them added
  $Site = (string)Config::Get('admin.settings', '');
  $Site = $Site !== '' && class_exists($Site) && is_subclass_of($Site, Admin\SiteSettings::class) ? $Site : null;
  $Settings = SETTINGS;
  foreach ($Site === null ? [] : $Site::Groups() as $Group => $Paths)
  {
    $Settings[$Group] = ($Settings[$Group] ?? []) + $Paths;
  }
  $Listed = array_merge(...array_values($Settings));
  // Settings the file has that aren't listed above, shown as they are
  $Unlisted = array_values(array_filter(Paths($Data), fn($Path) => !array_key_exists($Path, $Listed)));

  $Notice = null;
  if (QueryString::Get('action') == 'save')
  {
    if (!$CanEdit)
    {
      $Notice = new AlertWarning('Not saved', 'You may look at the settings, but not change them.');
    }
    elseif (!$Writable)
    {
      $Notice = new AlertWarning('Not saved', "The web server can't write $File.");
    }
    else
    {
      $Posted = (array)Post::Get('setting');
      foreach ($Listed as $Path => [, $Secret])
      {
        // The form's names use : for the dots, which PHP would turn into underscores
        $Value = $Posted[str_replace('.', ':', $Path)] ?? null;
        if ($Value === null || ($Secret && $Value === ''))
        {
          continue; // Not on the form, or a secret left as it is
        }
        $Value = trim((string)$Value);
        SetSetting($Data, $Path, $Value === '' ? null : (in_array($Value, ['true', 'false'], true) ? $Value === 'true' : $Value));
      }
      $Saved = file_put_contents($File, json_encode($Data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n", LOCK_EX);
      $Notice = $Saved ? new AlertSuccess('Saved', 'The settings apply from the next page that loads.') : new AlertWarning('Not saved', "Writing $File failed.");
    }
  }

  Page::AddContent($Result = new HTML\Form());
  $Result->Format->Padding(Side::All, PadSize::Pad3);
  $Result->Format->MaxWidth(ScreenSize::Large->Width());
  $Result->Format->Center();
  if ($Notice !== null)
  {
    $Result->AddChild($Notice);
  }

  // The file itself
  $Stat = stat($File);
  $Owner = function_exists('posix_getpwuid')
    ? (posix_getpwuid($Stat['uid'])['name'] ?? $Stat['uid']) . ':' . (posix_getgrgid($Stat['gid'])['name'] ?? $Stat['gid'])
    : $Stat['uid'] . ':' . $Stat['gid'];
  $Result->AddChild($About = new HTML\Paragraph(new HTML\Code($File)));
  $About->AddChild(sprintf(' (%s, mode %o, changed %s). ', $Owner, $Stat['mode'] & 0777, date('j F Y H:i', $Stat['mtime'])));
  $About->AddChild($Writable ? 'Empty a field to take a setting out; a secret\'s empty field keeps it.'
    : 'Read only: the web server can\'t write the file. To change settings here, let its group write it (chmod g+w), or edit it on the server.');
  $About->Format->Color(Color::BodySecondary);
  $About->AddClass('small');

  $Groups = $Settings + (empty($Unlisted) ? [] : ['Not listed here' => array_fill_keys($Unlisted, ['', false])]);
  foreach ($Groups as $Group => $GroupSettings)
  {
    $Result->AddChild($Heading = new HTML\Header(5, $Group));
    $Heading->Format->Margin(Side::Top, PadSize::Pad4);
    $Result->AddChild($Wrapper = new \BS\ResponsiveTable(ScreenSize::Medium));
    $Table = $Wrapper->Table();
    $Table->Small();
    foreach ($GroupSettings as $Path => [$Purpose, $Secret])
    {
      $Value = Setting($Data, $Path);
      $Row = $Table->Body()->AddRow();
      $Name = $Row->AddCell(new HTML\Code($Path));
      $Name->AddStyle('width', '30%');
      $Cell = $Row->AddCell(null);
      if (!array_key_exists($Path, $Listed))
      {
        // Unlisted settings aren't edited here: shown as they are, unless the name says it's a secret
        $Cell->AddChild(preg_match('/secret|password|token|key$/i', $Path) ? '(set, hidden)' : json_encode($Value, JSON_UNESCAPED_SLASHES));
        continue;
      }
      $Cell->AddChild($Input = new HTML\Input('setting[' . str_replace('.', ':', $Path) . ']', $Secret ? HTML\InputType::Password : HTML\InputType::Text));
      $Input->AddClasses(['form-control', 'form-control-sm', 'font-monospace']);
      $Input->AddAttribute('aria-label', $Path);
      if ($Secret)
      {
        $Input->Placeholder = $Value === null ? 'not set' : 'set (' . strlen((string)$Value) . ' characters), leave empty to keep';
        $Input->AddAttribute('autocomplete', 'off');
      }
      else
      {
        $Input->Value(is_bool($Value) ? ($Value ? 'true' : 'false') : (is_scalar($Value) ? (string)$Value : ''));
        $Input->Placeholder = 'not set';
      }
      if (!$Writable || !$CanEdit)
      {
        $Input->ReadOnly = true;
      }
      $Cell->AddChild($Help = new HTML\Div());
      $Help->AddChild($Purpose);
      $Help->AddClass('small');
      $Help->Format->Color(Color::BodySecondary);
    }
    // The site's own content under its group, e.g. what the settings set up
    if ($Site !== null && ($After = $Site::AfterGroup($Group)) !== null)
    {
      $Result->AddChild($After);
    }
  }

  if ($Writable && $CanEdit)
  {
    $Result->AddChild($Save = new Button('Save', Icon::Check2, "$Here?action=save"));
    $Save->Format->Margin(Side::Top, PadSize::Pad3);
  }
