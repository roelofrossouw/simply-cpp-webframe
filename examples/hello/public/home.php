<?php

  // Home (the menu's home item): open to everyone, and it greets you by name once you've signed in
  require_once 'sc-webframe/autoload.php';

  use Auth\Base;
  use BS\{Button, Card, Container, PadSize, ScreenSize, Side};

  Config::Init(Anonymous: true);

  Page::AddContent($Content = new Container());
  $Content->Format->Padding(Side::All, PadSize::Pad3);
  $Content->AddChild($Card = new Card());
  $Card->Format->Center();
  $Card->Format->MaxWidth(ScreenSize::Medium->Width());
  $Card->AddChild(new HTML\Header(2, 'Hello world!'));

  $Profile = Base::LoggedIn() ? User::Profile((int)Base::ID()) : null;
  if ($Profile !== null)
  {
    $Card->AddParagraph('You are signed in as ' . User::DisplayName($Profile) . ' with ' . User::ProviderTitle($Profile['provider'])
      . ', and you have ' . implode(', ', User::Access((int)Base::ID())) . ' access.');
  }
  else
  {
    $Card->AddParagraph('This page is built with sc-webframe\'s HTML and Bootstrap classes. Sign in to see who you are.');
    foreach (Base::Providers() as $Name => $Class)
    {
      $Card->AddChild($Button = new Button('Sign in with ' . $Class::TITLE, $Class::ICON));
      $Button->Link(Config::FrameworkURL('login.php') . '?provider=' . $Name);
      $Button->Format->Margin(Side::End, PadSize::Pad2);
    }
  }
