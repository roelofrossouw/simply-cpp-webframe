<?php
  require_once __DIR__ . '/_demo.php';

  // Administer → Demos: what the package's classes look like and do, a page per subject in a menu of its own (which
  // shows nav overflow at work: make the window narrower)
  Config::Init(Access: Config::FrameworkURL(DEMOS));

  use BS\{Icon, Menu};

  $Menu = new Menu(new HTML\Text('Demos'));
  $Menu->Overflow();
  $Menu->AddTarget();
  $Menu->LoadActive();
  $Pages = [
    [Icon::Fonts, 'Text', 'text.php'],
    [Icon::LayoutWtf, 'Layout', 'layout.php'],
    [Icon::UiChecks, 'Forms', 'forms.php'],
    [Icon::HandIndex, 'Buttons', 'buttons.php'],
    [Icon::CardText, 'Cards', 'cards.php'],
    [Icon::ExclamationCircle, 'Alerts', 'alerts.php'],
    [Icon::Grid3x3Gap, 'Components', 'components.php'],
    [Icon::Table, 'Tables', 'tables.php'],
    [Icon::MenuButtonWide, 'Menus', 'menus.php'],
    [Icon::WindowStack, 'Popups', 'popups.php'],
    [Icon::EmojiSmile, 'Icons', 'icons.php'],
    [Icon::Bug, 'Errors', 'errors.php'],
  ];
  foreach ($Pages as $Number => [$Icon, $Caption, $Page])
  {
    $Item = $Menu->AddItem($Icon, $Caption, Demo($Page));
    if ($Number === 0)
    {
      $Item->Keep()->Active();
    }
  }
  Page::AddContent($Menu);
