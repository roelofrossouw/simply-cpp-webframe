<?php

  // The settings: where the file is, paths into its sections, and the database connection string built from them
  require __DIR__ . '/check.inc';

  Config::Load();
  Same(__DIR__ . '/config.json', Config::File(), 'the WEBFRAME_CONFIG constant names the file');
  Same('Test site', Config::AppName(), 'app.name');
  Same('Example Ltd', Config::CompanyName(), 'app.company');
  Same('', Config::CompanyCountry(), 'a missing app.country is empty');
  Same('test-id', Config::Get('auth.github.client_id'), 'a path into a section');
  Same(null, Config::Get('auth.google.client_id'), 'a missing section');
  Same('fallback', Config::Get('app.nothing', 'fallback'), 'the default for a missing setting');
  Check(Config::Has('auth.github') && !Config::Has('auth.google'), 'Has() by section');
  Same('Africa/Johannesburg', date_default_timezone_get(), 'app.timezone sets the timezone');
  Same('/framework', Config::FrameworkURL(), 'the framework URL defaults to /framework');
  Same('/framework/login.php', Config::FrameworkURL('login.php'), 'a framework page');
  Check(is_file(Config::FrameworkFolder() . '/js/app.js'), 'the framework folder holds js/app.js');
  Same('/home.php', Config::Home(), 'Home defaults to /home.php');

  // Only the providers with a section are on
  Same(['github'], array_keys(Auth\Base::Providers()), 'only GitHub is set up');
  Same('GitHub', PolicyPage::Services(), 'the policies name the services that are set up');

  // Each part quoted, quotes and backslashes escaped
  Same("host='db.example.com' dbname='test' user='test' password='it\\'s \\\\secret'", DB::ConnectionString(), 'the connection string');
  Config::Set('database', ['connection' => 'service=test']);
  Same('service=test', DB::ConnectionString(), 'a connection string as given');

  Config::Set('app.framework_url', '/fw/');
  Same('/fw/css/style.css', Config::FrameworkURL('css/style.css'), 'another framework URL, its slash dropped');
