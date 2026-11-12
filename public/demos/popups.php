<?php
  require_once __DIR__ . '/_demo.php';

  use BS\
  {Button, Card, Color, Grid, Icon, ScreenSize};

  Config::Init(Access: Config::FrameworkURL(DEMOS));

  Page::AddContent($Card = new Card());
  $Card->AddHeader('Popups');
  $Card->AddParagraph('Button->Modal() (or MenuItem->Modal()) loads the page into the popup. The page returns a ModalDialog with the size to show.');
  $Card->AddChild($Grid = new Grid(2));
  $Grid->Columns(4, ScreenSize::Medium);
  foreach ([ScreenSize::Small, ScreenSize::Medium, ScreenSize::Large, ScreenSize::ExtraLarge] as $Size)
  {
    $Grid->AddChild($Button = new Button($Size->name, Icon::WindowStack, Demo('modal.php?size=') . $Size->name));
    $Button->Modal();
  }

  Page::AddContent($Card = new Card());
  $Card->AddHeader('Popups on popups');
  $Card->AddParagraph('Button->Push() in a popup opens its page in a popup on top, e.g. an Add or Edit from a list. The popup under it waits as it was (its form, its tab) and shows again when the new one closes; the new one gets a back arrow. Push(RefreshOnReturn: false) keeps the one under it as it was even after a save; by default it loads again when the new one saved something (showed a Toast::Notice).');
  $Card->AddChild($Button = new Button('Open level 1', Icon::Stack, Demo('stack.php')));
  $Button->Modal();

  Page::AddContent($Card = new Card());
  $Card->AddHeader('Side panel');
  $Card->AddParagraph('Button->OffCanvas() (or MenuItem->OffCanvas()) loads the page into the side panel. The page returns an OffCanvas.');
  $Card->AddChild($Button = new Button('Open the side panel', Icon::LayoutSidebar, Demo('offcanvas.php')));
  $Button->OffCanvas();

  Page::AddContent($Card = new Card());
  $Card->AddHeader('Toasts');
  $Card->AddParagraph('toast.call(this, url) posts the element\'s Data() values and shows the BS\Toast the page returns, without replacing the page. Used for small updates, like granting access on the Users page.');
  $Card->AddChild($Grid = new Grid(2));
  $Grid->Columns(4, ScreenSize::Medium);
  foreach ([Color::Light, Color::Success, Color::Warning, Color::Danger] as $Color)
  {
    $Grid->AddChild($Button = new Button($Color->name, Icon::Bell));
    $Button->Color($Color == Color::Light ? Color::Secondary : $Color);
    $Button->Data('color', $Color->value);
    $Button->Data('message', "A toast with Color::{$Color->name}.");
    $Button->OnClick("toast.call(this, '" . Demo('toast.php') . "');");
  }

  Page::AddContent($Card = new Card());
  $Card->AddHeader('Built in popups');
  $Card->AddParagraph('The app also shows popups itself: the cookie notice on a first visit, and a list of invalid inputs when a form is posted (see Forms).');
  $Card->AddChild($Button = new Button('Show the cookie notice again', Icon::ChatSquareText));
  $Button->Color(Color::Secondary);
  $Button->OnClick("localStorage.removeItem('cookies-disclaimed'); $('#cookie-disclaimer').remove(); checkCookieDisclaimer();");
