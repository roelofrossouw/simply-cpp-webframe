<?php
  require_once __DIR__ . '/_demo.php';

  use BS\
  {Button, Color, Icon, ModalDialog, ScreenSize};

  Config::Init(Access: Config::FrameworkURL(DEMOS), Shown: \BS\Dismiss::Modal);

  // A page opened with ->Modal() returns a ModalDialog, app.js shows it in the popup
  $Size = ScreenSize::Large;
  foreach (ScreenSize::cases() as $Case)
  {
    if ($Case->name === QueryString::Get('size'))
    {
      $Size = $Case;
    }
  }

  Page::AddContent($Dialog = new ModalDialog("{$Size->name} popup", $Size));

  $Dialog->AddParagraph("This popup was opened with ScreenSize::{$Size->name}. On smaller screens popups fill the whole screen, so they are easy to use on phones.");
  $Dialog->AddHeader('Popup content');
  $Dialog->AddParagraph('A popup is a ModalDialog with a header, a scrollable body and a footer with a close button. It can hold anything a page can.');
  $Dialog->AddChild($List = new \HTML\UnorderedList());
  for ($Line = 1; $Line <= 5; $Line++)
  {
    $List->AddItem("List item {$Line}");
  }

  // A form in the body, posted by the Save button in the footer
  $Dialog->AddHeader('A form in a popup');
  if (!empty($Name = Post::Get('name')))
  {
    $Dialog->AddChild(new \BS\AlertSuccess('Saved', "The footer's Save button posted the form: name = {$Name}"));
  }
  $Dialog->AddChild($Form = new \HTML\Form());
  $Form->AddChild($Input = new \BS\Input('name', 'Name (required)'));
  $Input->Required();
  $Dialog->AddButton($Save = new Button('Save', Icon::Check2, Server::Page(['size' => $Size->name])));
  $Save->Modal();

  $Dialog->AddButton($Button = new Button('Open another popup', Icon::WindowStack, Server::Page(['size' => ScreenSize::Small->name])));
  $Button->Modal();
  $Button->SkipForm();
  $Button->Color(Color::Secondary);
