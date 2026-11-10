<?php
  require_once __DIR__ . '/_demo.php';

  use BS\
  {Alert, AlertBox, AlertError, AlertInfo, AlertSuccess, AlertWarning, Card, Color, Grid, PlaceHolder, ScreenSize};

  Config::Init(Access: Config::FrameworkURL(DEMOS));

  Page::AddContent($Card = new Card());
  $Card->AddHeader('Alerts with an icon');
  $Card->AddChild($Grid = new Grid(1));
  $Grid->Columns(2, ScreenSize::Large);
  $Grid->AddChild(new AlertInfo('AlertInfo', 'For information the user should notice.'));
  $Grid->AddChild(new AlertSuccess('AlertSuccess', 'Something worked.'));
  $Grid->AddChild(new AlertWarning('AlertWarning', 'Something needs attention, e.g. no access to a page.'));
  $Grid->AddChild(new AlertError('AlertError', 'Something failed.'));

  Page::AddContent($Card = new Card());
  $Card->AddHeader('Alert');
  $Card->AddParagraph('An Alert takes a header and content, both a string or any element. Color() picks the colour.');
  $Card->AddChild($Alert = new Alert('Loading...', 'This is how the start page looks while the menu loads.'));
  $Alert->Color(Color::Light);
  $Alert->AddChild(new PlaceHolder([12, 6, 3, 8, 2]));

  Page::AddContent($Card = new Card());
  $Card->AddHeader('AlertBox');
  $Card->AddParagraph('A plain coloured box without a header, to fill with any content.');
  $Card->AddChild($Grid = new Grid(2));
  $Grid->Columns(4, ScreenSize::Medium);
  foreach ([Color::Primary, Color::Secondary, Color::Success, Color::Danger, Color::Warning, Color::Info, Color::Light, Color::Dark] as $Color)
  {
    $Grid->AddChild($Box = new AlertBox($Color));
    $Box->AddChild("Color::{$Color->name}");
  }
