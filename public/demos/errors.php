<?php
  require_once __DIR__ . '/_demo.php';

  use BS\
  {AlertInfo, Card, Icon, Menu};
  use HTML\Text;

  Config::Init(Access: Config::FrameworkURL(DEMOS));

  Page::AddContent($Card = new Card());
  $Card->AddHeader('Errors and loading');
  $Card->AddChild(new AlertInfo('How the app handles problems', 'PHP notices and warnings show inline where they happen. A fatal error, or a page that doesn\'t exist, shows "There was a problem loading the content" after a short delay. Slow pages show a placeholder after 100ms. Clicking another item while a page loads cancels it.'));
  $Card->AddChild($Menu = new Menu(new Text('Try')));
  $Menu->Small();
  $Menu->AddTarget();
  $Menu->AddItem(Icon::ShieldExclamation, 'Notice', Demo('notice.php'));
  $Menu->AddItem(Icon::ExclamationCircle, 'Warning', Demo('warning.php'));
  $Menu->AddItem(Icon::ExclamationTriangle, 'Error', Demo('error.php'));
  $Menu->AddItem(Icon::HourglassSplit, 'Slow page', Demo('slow.php'));
  $Menu->AddItem(Icon::XCircle, 'Missing page', Demo('missing.php'));
