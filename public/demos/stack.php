<?php
  require_once __DIR__ . '/_demo.php';

  use BS\
  {Button, Color, Icon, ModalDialog, ScreenSize, Toast};

  Config::Init(Access: Config::FrameworkURL(DEMOS), Shown: \BS\Dismiss::Modal);

  // Popups on popups (Button->Push()): each level opens the next on top of itself, and closing one shows the one under
  // it again. ?level= is how deep this one is.
  $Level = max(1, (int)(QueryString::Get('level') ?? 1));

  // The Save in a level: a popup holding only a notice comes back, so it's left empty once the notice moves to the
  // toast corner and goes back to the one under it, which loads again when its Push() asked for that (the default)
  if (QueryString::Get('action') === 'save')
  {
    $Name = trim((string)Post::Get('name'));
    Page::AddContent($Dialog = new ModalDialog("Level $Level"));
    $Dialog->AddChild(Toast::Notice('Saved', $Name === '' ? "Level $Level saved." : "Level $Level saved: $Name."));
    return;
  }

  $Sizes = [ScreenSize::ExtraLarge, ScreenSize::Large, ScreenSize::Medium, ScreenSize::Small];
  Page::AddContent($Dialog = new ModalDialog("Level $Level", $Sizes[min($Level, count($Sizes)) - 1]));

  $Dialog->AddParagraph($Level === 1
    ? 'This popup opens others on top of itself. Type something in the field, then open the next level: when you come back the field still holds it, unless the level above saved and this one was loaded again.'
    : "Opened from level " . ($Level - 1) . ". The arrow before the title, Close, Esc or a click beside the popup go back to it.");
  $Dialog->AddParagraph('Loaded at ' . date('H:i:s') . ' (this time changes when the popup loads again).');

  $Dialog->AddChild($Form = new \HTML\Form());
  $Form->AddChild(new \BS\Input('name', "Something to remember on level $Level"));

  // Save: posts the form and returns only a notice, so this level closes and the one under it shows again
  $Dialog->AddButton($Save = new Button('Save', Icon::Check2, Server::Page(['level' => $Level, 'action' => 'save'])));
  $Save->Modal();

  if ($Level < 4)
  {
    $Next = Server::Page(['level' => $Level + 1]);
    // Refreshed on the way back when the next level saved (the default)
    $Dialog->AddButton($Button = new Button('Add on top', Icon::PlusLg, $Next));
    $Button->Push();
    $Button->Color(Color::Secondary);
    // Kept exactly as it was on the way back, saved or not
    $Dialog->AddButton($Button = new Button('Edit on top (no refresh)', Icon::Pencil, $Next));
    $Button->Push(RefreshOnReturn: false);
    $Button->Color(Color::Secondary);
  }
