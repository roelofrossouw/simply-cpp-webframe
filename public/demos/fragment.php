<?php
  require_once __DIR__ . '/_demo.php';

  Config::Init(Access: Config::FrameworkURL(DEMOS));

  // A small page for the menu demos to load into a menu's target
  $Item = QueryString::Get('item') ?? 'This item';
  Page::AddContent($Box = new \BS\AlertBox(\BS\Color::Info, \BS\PadSize::Pad3));
  $Box->Format->Margin(\BS\Side::All, \BS\PadSize::Pad2);
  $Box->AddChild(new \HTML\Header(5, $Item));
  $Box->AddChild(new \HTML\Paragraph("Loaded into the menu's own target at " . date('H:i:s') . '.'));
