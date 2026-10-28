<?php
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README

  use Auth\Password;
  use BS\{AlertSuccess, AlertWarning, Button, Card, Icon, PadSize, ScreenSize, Side};

  // Create an account (auth.password.signup): asks for an email address and emails a link (Password::RESET_SECONDS); the
  // link opens this page again to choose names and a password, which makes the account (the address is its username,
  // with public access) and signs them in. Open to anyone, as a page of its own.
  Config::Init(true, true);
  $Here = Config::FrameworkURL('signup.php');
  $Token = (string)(Post::Get('token') ?? QueryString::Get('token') ?? '');

  Page::AddContent($Card = new Card());
  $Card->Format->Center();
  $Card->Format->MaxWidth(ScreenSize::Small->Width());
  $Card->Format->Margin(Side::Top, PadSize::Pad4);
  $Card->AddHeader('Create an account');

  if (!Password::CanSignUp())
  {
    $Card->AddChild(new AlertWarning('Not here', 'This site doesn\'t let you make your own account. Please ask an administrator for one.'));
    return;
  }

  // The link from the email: names and a password make the account
  if ($Token !== '')
  {
    $Email = Password::SignUpEmail($Token);
    if ($Email === null)
    {
      $Card->AddChild(new AlertWarning('This link doesn\'t work any more', 'It expired, or the account was made with it already. '
        . 'Sign in, or ask for a new link below.'));
      $Card->AddChild($Again = new HTML\Anchor('Send me a new link', $Here, IsLink: true));
      $Again->Format->Margin(Side::All, PadSize::Pad2);
      return;
    }
    if (Post::Get('password') !== null)
    {
      $New = (string)Post::Get('password');
      $Problem = $New === (string)Post::Get('repeat') ? Password::Weakness($New, $Email) : 'The two passwords aren\'t the same.';
      $Added = $Problem ?? Storage\Store::Get()->AddPasswordUser($Email, $Email, (string)Post::Get('first_name'), (string)Post::Get('last_name'),
        Password::Hash($New));
      if (is_int($Added))
      {
        if (Password::SignInAs($Added))
        {
          $Card->AddChild(new AlertSuccess('Welcome!', 'Your account is ready, and you\'re signed in.'));
          Config::LoadPage(Config::Origin(), false, 1500);
        }
        else
        {
          $Card->AddChild(new AlertSuccess('Your account is ready', 'Sign in with your email address and password.'));
        }
        return;
      }
      $Card->AddChild(new AlertWarning('Not made', $Added));
    }
    $Card->AddParagraph("Almost done: your name, and a password for $Email of at least " . Password::MinLength() . ' characters.');
    $Form = Password::PageForm($Card, $Here, [
      'first_name' => ['First name', HTML\InputType::Text, 'given-name'],
      'last_name' => ['Surname', HTML\InputType::Text, 'family-name', false],
      'password' => ['Password', HTML\InputType::Password, 'new-password'],
      'repeat' => ['Password again', HTML\InputType::Password, 'new-password'],
    ], 'Create my account');
    $Form->AddChild($Hidden = new HTML\Input('token', HTML\InputType::Hidden));
    $Hidden->Value($Token);
    return;
  }

  // Asked for a link: counted like a wrong password, so it can't flood someone's inbox. Whether the address has an
  // account already only shows in the email it gets.
  if (($Email = mb_strtolower(trim((string)Post::Get('email')))) !== '')
  {
    $Storage = Storage\Store::Get();
    $Address = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if (!filter_var($Email, FILTER_VALIDATE_EMAIL))
    {
      $Card->AddChild(new AlertWarning('Not sent', 'That email address doesn\'t look right.'));
    }
    elseif ($Storage->AddressFailures($Address, Password::LOCK_SECONDS) >= Password::ADDRESS_LIMIT)
    {
      $Card->AddChild(new AlertWarning('Too many tries', 'Please try again in a few minutes.'));
      return;
    }
    else
    {
      $Storage->PasswordFailed(null, $Address);
      $AppName = Config::AppName();
      $Mail = new \BS\Email();
      $Mail->AddChild($Body = new Card(PadSize::Pad4));
      $Body->AddChild(new HTML\Header(3, 'Hi,'));
      if (Password::Taken($Email))
      {
        $Body->AddParagraph("Someone asked to make an account on $AppName with this email address, but you have one already. "
          . 'Sign in with it; if you forgot your password, choose a new one.');
        $Body->AddChild($Button = new Button('Forgot your password?', Icon::Key));
        $Button->Link(Config::Origin() . Config::FrameworkURL('resetpassword.php'));
      }
      else
      {
        $Body->AddParagraph("Someone asked to make an account on $AppName with this email address. If it was you, finish it here; the link works for "
          . intdiv(Password::RESET_SECONDS, 60) . ' minutes.');
        $Body->AddChild($Button = new Button('Create my account', Icon::PersonPlus));
        $Button->Link(Config::Origin() . "$Here?token=" . Password::SignUpToken($Email));
      }
      $Body->AddParagraph('If it wasn\'t you, ignore this email.');
      $Body->AddChild(Config::MailFooter());
      if (!$Mail->Send($Email, "Your $AppName account"))
      {
        error_log('sc-webframe: the sign-up email wasn\'t sent');
      }
      $Card->AddChild(new AlertSuccess('Check your email', "We sent a link to $Email. It should arrive within a minute or two; "
        . 'check your junk folder if it doesn\'t.'));
      return;
    }
  }

  $Card->AddParagraph('Give your email address, and we\'ll email you a link to finish making your account.');
  Password::PageForm($Card, $Here, ['email' => ['Email', HTML\InputType::Email, 'email']], 'Send me a link');
