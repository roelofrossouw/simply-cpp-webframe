<?php
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README

  use Auth\Password;
  use BS\{AlertSuccess, AlertWarning, Button, Grid, Icon, Input, ModalDialog, ScreenSize};

  // Administer → Users: add a user who signs in with a password (no userid), or set a password user's password (userid);
  // in a popup, for those with the Edit right on Users
  Config::Init(Access: Config::FrameworkURL('admin/users.php'), Shown: \BS\Dismiss::Modal, Edit: true);
  $Here = Config::FrameworkURL('admin/password.php');

  $UserID = filter_var(QueryString::Get('userid'), FILTER_VALIDATE_INT);
  $User = $UserID === false ? null : Storage\Store::Get()->Password($UserID);
  $Notice = null;

  Page::AddContent($Dialog = new ModalDialog($User === null ? 'Add a user' : 'Set a password', ScreenSize::Medium));

  if (!Password::Configured())
  {
    $Dialog->AddChild(new AlertWarning('Passwords are off', 'Turn on signing in with a password first: auth.password.enabled in Administer → Config.'));
    return;
  }
  if ($UserID !== false && $User === null)
  {
    $Dialog->AddChild(new AlertWarning('No password', 'This user signs in with another provider.'));
    return;
  }

  if (QueryString::Get('action') == 'save')
  {
    $New = (string)Post::Get('password');
    if ($New !== (string)Post::Get('repeat'))
    {
      $Notice = new AlertWarning('Not saved', 'The two passwords aren\'t the same.');
    }
    elseif ($User !== null)
    {
      $Problem = Password::Set($UserID, $New);
      $Notice = $Problem === null ? new AlertSuccess('Password set', 'They sign in with it from now on; their other sign-ins end.')
        : new AlertWarning('Not saved', $Problem);
    }
    else
    {
      $Username = trim((string)Post::Get('username'));
      $Email = trim((string)Post::Get('email'));
      $Problem = match (true)
      {
        $Username === '' => 'Please give a username.',
        $Email !== '' && !filter_var($Email, FILTER_VALIDATE_EMAIL) => 'That email address doesn\'t look right.',
        default => Password::Weakness($New, $Username),
      };
      $Added = $Problem ?? Storage\Store::Get()->AddPasswordUser($Username, $Email, (string)Post::Get('first_name'), (string)Post::Get('last_name'),
        Password::Hash($New));
      if (is_int($Added))
      {
        $Notice = new AlertSuccess('User added', "$Username can sign in now, with Public access. Grant them more in the list.");
        // Show them in the list behind the popup
        $Dialog->AddChild(new HTML\Script('$(".menu-target").each(function () { const url = $(this).data("url"); '
          . 'if (url && url.includes("admin/users.php")) loadPage.call(this, url); });'));
      }
      else
      {
        $Notice = new AlertWarning('Not added', $Added);
      }
    }
  }

  if ($Notice !== null)
  {
    $Dialog->AddChild($Notice);
  }
  if ($User !== null)
  {
    $Dialog->AddChild(new HTML\Paragraph('For ' . $User['subject'] . ($User['email'] ? " ({$User['email']})" : '')
      . '. They sign in with the new password from now on, and their other sign-ins end.'));
  }
  $Dialog->AddChild($Form = new HTML\Form());
  $Form->AddChild($Fields = new Grid(1));
  $Fields->Columns(2, ScreenSize::Small);
  if ($User === null)
  {
    // What was typed stays when something wasn't right
    $Keep = !($Notice instanceof AlertSuccess);
    $Fields->Col($Username = new Input('username', 'Username'));
    $Username->InputElement()->Required = true;
    $Username->InputElement()->AutoComplete = 'off';
    $Fields->Col($Email = new Input('email', 'Email (for password resets)', HTML\InputType::Email));
    $Fields->NewRow(1);
    $Fields->Col($First = new Input('first_name', 'First name'));
    $Fields->Col($Last = new Input('last_name', 'Surname'));
    foreach (['username' => $Username, 'email' => $Email, 'first_name' => $First, 'last_name' => $Last] as $Name => $Input)
    {
      $Input->Value($Keep ? (string)Post::Get($Name) : '');
    }
    $Fields->NewRow(1);
  }
  foreach (['password' => 'Password', 'repeat' => 'The password again'] as $Name => $Caption)
  {
    $Fields->Col($Input = new Input($Name, $Caption, HTML\InputType::Password));
    $Input->InputElement()->Required = true;
    $Input->InputElement()->MinLength = Password::MinLength();
    $Input->InputElement()->AutoComplete = 'new-password';
  }
  $Form->AddChild($Rule = new HTML\Paragraph('At least ' . Password::MinLength() . ' characters. Tell them the password yourself; they can change it on Your profile.'));
  $Rule->AddClass('small');
  $Rule->Format->Color(\BS\Color::BodySecondary);

  $Query = $User === null ? '' : "&userid=$UserID";
  $Dialog->AddButton($Save = new Button($User === null ? 'Add' : 'Set password', Icon::Check2, "$Here?action=save$Query"));
  $Save->Modal();
