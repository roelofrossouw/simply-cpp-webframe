<?php
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README

  use BS\Icon;
  use BS\Menu;

  Config::Init(true);

  $CompanyName = Config::CompanyName();

  // The site's logo (app.logo), else its name
  if (!empty($Logo = Config::Get('app.logo')))
  {
    $Brand = new HTML\Image($Logo, $CompanyName);
    $Brand->AddStyle('height', '2em');
  }
  else
  {
    $Brand = new HTML\Text(Config::AppName());
  }
  $Menu = new Menu($Brand);
  $Menu->FixedTop();
  $Menu->Medium();
  $Menu->AddTarget();
  // Everything is in the menu setup (framework.menu, see sql/schema.sql): Home, the pages, and signing in or out
  MenuLayout::Build($Menu, 'main');
  echo $Menu;

  $Statusbar = new Menu(new \HTML\Text('&copy; ' . date('Y') . ' &ndash; ' . $CompanyName), true);
  $Statusbar->FixedBottom();
  $Statusbar->Small();
  $Statusbar->RightAlign();
  // Your profile (for approved users), the policies and so on, from the menu setup's status bar section
  MenuLayout::Build($Statusbar, 'status');
  echo $Statusbar;
