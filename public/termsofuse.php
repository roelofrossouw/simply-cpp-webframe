<?php
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README

  Config::Init(true, true);

  $AppName = Config::AppName();
  $CompanyName = Config::CompanyName();
  $CompanyCountry = Config::CompanyCountry();
  $Email = PolicyPage::Mail(Config::CompanyEmail());
  $Framework = Config::FrameworkURL();

  $Terms = new PolicyPage('Terms of Use', PolicyPage::Updated());

  $Terms->Summary([
    "$AppName is run by $CompanyName, for the people it gives access to.",
    'Use it only for what you were given access for, and keep the account you sign in with secure.',
  ]);

  $Terms->Section('About these terms',
    "These terms apply when you use $AppName, which is run by $CompanyName (\"we\" and \"us\"). By signing in you agree to them."
      . ' If you don\'t agree, please don\'t use the site.');

  $Terms->Section('Who may use it',
    'Anyone can sign in, but what you can do depends on the access an administrator gives you. We may change or remove access at any time,'
      . ' for example when you no longer need it.');

  $Terms->Section('Your account',
    match (true)
    {
      !\Auth\Password::Configured() => 'You sign in with your ' . PolicyPage::Services() . ' account. Please:',
      PolicyPage::HasServices() => 'You sign in with your ' . PolicyPage::Services() . ' account, or with your email address and password. Please:',
      default => 'You sign in with your email address and password. Please:',
    },
    [
      PolicyPage::HasServices() ? 'keep that account secure, ideally with two-step verification turned on'
        : 'keep your password to yourself, and don\'t use it on other sites',
      'don\'t let anyone else use the site as you',
      PolicyPage::Text('tell us straight away at ', clone $Email, ' if you think someone else has used it'),
    ],
    'You are responsible for what is done in the site while you are signed in.');

  $Terms->Section('Using the site responsibly',
    [
      'Use it only for what you were given access for.',
      'Don\'t try to reach parts you weren\'t given access to, or get around its security.',
      'Information you see about other users is confidential. Don\'t share it.',
    ]);

  $Terms->Section('Availability',
    "We work to keep $AppName running well, but it is provided as it is. It may change, or be unavailable now and then, for example during maintenance.");

  $Terms->Section('Ownership',
    "$AppName and its content belong to $CompanyName. You may use them only as these terms allow.");

  $Terms->Section('Liability',
    'As far as the law allows, we are not liable for indirect or consequential losses that come from using the site, or from not being able to use it.'
      . ' Nothing in these terms takes away rights the law gives you that can\'t be excluded.');

  $Terms->Section('Your privacy',
    PolicyPage::Text('How we handle your information is explained in our ', PolicyPage::Link('Privacy Policy', "$Framework/privacypolicy.php"),
      '. To have your data removed, see the ', PolicyPage::Link('Data Deletion Instructions', "$Framework/datadeletion.php"), '.'));

  $Terms->Section('Changes to these terms',
    'When we change these terms, we update the date at the top. If you keep using the site after a change, you accept the new terms.');

  if ($CompanyCountry !== '')
  {
    $Terms->Section('Law',
      "These terms are governed by the laws of $CompanyCountry.");
  }

  $Terms->Section('Contact us',
    PolicyPage::Text('Questions about these terms: email ', clone $Email, '.'));
