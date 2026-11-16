<?php

  // Mail branding (BS\Email::Brand): the mail section's logo, header_*, button_* and link_color settings as inline styles.
  // Without them an email is exactly as before (tests/resource/mail-*.html: 1.21.1's output, ahead of bootstrap-email).
  require __DIR__ . '/check.inc';

  use BS\Email;

  Config::Load();

  function Mails(): array
  {
    $Other = new Email();
    $Other->AddChild($Card = new BS\Card(BS\PadSize::Pad4));
    $Card->AddChild(new HTML\Header(3, 'Hi Sam,'));
    $Card->AddParagraph('Please choose a password <for> your account & "sign in".');
    $Card->AddChild($Button = new BS\Button('Choose a password', BS\Icon::Key));
    $Button->Link('https://www.example.com/reset?token=a&b=1');
    $Card->AddChild(Config::MailFooter());
    return ['code' => Auth\EmailCode::CodeMail('123456'), 'button' => $Other];
  }

  $Settings = ['logo', 'header_bg', 'header_color', 'header_border', 'button_bg', 'button_color', 'link_color'];
  function Brand(array $Values): void
  {
    global $Settings;
    foreach ($Settings as $Name)
    {
      Config::Set("mail.$Name", $Values[$Name] ?? null);
    }
  }

  // No branding: as before, the sign-in code's Box() marker taken out again
  Brand([]);
  foreach (Mails() as $Name => $Mail)
  {
    Same(file_get_contents(__DIR__ . "/resource/mail-$Name.html"), $Mail->Unformatted(), "$Name: unbranded, as 1.21.1 made it");
  }

  // 1 Gun's colours
  Brand(['logo' => 'https://www.example.com/img/mail-logo.png', 'header_bg' => '#25292d', 'header_color' => '#d4ad62',
         'header_border' => '3px solid #8a6a2f', 'button_bg' => '#8a6a2f', 'button_color' => '#ffffff', 'link_color' => '#7a5c22']);
  ['code' => $Code, 'button' => $Button] = array_map(fn($Mail) => $Mail->Unformatted(), Mails());
  Check(str_contains($Code, '<body class="bg-light"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width: 100%; background-color: #25292d; border-bottom: 3px solid #8a6a2f;"><tr>'), 'the header right after <body>');
  Check(str_contains($Code, '<img src="https://www.example.com/img/mail-logo.png" width="40" height="40" alt="" style="display: block; width: 40px; height: 40px;">'), 'the logo, 40 x 40');
  Check(str_contains($Code, 'font-size: 22px; font-weight: bold; color: #d4ad62;">Test site</td>'), 'the app name in header_color');
  Check(str_contains($Code, '<h1 class="text-center font-monospace" style="background-color: #8a6a2f; padding: 12px 16px; border-radius: 6px; color: #ffffff;">123456</h1>'), 'the sign-in code boxed in the button colours');
  Check(!str_contains($Code, 'mail-box'), 'no marker class left');
  Same(3, substr_count($Code, 'style="color: #7a5c22;"'), 'the footer\'s three links in link_color');
  Check(str_contains($Button, 'class="btn btn-primary" role="button" href="https://www.example.com/reset?token=a&amp;b=1" style="background-color: #8a6a2f; border-color: #8a6a2f; color: #ffffff;"'), 'btn-primary in the button colours, not in link_color');

  // Only some: no logo cell, no header colours; only links
  Brand(['header_bg' => 'navy']);
  $Code = Mails()['code']->Unformatted();
  Check(str_contains($Code, 'style="width: 100%; background-color: navy;"><tr><td style="padding: 12px 16px; font-size: 22px; font-weight: bold;">Test site</td>'), 'a header with only a background');
  Brand(['link_color' => 'rgb(10, 20, 30)']);
  $Code = Mails()['code']->Unformatted();
  Check(!str_contains($Code, 'role="presentation"') && str_contains($Code, 'style="color: rgb(10, 20, 30);"'), 'links only: no header');
  Check(str_contains($Code, '<h1 class="text-center font-monospace">123456</h1>'), 'the code unboxed without button colours');

  // Values that aren't what they should be are ignored: nothing gets in
  foreach (['red; background: url(x)', '"><script>', 'expression(alert(1))', '#12345', 'rgb(1,2)', '', ' ', 42, ['#fff']] as $Bad)
  {
    Same(null, Email::Colour($Bad), 'not a colour: ' . json_encode($Bad));
  }
  foreach (['#25292d', '#fff', '#ffff', '#11223344', 'rgb(10, 20, 30)', 'rgba(0,0,0,0.5)', 'rgb(10%, 20%, 30%)', 'navy', 'RebeccaPurple'] as $Good)
  {
    Same($Good, Email::Colour($Good), "a colour: $Good");
  }
  Same('3px solid #8a6a2f', Email::Border('3px solid #8a6a2f'), 'a border');
  Same('1px dashed', Email::Border(' 1px  DASHED '), 'a border without a colour');
  foreach (['3px solid #8a6a2f; color: red', '3px solid red red', '3em solid', 'solid solid', 'rgb(1,2,3) solid', '<b>', ''] as $Bad)
  {
    Same(null, Email::Border($Bad), 'not a border: ' . $Bad);
  }
  Same('https://www.example.com/logo.png', Email::Https('https://www.example.com/logo.png'), 'an https URL');
  foreach (['http://www.example.com/logo.png', 'javascript:alert(1)', 'https://', '//www.example.com/logo.png', 'https://www.example.com/a"b.png', 'https://www.example.com/a b.png', 'data:image/png;base64,AAAA', 42] as $Bad)
  {
    Same(null, Email::Https($Bad), 'not an https URL: ' . json_encode($Bad));
  }
  Brand(['logo' => 'http://www.example.com/logo.png', 'header_color' => 'red;}', 'button_bg' => 'url(x)', 'link_color' => '"><b>']);
  foreach (Mails() as $Name => $Mail)
  {
    Same(file_get_contents(__DIR__ . "/resource/mail-$Name.html"), $Mail->Unformatted(), "$Name: only bad values, so unbranded");
  }

  // The app's name is escaped in the header
  Brand(['header_bg' => '#000']);
  Config::Set('app.name', 'A & B <Co>');
  Check(str_contains(Mails()['button']->Unformatted(), '>A &amp; B &lt;Co&gt;</td>'), 'the app name escaped');

  // bootstrap-email (SC\BootstrapEmail or the program), when it's here, keeps the styles
  Config::Set('app.name', 'Test site');
  Brand(['logo' => 'https://www.example.com/img/mail-logo.png', 'header_bg' => '#25292d', 'header_color' => '#d4ad62',
         'header_border' => '3px solid #8a6a2f', 'button_bg' => '#8a6a2f', 'button_color' => '#ffffff', 'link_color' => '#7a5c22']);
  $Mail = Mails()['code'];
  $Formatted = $Mail->Html();
  if ($Formatted !== $Mail->Unformatted())
  {
    Check(str_contains($Formatted, 'bgcolor="#25292d"') || str_contains($Formatted, 'background-color: #25292d'), 'formatted: header background kept');
    Check(str_contains($Formatted, 'border-bottom-color: #8a6a2f') || str_contains($Formatted, 'border-bottom: 3px solid #8a6a2f'), 'formatted: header border kept');
    Check(str_contains($Formatted, 'mail-logo.png'), 'formatted: logo kept');
    Check(str_contains($Formatted, 'color: #d4ad62'), 'formatted: header colour kept');
    Check(str_contains($Formatted, 'background-color: #8a6a2f') && str_contains($Formatted, 'color: #ffffff'), 'formatted: code box kept');
    Same(3, substr_count($Formatted, 'color: #7a5c22'), 'formatted: link colours kept');
  }
  else
  {
    echo "bootstrap-email isn't here: the formatted checks are skipped\n";
  }
