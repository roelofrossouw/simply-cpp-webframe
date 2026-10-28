<?php
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README

  Config::Init(true, true);

  $AppName = Config::AppName();
  $CompanyName = Config::CompanyName();
  $CompanyCountry = Config::CompanyCountry();
  $Email = PolicyPage::Mail(Config::CompanyEmail());
  $Services = PolicyPage::Services();
  $Framework = Config::FrameworkURL();

  $Policy = new PolicyPage('Privacy Policy', PolicyPage::Updated());

  $Policy->Summary([
    "We only keep what $AppName needs to sign you in and to know what you may use.",
    'We never see or store your passwords.',
    'No advertising, no tracking, and we don\'t sell or share your data.',
    'You can see, correct or delete your data at any time.',
  ]);

  $Policy->Section('Who we are',
    "$AppName is run by $CompanyName" . ($CompanyCountry === '' ? '' : " in $CompanyCountry")
      . ' ("we" and "us" in this policy). We are responsible for the personal information described here.',
    PolicyPage::Text('If you have a question about your privacy, email us at ', clone $Email, '.'));

  $Policy->Section('What we collect',
    'We keep a small amount of information about you:',
    array_merge(
      PolicyPage::HasServices() ? ["From the service you sign in with ($Services): an identifier for your account with that service, and your name and email address, but only what you agree to share on its sign-in screen."] : [],
      \Auth\Password::Configured() ? ['If you sign in with a password: your username, name and email address as an administrator or you entered them, and your password, kept only as a one-way hash that can\'t be turned back into it.'] : [],
      [
        'What you add yourself: a profile photo, a nickname, how you\'d like to be addressed, and your name if your sign-in service didn\'t share it.',
        'What we record: when you first and last signed in, and the access an administrator gave you.',
      ]),
    (\Auth\Password::Configured() ? 'We don\'t collect payment details' : 'We don\'t collect passwords, payment details')
      . ' or your location, and we don\'t use analytics or advertising trackers.');

  $Policy->Section('How we use it',
    'We use your information only to run ' . $AppName . ':',
    [
      'to sign you in and keep you signed in',
      'to show you the parts of the site you have access to',
      'to address you the way you chose in your profile',
      'to email you about your account, for example a test email you asked for or a change to your access',
    ],
    'We don\'t send marketing emails, and we don\'t use your information for anything else.');

  $Policy->Section('Cookies and your browser',
    'We only use cookies that are needed to sign you in:',
    [
      'your sign-in, valid for 30 minutes at a time and renewed while you use the site',
      'which service you signed in with, so you can sign in quickly next time (6 months)',
      'a short security check while you sign in (10 minutes)',
      'the tokens your sign-in service gives us, so it can confirm it\'s still you (up to 6 months)',
    ],
    'Your browser also remembers your light or dark theme, and that you have seen our cookie notice. There are no third-party or tracking cookies.'
      . ' If you block cookies, you won\'t be able to sign in.');

  $Policy->Section('Who can see your data',
    [
      "The people who administer $AppName can see who signed in, with their name, email address, sign-in dates and access, so they can decide who may use what.",
      "The service you sign in with knows you signed in to $AppName. What it does with that is covered by its own privacy policy.",
      'We don\'t sell or rent your information, and we don\'t share it with anyone for marketing. We only disclose it when the law requires us to.',
    ]);

  $Policy->Section('How long we keep it',
    'We keep your information for as long as you have an account. When you ask us to delete it, we do so within 30 days.'
      . ' Technical logs, such as which pages were requested, are kept for a limited time to keep the service secure.');

  $Policy->Section('Your rights',
    $CompanyCountry === 'South Africa' ? 'Under South Africa\'s Protection of Personal Information Act (POPIA) you can:' : 'You can:',
    [
      'ask us what information we hold about you',
      PolicyPage::Text('correct it: most of it comes from your sign-in service and is updated each time you sign in, and once you have access you can change the rest in your profile'),
      PolicyPage::Text('have it deleted, see the ', PolicyPage::Link('Data Deletion Instructions', "$Framework/datadeletion.php")),
      'object to how we use it',
    ],
    $CompanyCountry === 'South Africa'
      ? PolicyPage::Text('To use any of these rights, email us at ', clone $Email, ', also if you haven\'t been given access yet. If you are not happy with our answer, you can complain to the ',
        PolicyPage::External('Information Regulator', 'https://inforegulator.org.za'), '.')
      : PolicyPage::Text('To use any of these rights, email us at ', clone $Email, ', also if you haven\'t been given access yet.'));

  $Policy->Section('Keeping it safe',
    "Everything you send to $AppName is encrypted. Your sign-in is protected by signed tokens, only people an administrator approved can use more than the public pages,"
      . ' and administrators only see what they need to manage access.');

  $Policy->Section('Changes to this policy',
    'When we change this policy, we update the date at the top. If a change affects you in a meaningful way, we\'ll let you know when you next sign in or by email.');

  $Policy->Section('Contact us',
    PolicyPage::Text('Questions, requests or concerns about your privacy: email ', clone $Email, '. See also our ',
      PolicyPage::Link('Terms of Use', "$Framework/termsofuse.php"), '.'));
