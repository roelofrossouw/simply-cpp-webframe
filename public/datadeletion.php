<?php
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README

  Config::Init(true, true);

  $AppName = Config::AppName();
  $CompanyName = Config::CompanyName();
  $CompanyEmail = Config::CompanyEmail();
  $Email = PolicyPage::Mail($CompanyEmail);
  $Services = PolicyPage::Services();

  $Page = new PolicyPage('Data Deletion Instructions', PolicyPage::Updated());

  $Page->Summary([
    "Email $CompanyEmail from the address you sign in with and ask us to delete your $AppName data.",
    "Tell us which service you sign in with ($Services).",
    'We delete everything within 30 days and confirm by email.',
  ]);

  $Page->Section('What we keep about you',
    "When you sign in to $AppName with $Services, we keep:",
    [
      'the identifier that service gives us for your account',
      'your name and email address, as shared by that service',
      'a profile photo and nickname, if you added them',
      'when you first and last signed in',
      'the access an administrator gave you',
    ],
    'We never receive or keep your password for any of these services.');

  $Page->Section('How to have your data deleted',
    PolicyPage::Text('Send an email to ', clone $Email, ' and:'),
    [
      'send it from the email address you use to sign in, so we know the request comes from you',
      "ask for your $AppName data to be deleted",
      'say which service you sign in with, for example Facebook. If you use more than one, list each of them.',
    ],
    "We delete your account, profile and access within 30 days, and email you when it's done."
      . ' If you sign in again afterwards, a new account is created with only basic access.');

  $Page->Section('Removing the app in your sign-in service',
    "You can also remove $AppName from the service you sign in with, for example in Facebook under Settings, Apps and websites,"
      . " or in GitHub under Settings, Applications. That stops $AppName from signing you in, but doesn't delete the data we already keep,"
      . ' so please also send us the email above.');

  $Page->Section('More about your privacy',
    PolicyPage::Text('Our ', PolicyPage::Link('Privacy Policy', Config::FrameworkURL('privacypolicy.php')),
      ' explains what we collect and why. Questions about your data: contact ' . $CompanyName . ' at ', clone $Email, '.'));
