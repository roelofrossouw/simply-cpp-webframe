<?php
  require_once __DIR__ . '/_demo.php';

  use BS\{Accordion, ActionList, Align, AspectRatio, Badge, BlockQuote, Breadcrumb, Button, ButtonGroup, Card, Checkbox,
    Collapse, Color, Figure, FluidContainer, Grid, Icon, IconLink, Justify, ListGroup, PadSize, Pagination, Placement,
    Popover, Progress, Radio, Range, Ratio, Rounded, ScreenSize, Select, Shadow, Side, Size, Spinner, Stack, Tabs,
    TextArea, Tooltip, VerticalRule};

  /**
   * Demo of the Bootstrap components added to BS: one card per component, with the code to look for
   */

  Config::Init(Access: Config::FrameworkURL(DEMOS));

  Page::AddContent($Page = new FluidContainer());
  $Page->Format->Padding(Side::All, PadSize::Pad3);
  $Page->AddChild($Grid = new Grid(1));
  $Grid->Columns(2, ScreenSize::Large);

  $Demo = function (string $Title, string $Code) use ($Grid): Card
  {
    $Grid->Col($Card = new Card(PadSize::None));
    $Card->Format->FullHeight(PadSize::None);
    $Card->AddHeader($Title, 5);
    $Card->AddChild($Note = new HTML\Paragraph($Code));
    $Note->Format->Monospace()->Color(Color::BodySecondary);
    $Note->Format->Font->Size(BS\FontSize::Size6);
    return $Card;
  };

  // Navigation
  $Card = $Demo('Breadcrumb', 'new Breadcrumb(); ->AddItem($Caption, $URL)');
  $Card->AddChild($Crumbs = new Breadcrumb());
  $Crumbs->AddItem('Home', '/home.php');
  $Crumbs->AddItem('Demos', Demo('text.php'));
  $Crumbs->AddItem('Components');

  $Card = $Demo('Tabs and pills', 'new Tabs($Pills); ->AddTab($Caption, $Content)');
  $Card->AddChild($Tabs = new Tabs());
  $Tabs->AddTab('Overview', 'Tabs switch panes without loading anything.', Icon: Icon::House);
  $Tabs->AddTab('Details', 'Each pane can hold any content.');
  $Card->AddChild($Pills = (new Tabs(true))->Fill());
  $Pills->AddTab('Pills', 'The same, styled as pills and filling the width.');
  $Pills->AddTab('Fill', 'Fill() spreads them out.');

  $Card = $Demo('Tabs that overflow', '(new Tabs())->Overflow(); ->AddTab($Caption, $Content, Keep: true)');
  $Card->AddParagraph('Tabs that don\'t fit go into More, last first, and come back as room comes: make the window narrower. Overview is kept (Keep: true); a tab chosen in More opens as usual.');
  $Card->AddChild($Many = (new Tabs())->Overflow());
  foreach (['Overview', 'Details', 'History', 'Activity', 'Comments', 'Attachments', 'Related', 'Settings'] as $Number => $Caption)
  {
    $Many->AddTab($Caption, "The $Caption pane.", Keep: $Number === 0);
  }

  $Card = $Demo('Pagination', "new Pagination(\$Page, \$Pages, '...?page={page}')");
  $Page = max(1, (int)(QueryString::Get('page') ?? 6));
  $Card->AddChild(new Pagination($Page, 12, Demo('components.php?page={page}')));
  $Card->AddChild(new Pagination(2, 3, Demo('components.php?page={page}'), Size: Size::Small));

  $Card = $Demo('Accordion', 'new Accordion(); ->AddItem($Title, $Content, $Open)');
  $Card->AddChild($Accordion = new Accordion());
  $Accordion->AddItem('What is an accordion?', 'Panels that open one at a time.', true);
  $Accordion->AddItem('Can more stay open?', 'Yes: call AlwaysOpen().');
  $Accordion->AddItem('Without borders?', 'new Accordion(Flush: true), e.g. inside a card.');

  $Card = $Demo('Collapse', '$Details = new Collapse($Content); $Details->Toggle($Button)');
  $Card->AddChild($Toggle = new Button('Show details', Icon::ChevronDown));
  $Toggle->Color(Color::Secondary);
  $Card->AddChild($Details = new Collapse('Hidden until the button is clicked.'));
  $Details->Format->Margin(Side::Top, PadSize::Pad2);
  $Details->Toggle($Toggle);

  // Content
  $Card = $Demo('Badges', "new Badge('New', Color::Success); ->Pill()");
  $Card->AddChild((new HTML\Header(4, 'Containers '))->AddChild(new Badge('12', Color::Primary))->Parent());
  foreach ([Color::Primary, Color::Success, Color::Danger, Color::Warning, Color::Info, Color::Dark] as $Color)
  {
    $Card->AddChild((new Badge($Color->name, $Color))->Pill());
  }

  $Card = $Demo('List groups', 'new ListGroup(); ->AddItem($Content, $Color, $Active) / new ActionList(); ->AddLink(...)');
  $Card->AddChild($Stack = new Stack());
  $Stack->AddChild($List = new ListGroup(Numbered: true));
  $List->AddItem('A numbered list');
  $List->AddItem('An active item', null, true);
  $List->AddItem('A coloured item', Color::Warning);
  $Stack->AddChild($Actions = new ActionList());
  $Actions->AddLink('Tables', Demo('tables.php'), Icon::Boxes);
  $Actions->AddButton('A button row', Icon::Lightning)->OnClick("toast.call(this, '" . Demo('toast.php') . "');");

  $Card = $Demo('Progress and spinners', 'new Progress($Value, $Max); new Spinner($Color)');
  $Card->AddChild($Stack = new Stack());
  $Stack->AddChild(new Progress(40, ShowValue: true));
  $Stack->AddChild((new Progress(70))->Color(Color::Success)->Striped(true));
  $Stack->AddChild($Row = new Stack(true));
  $Row->AddChild(new Spinner(Color::Primary));
  $Row->AddChild(new Spinner(Color::Success, Grow: true));
  $Row->AddChild(new VerticalRule());
  $Row->AddChild(new Spinner(Color::Secondary, Small: true));

  $Card = $Demo('Tooltips and popovers', "Tooltip::Add(\$Element, \$Text); Popover::Add(\$Element, \$Title, \$Text)");
  $Card->AddChild($Row = new Stack(true));
  $Row->AddChild(Tooltip::Add(new Button('Hover me'), 'A tooltip', Placement::Bottom));
  $Row->AddChild($Info = new Button('Click me', Icon::InfoCircle));
  $Info->Color(Color::Info);
  Popover::Add($Info, 'A popover', 'Popovers hold a little more text. Click anywhere else to close it.', Placement::End);

  $Card = $Demo('Button group', "new ButtonGroup(\$Label); ->Vertical()");
  $Card->AddChild($Group = new ButtonGroup('Views'));
  foreach (['Day', 'Week', 'Month'] as $View)
  {
    $Group->AddChild($Button = new Button($View));
    $Button->Color(Color::Secondary);
  }

  $Card = $Demo('Figure, blockquote and icon link', "new Figure(\$Src, \$Alt, \$Caption); new BlockQuote(\$Quote, \$Source); new IconLink(...)");
  $Card->AddChild($Row = new Stack(true, PadSize::Pad4));
  $Row->Format->AlignItems(Align::Start);
  $Row->AddChild($Figure = new Figure('/img/app-icon-1024.png', '1Web MAN icon', 'The app icon'));
  $Figure->Format->Width(120);
  $Row->AddChild($Text = new Stack());
  $Text->AddChild(new BlockQuote('Simple things should be simple.', 'Alan Kay'));
  $Text->AddChild(new IconLink('Bootstrap documentation', Icon::ArrowRight, 'https://getbootstrap.com/docs/5.3/', IconAfter: true, Animate: true));

  $Card = $Demo('Ratio', 'new Ratio($Content, AspectRatio::SixteenByNine)');
  $Box = new HTML\Div(Content: '16 by 9, whatever the width');
  $Box->Format->Background(Color::PrimarySubtle)->Border(Side::All, Color::PrimarySubtle)->Rounded(Rounded::Large);
  $Box->Format->Display(BS\Display::Flex)->AlignItems(Align::Center)->JustifyContent(Justify::Center);
  $Card->AddChild($Ratio = new Ratio($Box, AspectRatio::SixteenByNine));
  $Ratio->Format->Width(BS\Percent::P50);

  // Forms
  $Card = $Demo('Form controls', "new Select(...); new Checkbox(...); new Radio(...); new Range(...); new TextArea(...)");
  $Card->AddChild($Form = new HTML\Form());
  $Form->AddChild($Fields = new Stack(Gap: PadSize::Pad3));
  $Fields->AddChild((new Select('size', 'Size', ['s' => 'Small', 'm' => 'Medium', 'l' => 'Large'], Placeholder: 'Choose a size...'))
    ->Required()->Feedback('Please choose a size.')->Help('Required: Send shows a message until one is chosen.'));
  $Fields->AddChild($Checks = new HTML\Div());
  $Checks->AddChild((new Checkbox('updates', 'Email me updates', true))->Inline());
  $Checks->AddChild((new Checkbox('terms', 'I accept the terms'))->Inline()->Required());
  $Fields->AddChild($Radios = new HTML\Div());
  foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'never' => 'Never'] as $Value => $Label)
  {
    $Radios->AddChild((new Radio('frequency', $Label, $Value, $Value == 'weekly'))->Inline());
  }
  $Fields->AddChild(new Range('volume', 'Volume', 0, 10, 1, 7));
  $Fields->AddChild((new TextArea('notes', 'Notes'))->Help('Floating labels need a fixed height, 100 pixels by default.'));
  $Fields->AddChild($Send = new Button('Send', Icon::Send, Demo('toast.php')));
  $Send->Format->AlignSelf(Align::Start);

  // Utilities
  $Card = $Demo('Utilities', '->Format->Border() Rounded() Shadow() Opacity() Position() Place() Gap() JustifyContent() ...');
  $Card->AddChild($Row = new Stack(true, PadSize::Pad3));
  $Row->Format->FlexWrap(BS\FlexWrap::Wrap);
  foreach ([Shadow::Small, Shadow::Regular, Shadow::Large] as $Shadow)
  {
    $Row->AddChild($Box = new HTML\Div(Content: 'shadow ' . strtolower($Shadow->name)));
    $Box->Format->Padding(Side::All, PadSize::Pad3)->Rounded()->Shadow($Shadow)->Background(Color::Body);
  }
  $Row->AddChild($Box = new HTML\Div(Content: 'with badge'));
  $Box->Format->Position(BS\Position::Relative)->Padding(Side::All, PadSize::Pad3)->Border(Side::All, Color::Info, 2)->Rounded(Rounded::Large);
  $Box->AddChild($Corner = new Badge('3', Color::Danger));
  $Corner->Pill();
  $Corner->Format->Position(BS\Position::Absolute)->Place(Side::Top, 0)->Place(Side::Left, 100)->TranslateMiddle();
  $Row->AddChild($Faded = new HTML\Div(Content: 'opacity 50'));
  $Faded->Format->Padding(Side::All, PadSize::Pad3)->Opacity(50)->TextBackground(Color::Dark)->Rounded();
