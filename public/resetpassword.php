<?php
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README

  use Auth\Password;
  use BS\{AlertSuccess, AlertWarning, Card, PadSize, ScreenSize, Side};

  // Choosing a password, always through a link emailed to the account's address: Forgot your password? asks for the
  // address and sends one (Password::LINK_SECONDS); an admin's invitation, Your profile and the admin's key button send
  // one too. The link opens this page again to choose the password, which signs you in. Open to anyone, as a page of its own.
  Config::Init(true, true);
  $Here = Config::FrameworkURL('resetpassword.php');
  $Token = (string)(Post::Get('token') ?? QueryString::Get('token') ?? '');

  Page::AddContent($Card = new Card());
  $Card->Format->Center();
  $Card->Format->MaxWidth(ScreenSize::Small->Width());
  $Card->Format->Margin(Side::Top, PadSize::Pad4);
  // Its own page, without the menu: a way back
  Auth\Base::AddHome($Card);

  if (!Password::CanEmail())
  {
    $Card->AddHeader('Your password');
    $Card->AddChild(new AlertWarning('Not here', 'This site can\'t email password links. Please ask an administrator.'));
    return;
  }

  // The link from the email: choose the password
  if ($Token !== '')
  {
    $Card->AddHeader('Choose a password');
    $Account = Password::TokenAccount($Token, 'password');
    if ($Account === null)
    {
      $Card->AddChild(new AlertWarning('This link doesn\'t work any more', 'It expired, or it was used already. Ask for a new one below.'));
      $Card->AddChild($Again = Password::PageLink('Send me a new link', $Here));
      $Again->Format->Margin(Side::All, PadSize::Pad2);
      return;
    }
    $UserID = (int)$Account['user_id'];
    if (Post::Get('password') !== null)
    {
      $New = (string)Post::Get('password');
      $Problem = $New === (string)Post::Get('repeat') ? Password::Set($UserID, $New) : 'The two passwords aren\'t the same.';
      if ($Problem === null)
      {
        if (Password::SignInAs($UserID))
        {
          $Card->AddChild(new AlertSuccess('Password saved', 'You\'re signed in. Anywhere else you were signed in, you\'re signed out.'));
          Config::LoadPage(Config::Origin(), false, 1500);
        }
        else
        {
          $Card->AddChild(new AlertWarning('Password saved', 'But your account is blocked from signing in. Please contact an administrator.'));
        }
        return;
      }
      $Card->AddChild(new AlertWarning('Not saved', $Problem));
    }
    $Card->AddParagraph("A password for {$Account['email']}, at least " . Password::MinLength() . ' characters.');
    $Form = Password::PageForm($Card, $Here, ['password' => ['Password', HTML\InputType::Password, 'new-password'],
      'repeat' => ['Password again', HTML\InputType::Password, 'new-password']], 'Save password');
    $Form->AddChild($Hidden = new HTML\Input('token', HTML\InputType::Hidden));
    $Hidden->Value($Token);
    return;
  }

  // Asked for a link: the same answer whether or not there's an account, and counted like a wrong password, so it can't
  // be used to find accounts or flood someone's inbox
  $Card->AddHeader('Forgot your password?');
  if (($Email = trim((string)Post::Get('email'))) !== '')
  {
    if (!Password::MayTry())
    {
      $Card->AddChild(new AlertWarning('Too many tries', 'Please try again in a few minutes.'));
      return;
    }
    if (($Address = Password::Address($Email)) !== null && ($UserID = Storage\Store::Get()->PasswordUserID($Address)) !== null)
    {
      Password::SendPasswordLink($UserID);
    }
    $Card->AddChild(new AlertSuccess('Check your email', 'If there\'s an account for that address, we sent it a link to choose a new password. '
      . 'It should arrive within a minute or two; check your junk folder if it doesn\'t.', \BS\Icon::Envelope));
    return;
  }

  $Card->AddParagraph('Give your email address, and we\'ll email you a link to choose a new password.');
  Password::PageForm($Card, $Here, ['email' => ['Email', HTML\InputType::Email, 'username']], 'Send me a link');
