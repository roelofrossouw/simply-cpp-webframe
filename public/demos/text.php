<?php
  require_once __DIR__ . '/_demo.php';

  use BS\
  {Card, Color, FontSize, FontWeight, Grid, Icon, PadSize, PlaceHolder, ScreenSize, Side};
  use HTML\
  {Bold, Header, Paragraph, Small, Strong, UnorderedList};

  Config::Init(Access: Config::FrameworkURL(DEMOS));

  Page::AddContent($Grid = new Grid(1));
  $Grid->Columns(2, ScreenSize::Large);

  $Grid->AddChild($Card = new Card());
  $Card->AddHeader('Headings');
  for ($Level = 1; $Level <= 6; $Level++)
  {
    $Card->AddChild(new Header($Level, "Header({$Level}, ...)"));
  }
  $Card->AddParagraph('A paragraph sized like a heading: Format->HeadingLevel(4)')->Format->HeadingLevel(4);

  $Grid->AddChild($Card = new Card());
  $Card->AddHeader('Paragraphs');
  $Card->AddParagraph('A lead paragraph stands out from regular paragraphs: Paragraph->Lead()')->Lead();
  $Paragraph = $Card->AddParagraph('Inline elements:');
  $Paragraph->AddChild(new Bold('Bold,'));
  $Paragraph->AddChild(new Strong('Strong'));
  $Paragraph->AddChild('and');
  $Paragraph->AddChild(new Small('Small'));
  $Paragraph->AddChild('text, and an icon');
  $Paragraph->AddChild(Icon::Stars->Element());
  $Paragraph = $Card->AddParagraph('Paragraph->AddLine() adds a line break.');
  $Paragraph->AddLine('This is the second line.');
  $Card->AddParagraph('Links load pages in the app:')->AddAnchor('reload this page', Server::Page());
  $Card->AddChild($List = new UnorderedList());
  $List->AddItem('UnorderedList->AddItem()');
  $List->AddItem('Text is escaped, so <b>this</b> shows as typed');
  $Card->AddParagraph('PlaceHolder, while content loads:');
  $Card->AddChild(new PlaceHolder([6, 4, 8, 3]));

  $Grid->AddChild($Card = new Card());
  $Card->AddHeader('Font size and weight');
  foreach (FontSize::cases() as $Size)
  {
    $Card->AddParagraph("FontSize::{$Size->name}")->Format->Font->Size($Size);
  }
  foreach (FontWeight::cases() as $Weight)
  {
    $Card->AddParagraph("FontWeight::{$Weight->name}")->Format->Font->Weight($Weight);
  }

  $Grid->AddChild($Card = new Card());
  $Card->AddHeader('Colours');
  $Card->AddChild($Colours = new Grid(2));
  $Colours->Columns(3, ScreenSize::Medium);
  // Only the colours Bootstrap has as text colours (no subtle or transparent ones)
  foreach (array_filter(Color::cases(), fn($Color) => !$Color->IsSubtle() && $Color !== Color::Transparent) as $Color)
  {
    $Text = $Colours->Col("Format->Color(Color::{$Color->name})");
    $Text->Format->Color($Color);
    $Text->Format->Padding(Side::All, PadSize::Pad1);
    if (in_array($Color, [Color::Light, Color::LightEmphasis, Color::White, Color::White50], true))
    {
      $Text->Format->Background(Color::Dark);
    }
  }
  $Card->AddChild(new Header(5, 'Backgrounds'));
  $Card->AddChild($Backgrounds = new Grid(2));
  $Backgrounds->Columns(4, ScreenSize::Medium);
  foreach ([Color::Primary, Color::Secondary, Color::Success, Color::Danger, Color::Warning, Color::Info, Color::Light, Color::Dark, Color::BodySecondary, Color::BodyTertiary] as $Color)
  {
    $Box = $Backgrounds->Col("Background(Color::{$Color->name})");
    $Box->Format->Background($Color);
    $Box->Format->Padding(Side::All, PadSize::Pad2);
    if (in_array($Color, [Color::Primary, Color::Secondary, Color::Success, Color::Danger, Color::Dark], true))
    {
      $Box->Format->Color(Color::White);
    }
  }
