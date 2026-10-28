<?php

  // The Hello world example's Home renders, with a sign-in provider set up (its button loop) and without
  require __DIR__ . '/check.inc';

  $_SERVER['HTTP_HOST'] = 'www.example.com';
  $_SERVER['HTTP_X_REQUESTED_BY'] = 'sc-webframe'; // As js/app.js loads it
  $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/home.php';
  Config::Load();
  Storage\Store::Use(new class extends Storage\Postgres {
    public function Menu(): array { return []; }
  });

  ob_start();
  include dirname(__DIR__) . '/examples/hello/public/home.php';
  Page::Show();
  $Html = ob_get_clean();
  Check(str_contains($Html, 'Hello world!'), 'Home says Hello world!');
  Check(str_contains($Html, 'Sign in with GitHub'), 'with the providers that are set up');
  Check(!str_contains($Html, 'Something Went Wrong') && !str_contains($Html, 'alert-danger'), 'and no error');
  Check(str_contains($Html, 'me-2'), 'Side::End gives the end margin');
