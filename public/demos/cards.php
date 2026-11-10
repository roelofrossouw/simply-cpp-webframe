<?php
  require_once __DIR__ . '/_demo.php';

  use BS\
  {Button, Card, Color, Grid, Icon, ScreenSize, Side};

  Config::Init(Access: Config::FrameworkURL(DEMOS));

  Page::AddContent($Grid = new Grid(1));
  $Grid->Columns(2, ScreenSize::Medium);
  $Grid->Columns(3, ScreenSize::ExtraLarge);

  $Grid->AddChild($Card = new Card());
  $Card->AddHeader('AddHeader()');
  $Card->AddTitle('AddTitle()');
  $Card->AddParagraph('AddParagraph() and AddChild() go into the card body.');

  $Grid->AddChild($Card = new Card());
  $Card->AddHeaderElement($Button = new Button(null, Icon::ArrowRepeat, Server::Page()));
  $Button->Format->Float(Side::Right);
  $Button->Color(Color::Secondary);
  $Card->AddHeader('With a header button');
  $Card->AddParagraph('AddHeaderElement() puts any element in the header, like this refresh button. Loaded at ' . date('H:i:s') . '.');

  $Grid->AddChild($Card = new Card());
  $Card->AddHeader('With a footer');
  $Card->AddParagraph('AddFooter() adds buttons or text below the body.');
  $Card->AddFooter($Buttons = new Grid(2));
  $Buttons->AddChild($Button = new Button('OK', Icon::Check2));
  $Button->Data('message', 'OK clicked');
  $Button->OnClick("toast.call(this, '" . Demo('toast.php') . "');");
  $Buttons->AddChild($Button = new Button('Cancel', Icon::X));
  $Button->Color(Color::Secondary);

  $Grid->AddChild($Card = new Card());
  $Card->AddImage(new \HTML\Image('/img/logo.svg', 'Logo'));
  $Card->AddTitle('With an image');
  $Card->AddParagraph('AddImage() puts the image at the top of the card.');

  $Grid->AddChild($Card = new Card());
  $Card->Format->Background(Color::Dark);
  $Card->Format->Color(Color::White);
  $Card->AddHeader('Coloured');
  $Card->AddParagraph('Format->Background() and Format->Color() work on cards too.');
