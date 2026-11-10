<?php
  require_once __DIR__ . '/_demo.php';

  use BS\
  {Card, Color, Icon, Menu};
  use HTML\Text;

  Config::Init(Access: Config::FrameworkURL(DEMOS));

  $Fragment = fn(string $Item): string => Demo('fragment.php?item=') . urlencode($Item);

  Page::AddContent($Card = new Card());
  $Card->AddHeader('Menu with its own target');
  $Card->AddParagraph('AddTarget() gives the menu a target below it, so its items load there instead of replacing the page. LoadActive() loads the active item straight away. Items can have dropdowns, open in the popup or side panel, or show only their icon.');
  $Card->AddChild($Menu = new Menu(new Text('Page menu')));
  $Menu->Color(Color::Primary);
  $Menu->AddTarget();
  $Menu->LoadActive();
  $Menu->AddItem(Icon::House, 'Overview', $Fragment('Overview'))->Active();
  $Menu->AddItem(Icon::ListUl, 'Details', $Fragment('Details'));
  $More = $Menu->AddItem(Icon::Collection, 'More');
  $More->AddItem(Icon::Star, 'First sub item', $Fragment('First sub item'));
  $More->AddItem(Icon::Stars, 'Second sub item', $Fragment('Second sub item'));
  $Menu->AddItem(Icon::WindowStack, 'Popup', Demo('modal.php'))->Modal();
  $Menu->AddItem(Icon::Gear, 'Side panel', Demo('offcanvas.php'))->OffCanvas()->IconOnly();

  Page::AddContent($Card = new Card());
  $Card->AddHeader('Status bar');
  $Card->AddParagraph('Small() and RightAlign() make a compact bar, like the one at the bottom of the app. Modal() on the menu opens all its items in the popup.');
  $Card->AddChild($Menu = new Menu(new Text('Status bar')));
  $Menu->Small();
  $Menu->RightAlign();
  $Menu->Modal();
  $Menu->AddItem(Icon::WindowStack, 'Small popup', Demo('modal.php?size=Small'));
  $Menu->AddItem(Icon::WindowStack, 'Large popup', Demo('modal.php?size=Large'));

  Page::AddContent($Card = new Card());
  $Card->AddHeader('Colours');
  $Card->AddParagraph('Color() sets the bar\'s background, dark colours switch to light text. Without it, menus use the theme\'s background.');
  foreach ([null, Color::Dark, Color::Success, Color::Danger, Color::Warning, Color::Light] as $Color)
  {
    $Card->AddChild($Menu = new Menu(new Text($Color ? "Color::{$Color->name}" : 'Default')));
    $Menu->Small();
    if ($Color)
    {
      $Menu->Color($Color);
    }
    $Menu->AddItem(Icon::House, 'Item');
    $Menu->AddItem(Icon::Gear, 'Settings')->IconOnly();
  }

  Page::AddContent($Card = new Card());
  $Card->AddHeader('Nav overflow');
  $Card->AddParagraph('Overflow() keeps a menu to one line: items that don\'t fit go into More, last first, and the bar folds behind its toggle button only when even that doesn\'t fit. Keep() keeps an item out of More; an item with a dropdown shows in More as a heading over its items. The main menu does this too (setting menu.overflow).');
  $Card->AddChild($Menu = new Menu(new Text('Overflow')));
  $Menu->Overflow();
  $Menu->AddTarget();
  $Menu->AddItem(Icon::House, 'Home', $Fragment('Home'))->Keep()->Active();
  foreach ([[Icon::People, 'Customers'], [Icon::FileEarmarkText, 'Policies'], [Icon::Cash, 'Payments'], [Icon::Clipboard, 'Claims'],
             [Icon::GraphUp, 'Reports'], [Icon::Envelope, 'Messages'], [Icon::Calendar3, 'Diary']] as [$Icon, $Caption])
  {
    $Item = $Menu->AddItem($Icon, $Caption, $Fragment($Caption));
    if ($Caption === 'Reports')
    {
      $Item->AddItem(Icon::CalendarMonth, 'Monthly', $Fragment('Monthly report'));
      $Item->AddItem(Icon::Calendar, 'Yearly', $Fragment('Yearly report'));
    }
  }
  $Menu->AddItem(Icon::Gear, 'Settings', $Fragment('Settings'))->Keep()->IconOnly();

  Page::AddContent($Card = new Card());
  $Card->AddHeader('The app menus');
  $Card->AddParagraph('The main menu uses FixedTop() and Medium(), the status bar FixedBottom() and Small(). setupMenu() pads the page so fixed menus don\'t cover it, and collapses a menu into the toggler when its items don\'t fit (with Overflow(), only once More doesn\'t fit either).');
