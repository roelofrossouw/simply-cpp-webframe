<?php
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README

  use Auth\Password;
  use BS\{AlertSuccess, AlertWarning, Button, Card, Icon, Input, PadSize, ScreenSize, Side};

  // Forgot your password?: asks for the username or email and emails a link (Password::RESET_SECONDS) to the address the
  // user has; the link opens this page again, to choose a new password. Open to anyone, as a page of its own.
  Config::Init(true, true);
  $Here = Config::FrameworkURL('resetpassword.php');
  $Token = (string)(Post::Get('token') ?? QueryString::Get('token') ?? '');

  Page::AddContent($Card = new Card());
  $Card->Format->Center();
  $Card->Format->MaxWidth(ScreenSize::Small->Width());
  $Card->Format->Margin(Side::Top, PadSize::Pad4);
  $Card->AddHeader('Forgot your password?');

  if (!Password::CanReset())
  {
    $Card->AddChild(new AlertWarning('Not here', 'This site doesn\'t reset passwords by email. Please ask an administrator to set a new one.'));
    return;
  }

  // A sign-in form's parts: a form posting here, and its fields
  function ResetForm(Card $Card, array $Fields, string $Caption): HTML\Form
  {
    $Card->AddChild($Form = new HTML\Form());
    $Form->Method = 'post';
    $Form->Action = Config::FrameworkURL('resetpassword.php');
    $Form->Format->Margin(Side::All, PadSize::Pad2);
    foreach ($Fields as $Name => [$Label, $Type, $AutoComplete])
    {
      $Form->AddChild($Input = new Input($Name, $Label, $Type));
      $Input->InputElement()->Required = true;
      $Input->InputElement()->AutoComplete = $AutoComplete;
      if ($Type == HTML\InputType::Password)
      {
        $Input->InputElement()->MinLength = Password::MinLength();
      }
      $Input->Format->Margin(Side::Bottom, PadSize::Pad2);
    }
    $Form->AddChild($Submit = new HTML\Button($Caption));
    $Submit->Type = 'submit';
    $Submit->AddClasses(['btn', 'btn-primary', 'mt-2']);
    return $Form;
  }

  function SignInLink(Card $Card, string $Caption): void
  {
    $Card->AddChild($Link = new Button($Caption, Icon::BoxArrowInRight));
    $Link->Link(Config::FrameworkURL('login.php') . '?provider=password');
    $Link->Format->Margin(Side::All, PadSize::Pad2);
  }

  // The link from the email: choose a new password
  if ($Token !== '')
  {
    $UserID = Password::TokenUser($Token, 'reset');
    if ($UserID === null)
    {
      $Card->AddChild(new AlertWarning('This link doesn\'t work any more', 'It expired, or it was used already. Ask for a new one below.'));
      $Card->AddChild($Again = new HTML\Anchor('Send me a new link', $Here, IsLink: true));
      $Again->Format->Margin(Side::All, PadSize::Pad2);
      return;
    }
    if (Post::Get('password') !== null)
    {
      $New = (string)Post::Get('password');
      $Problem = $New === (string)Post::Get('repeat') ? Password::Set($UserID, $New) : 'The two passwords aren\'t the same.';
      if ($Problem === null)
      {
        $Card->AddChild(new AlertSuccess('Password changed', 'Sign in with your new password. Anywhere else you were signed in, you\'re signed out.'));
        SignInLink($Card, 'Sign in');
        return;
      }
      $Card->AddChild(new AlertWarning('Not changed', $Problem));
    }
    $Card->AddParagraph('Choose a new password for ' . Storage\Store::Get()->Password($UserID)['subject'] . ', at least ' . Password::MinLength() . ' characters.');
    $Form = ResetForm($Card, ['password' => ['New password', HTML\InputType::Password, 'new-password'],
      'repeat' => ['New password again', HTML\InputType::Password, 'new-password']], 'Change password');
    $Form->AddChild($Hidden = new HTML\Input('token', HTML\InputType::Hidden));
    $Hidden->Value($Token);
    return;
  }

  // Asked for a link: the same answer whether or not the user exists, and counted like a wrong password, so it can't be
  // used to find usernames or flood someone's inbox
  if (($Name = trim((string)Post::Get('name'))) !== '')
  {
    $Storage = Storage\Store::Get();
    $Address = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if ($Storage->AddressFailures($Address, Password::LOCK_SECONDS) >= Password::ADDRESS_LIMIT)
    {
      $Card->AddChild(new AlertWarning('Too many tries', 'Please try again in a few minutes.'));
      return;
    }
    $Storage->PasswordFailed(null, $Address);
    $UserID = $Storage->PasswordUserID($Name);
    $User = $UserID === null ? null : $Storage->Password($UserID);
    if ($User !== null && filter_var($User['email'] ?? '', FILTER_VALIDATE_EMAIL) && ($Reset = Password::Token($UserID, 'reset', Password::RESET_SECONDS)) !== null)
    {
      $Profile = User::Profile($UserID);
      $Who = User::DisplayName($Profile);
      $AppName = Config::AppName();
      $Email = new \BS\Email();
      $Email->AddChild($Mail = new Card(PadSize::Pad4));
      $Mail->AddChild(new HTML\Header(3, "Hi $Who,"));
      $Mail->AddParagraph("Someone asked to reset the password of {$User['subject']} on $AppName. If it was you, choose a new one here; the link works for "
        . intdiv(Password::RESET_SECONDS, 60) . ' minutes, once.');
      $Mail->AddChild($Button = new Button('Choose a new password', Icon::Key));
      $Button->Link(Config::Origin() . "$Here?token=$Reset");
      $Mail->AddParagraph('If it wasn\'t you, ignore this email: your password stays as it is.');
      $Mail->AddChild(Config::MailFooter());
      if (!$Email->Send("$Who <{$User['email']}>", "Your $AppName password"))
      {
        error_log("sc-webframe: the password reset email to user $UserID wasn't sent");
      }
    }
    $Card->AddChild(new AlertSuccess('Check your email', 'If that user has an email address with us, we sent a link to choose a new password. '
      . 'It should arrive within a minute or two; check your junk folder if it doesn\'t.'));
    SignInLink($Card, 'Back to signing in');
    return;
  }

  $Card->AddParagraph('Give your username or email address, and we\'ll email you a link to choose a new password.');
  ResetForm($Card, ['name' => ['Username or email', HTML\InputType::Text, 'username']], 'Send me a link');
