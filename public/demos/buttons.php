<?php
  require_once __DIR__ . '/_demo.php';

  use BS\
  {Button, Card, CloseButton, Color, Dismiss, DropDownButton, Grid, Icon, ScreenSize, Submit};

  Config::Init(Access: Config::FrameworkURL(DEMOS));

  Page::AddContent($Card = new Card());
  $Card->AddHeader('Colours');
  $Card->AddParagraph('Each button shows a toast in its colour: OnClick("toast.call(this, url)") posts its Data() values.');
  $Card->AddChild($Grid = new Grid(2));
  $Grid->Columns(4, ScreenSize::Medium);
  foreach ([Color::Primary, Color::Secondary, Color::Success, Color::Danger, Color::Warning, Color::Info, Color::Light, Color::Dark] as $Color)
  {
    $Grid->AddChild($Button = new Button($Color->name));
    $Button->Color($Color);
    $Button->Data('color', $Color->value);
    $Button->Data('message', "Button with Color::{$Color->name}");
    $Button->OnClick("toast.call(this, '" . Demo('toast.php') . "');");
  }

  Page::AddContent($Card = new Card());
  $Card->AddHeader('Icons and actions');
  $Card->AddChild($Grid = new Grid(1));
  $Grid->Columns(3, ScreenSize::Medium);
  $Grid->AddChild(new Button('Reload this page', Icon::ArrowRepeat, Server::Page()));
  $Grid->AddChild($Button = new Button('Open in a popup', Icon::WindowStack, Demo('modal.php')));
  $Button->Modal();
  $Button->Color(Color::Secondary);
  $Grid->AddChild($Button = new Button('Open in the side panel', Icon::LayoutSidebar, Demo('offcanvas.php')));
  $Button->OffCanvas();
  $Button->Color(Color::Secondary);

  $Grid->AddChild($DropDown = new DropDownButton('DropDownButton', Icon::ListUl));
  $DropDown->Color(Color::Secondary);
  $DropDown->AddItem('First', Icon::Star)->OnClick("toast.call(this, '" . Demo('toast.php') . "');")->Data('message', 'First item clicked');
  $DropDown->AddItem('Second', Icon::Stars)->OnClick("toast.call(this, '" . Demo('toast.php') . "');")->Data('message', 'Second item clicked');

  $Grid->AddChild($Button = new Submit('Submit', Icon::Check2));
  $Grid->AddChild(new CloseButton(Dismiss::None, 'CloseButton with a caption'));

  Page::AddContent($Card = new Card());
  $Card->AddHeader('Close button');
  $Card->AddParagraph('Without a caption, CloseButton is the small X used on popups, side panels and toasts.');
  $Card->AddChild(new CloseButton());
