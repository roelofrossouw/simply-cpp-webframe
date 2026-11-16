<?php
  require_once __DIR__ . '/_demo.php';

  use BS\
  {Badge, Card, Color, ModalDialog, ResponsiveTable, ScreenSize};

  // Loading screens (Popups → Loading screens): a page that takes 2 seconds, in a popup (?in=popup) or the page, so the
  // loading screen shows long enough to see. The first time it's the card of placeholders; after that the shape of this
  // page as it showed last time.
  $Popup = QueryString::Get('in') === 'popup';
  Config::Init(Access: Config::FrameworkURL(DEMOS), Shown: $Popup ? \BS\Dismiss::Modal : \BS\Dismiss::None);
  sleep(2);

  if ($Popup)
  {
    Page::AddContent($Holder = new ModalDialog('Client details', ScreenSize::Large));
  }
  else
  {
    Page::AddContent($Holder = new Card());
    $Holder->AddHeader('Client details');
  }

  $Holder->AddParagraph('Loaded at ' . date('H:i:s') . '. Open it again: while it loads (2 seconds), this page shows as it is now, '
    . 'its text, fields, buttons and table as placeholders that glow. Only the layout is kept in the browser, nothing it says.');

  // A picture: a block of its size while loading
  $Holder->AddChild($Picture = new \HTML\Image('data:image/svg+xml,' . rawurlencode(
      '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="90"><rect width="160" height="90" rx="8" fill="#0d6efd"/>'
      . '<text x="80" y="52" font-family="sans-serif" font-size="18" fill="white" text-anchor="middle">Picture</text></svg>'), 'A picture'));
  $Picture->AddAttribute('width', 160)->AddAttribute('height', 90)->AddClass('mb-3');

  $Holder->AddChild($Form = new \HTML\Form());
  $Form->AddChild(new \BS\Input('name', 'Name'));
  $Form->AddChild(new \BS\Input('email', 'Email', \HTML\InputType::Email));

  // Left out of the loading screen (data-skeleton="skip"): it shows only once the page is there
  $Holder->AddChild($Skipped = new \HTML\Paragraph('This line has data-skeleton="skip": the loading screen leaves it out.'));
  $Skipped->AddAttribute('data-skeleton', 'skip')->AddClass('text-body-secondary');

  // A table: its columns, and at most 10 rows, while loading
  $Holder->AddChild($Wrapper = new ResponsiveTable(ScreenSize::Medium));
  $Table = $Wrapper->Table();
  $Rows = [];
  foreach (['Motor', 'Household contents', 'Buildings', 'All risks', 'Personal liability', 'Legal assistance', 'Watercraft',
             'Caravan', 'Trailer', 'Business interruption', 'Electronic equipment', 'Money'] as $Number => $Section)
  {
    $Rows[] = ['section' => $Section, 'sum_insured' => 'R ' . number_format(($Number + 1) * 125000, 0, '.', ' '),
               'premium' => 'R ' . number_format(($Number + 3) * 117.5, 2, '.', ' ')];
  }
  $Table->AddRows($Rows, true);

  $Holder->AddChild(new Badge('Active', Color::Success));
  $Holder->AddChild(' ');
  $Holder->AddChild(new Badge('12 sections', Color::Secondary));
