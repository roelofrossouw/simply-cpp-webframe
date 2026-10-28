<?php
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README

  use Auth\Password;
  use BS\{AlertSuccess, AlertWarning, Button, Grid, Icon, Input, ModalDialog, ScreenSize};

  // Administer → Users: add an account that signs in with its email address and a password (no userid), which emails
  // it an invitation to choose the password; or email a password account a link to choose a new one (userid). Admins
  // never see or set passwords. In a popup, for those with the Edit right on Users.
  Config::Init(Access: Config::FrameworkURL('admin/users.php'), Shown: \BS\Dismiss::Modal, Edit: true);
  $Here = Config::FrameworkURL('admin/password.php');

  $UserID = filter_var(QueryString::Get('userid'), FILTER_VALIDATE_INT);
  $Account = $UserID === false ? null : Storage\Store::Get()->Password($UserID);

  Page::AddContent($Dialog = new ModalDialog($Account === null ? 'Add a user' : 'Password link', ScreenSize::Medium));

  if (!Password::CanEmail())
  {
    $Dialog->AddChild(new AlertWarning('Passwords are off', 'Signing in with a password needs auth.password.enabled, and mail, in Administer → Config.'));
    return;
  }
  if ($UserID !== false && $Account === null)
  {
    $Dialog->AddChild(new AlertWarning('No password', 'This user signs in with another provider.'));
    return;
  }

  // An account: send it a link
  if ($Account !== null)
  {
    $Invite = $Account['hash'] === null;
    if (QueryString::Get('action') == 'send')
    {
      $Dialog->AddChild(Password::SendPasswordLink($UserID, $Invite)
        ? new AlertSuccess('Sent', "We emailed {$Account['email']} a link to choose " . ($Invite ? 'their password.' : 'a new password.'))
        : new AlertWarning('Not sent', 'Something went wrong while sending the email. Please try again later.'));
      return;
    }
    $Dialog->AddChild(new HTML\Paragraph($Invite
      ? "{$Account['email']} hasn't chosen a password yet. Send the invitation again? Its link works for " . intdiv(Password::INVITE_SECONDS, 86400) . ' days.'
      : "Email {$Account['email']} a link to choose a new password? Their password works until they do."));
    $Dialog->AddButton($Send = new Button($Invite ? 'Send the invitation' : 'Send the link', Icon::Envelope, "$Here?action=send&userid=$UserID"));
    $Send->Modal();
    return;
  }

  $Notice = null;
  if (QueryString::Get('action') == 'save')
  {
    $Email = Password::Address((string)Post::Get('email'));
    $Added = $Email === null ? 'That email address doesn\'t look right.'
      : Storage\Store::Get()->AddPasswordUser($Email, (string)Post::Get('first_name'), (string)Post::Get('last_name'), null);
    if (is_int($Added))
    {
      $Notice = Password::SendPasswordLink($Added, true)
        ? new AlertSuccess('User added', "We emailed $Email an invitation to choose their password; their account works once they have. "
          . 'They have Public access; grant them more in the list.')
        : new AlertWarning('Invitation not sent', "The account for $Email is made, but the email didn't go out. Send it again with the key button in the list.");
      // Show them in the list behind the popup
      $Dialog->AddChild(new HTML\Script('$(".menu-target").each(function () { const url = $(this).data("url"); '
        . 'if (url && url.includes("admin/users.php")) loadPage.call(this, url); });'));
    }
    else
    {
      $Notice = new AlertWarning('Not added', $Added);
    }
  }

  if ($Notice !== null)
  {
    $Dialog->AddChild($Notice);
  }
  $Dialog->AddChild(new HTML\Paragraph('They get an email with a link to choose their password, and sign in with their email address.'));
  $Dialog->AddChild($Form = new HTML\Form());
  $Form->AddChild($Fields = new Grid(1));
  $Fields->Columns(2, ScreenSize::Small);
  // What was typed stays when something wasn't right
  $Keep = !($Notice instanceof AlertSuccess);
  $Fields->Col($EmailInput = new Input('email', 'Email', HTML\InputType::Email));
  $EmailInput->InputElement()->Required = true;
  $EmailInput->InputElement()->AutoComplete = 'off';
  $Fields->NewRow(1);
  $Fields->Col($First = new Input('first_name', 'First name'));
  $Fields->Col($Last = new Input('last_name', 'Surname'));
  foreach (['email' => $EmailInput, 'first_name' => $First, 'last_name' => $Last] as $Name => $Input)
  {
    $Input->Value($Keep ? (string)Post::Get($Name) : '');
  }
  $Dialog->AddButton($Save = new Button('Add and invite', Icon::Check2, "$Here?action=save"));
  $Save->Modal();
