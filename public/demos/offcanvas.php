<?php
  require_once __DIR__ . '/_demo.php';

  use BS\
  {Button, Color, Grid, Icon, OffCanvas};

  Config::Init(Access: Config::FrameworkURL(DEMOS), Shown: \BS\Dismiss::OffCanvas);

  // A page opened with ->OffCanvas() returns an OffCanvas, app.js shows it in the side panel
  Page::AddContent($Panel = new OffCanvas('Side panel'));

  $Panel->AddChild(new \HTML\Paragraph('Side panels slide in from the left. They suit settings and short lists of actions, like the Settings panel.'));
  $Panel->AddChild($Grid = new Grid(1));

  $Grid->AddChild($Button = new Button('Show a toast', Icon::Bell));
  $Button->Data('message', 'Sent from the side panel.');
  $Button->OnClick("toast.call(this, '" . Demo('toast.php') . "');");

  $Grid->AddChild($Button = new Button('Open a popup instead', Icon::WindowStack, Demo('modal.php')));
  $Button->Modal();
  $Button->Color(Color::Secondary);
