<?php
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README

  use Auth\Base;
  use BS\{AlertSuccess, AlertWarning, Avatar, Button, Color, Grid, Icon, Input, ModalDialog, ScreenSize, Side, PadSize};

  // Your profile (the status bar's profile item): what the site calls you, your photo, a test email; any signed in user
  Config::Init(Access: Config::ANY_USER);
  $Here = Config::FrameworkURL('profile.php');

  $UserID = (int)Base::ID();
  $Notice = null;

  // Changes post back to this page, which then shows the profile again
  switch (QueryString::Get('action'))
  {
    case 'save':
      $Saved = User::SetDisplay($UserID, (string)Post::Get('display_as'), Post::Get('nickname'));
      if ($Saved && Post::Get('first_name') !== null && User::CanEditName(User::Profile($UserID)))
      {
        $Saved = User::SetName($UserID, (string)Post::Get('first_name'), (string)Post::Get('last_name'));
      }
      $Notice = $Saved ? new AlertSuccess('Saved', 'Your profile is up to date.') : new AlertWarning('Not saved', 'Something in the form wasn\'t right, please check it and try again.');
      break;
    case 'photo':
      $File = $_FILES['photo'] ?? null;
      $Saved = $File !== null && $File['error'] === UPLOAD_ERR_OK && is_uploaded_file($File['tmp_name'])
        && User::SetPhoto($UserID, file_get_contents($File['tmp_name']));
      $Notice = $Saved ? new AlertSuccess('Looking good', 'Your new photo is saved.') : new AlertWarning('Photo not saved', 'That file didn\'t work as a photo. Try a JPEG or PNG image.');
      break;
    case 'testemail':
      $Notice = SendTestEmail(User::Profile($UserID));
      break;
    // A password account changes its password and email address through links emailed to it
    case 'passwordlink':
      $Account = Storage\Store::Get()->Password($UserID);
      $Notice = $Account !== null && \Auth\Password::SendPasswordLink($UserID)
        ? new AlertSuccess('Check your email', "We sent {$Account['email']} a link to choose a new password. Your password works until you do.", \BS\Icon::Envelope)
        : new AlertWarning('Not sent', 'Something went wrong while sending the email. Please try again later.');
      break;
    case 'email':
      $New = \Auth\Password::Address((string)Post::Get('new_email'));
      $Notice = match (true)
      {
        Storage\Store::Get()->Password($UserID) === null => new AlertWarning('Not changed', 'Your email address comes from your sign-in provider.'),
        $New === null => new AlertWarning('Not changed', 'That email address doesn\'t look right.'),
        \Auth\Password::Taken($New) => new AlertWarning('Not changed', 'There is an account for that address already.'),
        !\Auth\Password::SendEmailLink($UserID, $New) => new AlertWarning('Not sent', 'Something went wrong while sending the email. Please try again later.'),
        default => new AlertSuccess('Check your email', "We sent $New a link. Your email address changes once you open it; until then you sign in with the one you have.", \BS\Icon::Envelope),
      };
      break;
    case 'removephoto':
      User::RemovePhoto($UserID);
      $Notice = new AlertSuccess('Photo removed', 'We\'ll show your initials instead.');
      break;
  }

  /**
   * Email the user a short test message, to check that email reaches them
   */
  function SendTestEmail(array $Profile): \BS\Alert
  {
    if (!filter_var($Profile['email'] ?? '', FILTER_VALIDATE_EMAIL))
    {
      return new AlertWarning('No email address', 'Your sign-in account didn\'t give us an email address we can send to.');
    }
    $Name = User::DisplayName($Profile);
    $AppName = Config::AppName();
    $Email = new \BS\Email();
    $Email->AddChild($Card = new \BS\Card(PadSize::Pad4));
    $Card->AddChild(new HTML\Header(3, "Hi $Name,"));
    $Card->AddParagraph("This is a test email from $AppName. You asked for it from your profile, to check that our emails reach you.");
    $Card->AddParagraph('If you can read this, everything works. There\'s nothing else you need to do.');
    $Card->AddChild($Button = new Button("Open $AppName", Icon::BoxArrowUpRight));
    $Button->Link(Config::Origin());
    $Card->AddChild(Config::MailFooter());
    if (!$Email->Send("$Name <{$Profile['email']}>", "Test email from $AppName"))
    {
      return new AlertWarning('Email not sent', 'Something went wrong while sending the email. Please try again later.');
    }
    $Redirect = Config::Get('mail.redirect');
    return new AlertSuccess('Email sent', 'We sent a test email to ' . ($Redirect ? "$Redirect (this server sends all email there)" : $Profile['email'])
      . '. It should arrive within a minute or two, check your junk folder if it doesn\'t.', \BS\Icon::Envelope);
  }

  $Profile = User::Profile($UserID);
  $Name = User::DisplayName($Profile);
  $Provider = User::ProviderTitle($Profile['provider']);

  Page::AddContent($Dialog = new ModalDialog('Your profile', ScreenSize::Medium));
  if ($Notice !== null)
  {
    $Dialog->AddChild($Notice);
    $Notice->Format->Padding(Side::All, PadSize::Pad3);
    // Keep the status bar in step with the new name
    $Dialog->AddChild(new HTML\Script('$("#profile span").last().text(' . json_encode($Name) . ');'));
  }

  // Photo
  $Dialog->AddChild($Top = new HTML\Div());
  $Top->AddClasses(['d-flex', 'align-items-center', 'gap-3']);
  $Top->Format->Margin(Side::Bottom, PadSize::Pad4);
  $Top->AddChild(new Avatar($Profile['photo'], $Name, 96));
  $Top->AddChild($Photo = new HTML\Div());
  $Photo->AddHeader('Hi ' . $Name . '!', 4);
  $Photo->AddChild($Upload = new HTML\Input('photo', HTML\InputType::File));
  $Upload->Accept = 'image/*';
  $Upload->AddClass('d-none');
  $Upload->OnChange('uploadPhoto.call(this, ' . json_encode("$Here?action=photo", JSON_UNESCAPED_SLASHES) . ');');
  $FromProvider = $Profile['photo'] && $Profile['photo_source'] == 'provider';
  if ($FromProvider)
  {
    $Photo->AddChild($Note = new HTML\Paragraph('This photo comes from your ' . User::ProviderTitle($Profile['provider']) . ' account.'));
    $Note->Format->Color(Color::BodySecondary);
  }
  $Photo->AddChild($Change = new Button($FromProvider ? 'Use my own photo' : ($Profile['photo'] ? 'Change photo' : 'Add a photo'), Icon::Camera));
  $Change->Format->Margin(Side::End, PadSize::Pad2);
  $Change->OnClick('$("#' . $Upload->Id . '").click();');
  if ($Profile['photo'])
  {
    $Photo->AddChild($Remove = new Button('Remove', Icon::Trash, "$Here?action=removephoto"));
    $Remove->Color(Color::Secondary);
    $Remove->Modal();
  }

  $Dialog->AddChild($Form = new HTML\Form());

  // How the app calls you
  $Form->AddChild(new HTML\Header(5, 'What should we call you?'));
  $Form->AddChild($Grid = new Grid(1));
  $Grid->Columns(2, ScreenSize::Small);

  $Floating = new HTML\Div();
  $Floating->AddClass('form-floating');
  $Floating->AddChild($Select = new HTML\Select());
  $Select->Name = 'display_as';
  $Select->Id = uniqid('display_');
  $Select->AddClass('form-select');
  foreach (User::DISPLAY_AS as $Value => $Caption)
  {
    $Select->AddChild($Option = new HTML\Option($Caption));
    $Option->Value = $Value;
    $Option->Selected = $Value == $Profile['display_as'];
  }
  $Floating->AddChild(new HTML\Label('Call me by', $Select->Id));
  $Grid->Col($Floating);

  $Grid->Col($Nickname = new Input('nickname', 'Nickname'));
  $Nickname->Value($Profile['nickname'] ?? '');
  $Nickname->InputElement()->MaxLength = 50;
  $NicknameColumn = $Nickname->Parent();
  $NicknameColumn->Id = uniqid('nickname_');
  if ($Profile['display_as'] != 'nickname')
  {
    $NicknameColumn->AddClass('d-none');
  }
  $Select->OnChange('$("#' . $NicknameColumn->Id . '").toggleClass("d-none", this.value !== "nickname");');

  // Details from the sign-in provider; password users give their own
  $IsPassword = $Profile['provider'] == 'password';
  $CanEditName = User::CanEditName($Profile);
  $Form->AddChild($Heading = new HTML\Header(5, 'About you'));
  $Heading->Format->Margin(Side::Top, PadSize::Pad4);
  $Form->AddChild(new HTML\Paragraph(match (true)
  {
    $IsPassword => 'You sign in with your email address and a password, so your name is yours to keep up to date.',
    $CanEditName => "$Provider didn't share your name with us, so you can fill it in here. Your email comes from $Provider.",
    default => "These details come from your $Provider account, so you can change them there.",
  }));
  $Form->AddChild($Details = new Grid(1));
  $Details->Columns(2, ScreenSize::Small);
  foreach (['first_name' => 'First name', 'last_name' => 'Surname'] as $Field => $Caption)
  {
    $Details->Col($Input = new Input($Field, $Caption));
    $Input->Value($Profile[$Field] ?? '');
    if (!$CanEditName)
    {
      $Input->InputElement()->ReadOnly = true;
    }
  }
  $Details->NewRow(1);
  $Details->Col($Email = new Input('email', $IsPassword ? 'Email' : "Email (from $Provider)", HTML\InputType::Email));
  $Email->Value($Profile['email'] ?? '');
  $Email->InputElement()->ReadOnly = true;
  $Email->InputElement()->RemoveAttribute('name');
  if (filter_var($Profile['email'] ?? '', FILTER_VALIDATE_EMAIL))
  {
    $Details->Col($Test = new Button('Send me a test email', Icon::Envelope, "$Here?action=testemail"));
    $Test->Color(Color::Secondary);
    $Test->SkipForm();
    $Test->Modal();
  }

  $Form->AddChild($Since = new HTML\Paragraph(($IsPassword ? 'Your account was made on ' : "You signed in with $Provider for the first time on ")
    . date('j F Y', strtotime($Profile['created'])) . '.'));
  $Since->Format->Color(Color::BodySecondary);
  $Since->Format->Margin(Side::Top, PadSize::Pad3);

  // Your sign-in: both changes go through a link emailed to you; a form of its own, so Save doesn't post it
  if ($IsPassword)
  {
    $Dialog->AddChild($Change = new HTML\Form());
    $Change->AddChild($Heading = new HTML\Header(5, 'Your sign-in'));
    $Heading->Format->Margin(Side::Top, PadSize::Pad4);
    $Change->AddChild(new HTML\Paragraph('We email you a link to change your password, or to your new address to change your email.'));
    $Change->AddChild($Fields = new Grid(1));
    $Fields->Columns(2, ScreenSize::Small);
    $Fields->Col($NewEmail = new Input('new_email', 'New email address', HTML\InputType::Email));
    $NewEmail->InputElement()->Required = true;
    $NewEmail->InputElement()->AutoComplete = 'email';
    $Fields->Col($EmailButton = new Button('Change email', Icon::Envelope, "$Here?action=email"));
    $EmailButton->Color(Color::Secondary);
    $EmailButton->Modal();
    $Change->AddChild($PasswordButton = new Button('Email me a link to change my password', Icon::Key, "$Here?action=passwordlink"));
    $PasswordButton->Color(Color::Secondary);
    $PasswordButton->SkipForm();
    $PasswordButton->Modal();
    $PasswordButton->Format->Margin(Side::Top, PadSize::Pad3);
  }

  $Dialog->AddButton($Save = new Button('Save', Icon::Check2, "$Here?action=save"));
  $Save->Modal();
