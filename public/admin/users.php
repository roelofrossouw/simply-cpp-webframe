<?php
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README

  use BS\SwitchInput;

  // Administer → Users: everyone who signed in, with a switch per access level; saved by usergrant.php
  Config::Init();

  $Levels = User::Levels();
  // Only users with the menu's Edit right on this page may grant access, the others see the switches
  $CanEdit = \Auth\Base::CanEdit();
  $ValidFields = ['name', 'email', 'provider', 'last_login'];
  $Data = array_map(fn($User) => $User + ['name' => trim(($User['first_name'] ?? '') . ' ' . ($User['last_name'] ?? ''))], User::All());
  // Sorted as the heading asks (?_order= and ?_desc=), newest sign-in first otherwise
  $Order = QueryString::Get('_order');
  if (in_array($Order, $ValidFields, true))
  {
    usort($Data, fn($A, $B) => strcasecmp((string)$A[$Order], (string)$B[$Order]) * (empty(QueryString::Get('_desc')) ? 1 : -1));
  }

  Page::AddContent($Result = new HTML\Div());
  $Result->Format->Padding(\BS\Side::All, \BS\PadSize::Pad3);
  $Result->Format->MaxWidth(\BS\ScreenSize::ExtraLarge->Width());
  $Result->Format->Center();
  // Users who sign in with a password are added here
  $Passwords = \Auth\Password::CanEmail() && $CanEdit;
  if ($Passwords)
  {
    $Result->AddChild($Add = new \BS\Button('Add a user', \BS\Icon::PersonPlus, Config::FrameworkURL('admin/password.php')));
    $Add->Modal();
    $Add->Format->Margin(\BS\Side::Bottom, \BS\PadSize::Pad3);
  }
  // Scrolls sideways inside its own box on screens narrower than large, instead of running off the page
  $Result->AddChild($Wrapper = new \BS\ResponsiveTable(\BS\ScreenSize::Large));
  $Table = $Wrapper->Table();

  $Table->Striped();
  $Table->Caption(count($Data) . ' users. Revoking Public blocks a user from signing in. Access changes apply when a user\'s sign-in is next renewed, within 30 minutes.');

  $Row = $Table->Header()->AddRow();
  $Row->StickyTop();
  $Row->AddHeadingCell('Name')->Sort('name');
  $Row->AddHeadingCell('Email')->Sort('email');
  $Row->AddHeadingCell('Signs in with')->Sort('provider');
  $Row->AddHeadingCell('Last sign-in')->Sort('last_login');
  foreach ($Levels as $Code => $Description)
  {
    $Cell = $Row->AddHeadingCell(ucfirst($Code));
    $Cell->AddAttribute('title', $Description);
  }

  foreach ($Data as $Record)
  {
    $Access = $Record['access'];
    $Row = $Table->Body()->AddRow();
    $Row->AddCell(($Record['name'] ?: '-') . (User::Blocked($Access) ? ' (blocked)' : ''));
    $Row->AddCell($Record['email'] ?? '-');
    // A password account works once a link chose its password
    $Provider = $Row->AddCell(User::ProviderTitle($Record['provider']) . (($Record['verified'] ?? null) === 'f' ? ' (invited)' : ''));
    if ($Passwords && $Record['provider'] == 'password')
    {
      $Provider->AddChild($Set = new \BS\Button(null, \BS\Icon::Key, Config::FrameworkURL('admin/password.php') . '?userid=' . (int)$Record['user_id']));
      $Set->Modal();
      $Set->Color(\BS\Color::Secondary);
      $Set->AddClass('btn-sm');
      $Set->Format->Margin(\BS\Side::Start, \BS\PadSize::Pad2);
      $Set->AddAttribute('title', 'Email a password link');
      $Set->AddAttribute('aria-label', 'Email a password link to ' . ($Record['name'] ?: $Record['email'] ?? 'user ' . $Record['user_id']));
    }
    $Row->AddCell(empty($Record['last_login']) ? 'never' : date('d/m/Y H:i', strtotime($Record['last_login'])));

    foreach ($Levels as $Code => $Description)
    {
      $Switch = new SwitchInput(in_array($Code, $Access, true));
      $Switch->Label(ucfirst($Code) . ' access for ' . ($Record['name'] ?: $Record['email'] ?? 'user ' . $Record['user_id']), false);
      $Switch->Data('userid', (int)$Record['user_id']);
      $Switch->Data('code', $Code);
      // Admins can't take their own admin access away or block themselves, so there is always one left
      if (!$CanEdit || (in_array($Code, ['admin', 'public'], true) && $Record['user_id'] == \Auth\Base::ID()))
      {
        $Switch->Disabled();
      }
      $Switch->OnChange('toast.call(this, ' . json_encode(Config::FrameworkURL('admin/usergrant.php'), JSON_UNESCAPED_SLASHES) . ');');
      $Row->AddCell($Switch);
    }
  }
