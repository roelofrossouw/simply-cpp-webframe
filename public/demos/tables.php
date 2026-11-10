<?php
  require_once __DIR__ . '/_demo.php';

  use BS\
  {AlertBox, Card, Color, ResponsiveTable, ScreenSize, WideContent};
  use HTML\Table;

  Config::Init(Access: Config::FrameworkURL(DEMOS));

  // From rows such as a query answers (here a fixed list): headings come from the column names, click a heading to sort.
  // A real page puts DB::Sort($SortFields) in its query instead of sorting here.
  $SortFields = ['name', 'container_count', 'max_down'];
  $Rows = [
    ['name' => 'web', 'container_count' => 6, 'max_down' => 1],
    ['name' => 'database', 'container_count' => 3, 'max_down' => 0],
    ['name' => 'mail', 'container_count' => 2, 'max_down' => 1],
    ['name' => 'build', 'container_count' => 4, 'max_down' => 2],
    ['name' => 'listener', 'container_count' => 2, 'max_down' => '-'],
  ];
  $Order = QueryString::Get('_order');
  if (in_array($Order, $SortFields, true))
  {
    usort($Rows, fn($A, $B) => (empty(QueryString::Get('_desc')) ? 1 : -1) * strnatcasecmp((string)$A[$Order], (string)$B[$Order]));
  }

  Page::AddContent($Card = new Card());
  $Card->AddHeader('From query results');
  $Card->AddParagraph('Table->AddRows($Rows, RowHeading: true, SortFields: [...]) builds the headings from the column names. Click a heading to sort: TableHeadingCell::Sort() sets ?_order= and ?_desc=, which DB::Sort() turns into the query\'s order by.');
  if (empty($Rows))
  {
    $Card->AddChild(new AlertBox(Color::Warning))->AddChild('The query gave no rows.');
  }
  else
  {
    $Card->AddChild($Wrapper = new ResponsiveTable(ScreenSize::Large));
    $Table = $Wrapper->Table();
    $Table->Striped();
    $Table->Caption(count($Rows) . ' templates in the design.');
    $Table->AddRows($Rows, true, $SortFields);
  }

  // Built row by row
  Page::AddContent($Card = new Card());
  $Card->AddHeader('Built row by row');
  $Card->AddChild($Wrapper = new ResponsiveTable(ScreenSize::Large));
  $Table = $Wrapper->Table();
  $Table->Caption('Header, row headings, row colours and a footer.');
  $Row = $Table->Header()->AddRow();
  $Row->StickyTop();
  foreach (['Server', 'Containers', 'Running', 'Status'] as $Heading)
  {
    $Row->AddHeadingCell($Heading);
  }
  $Servers = [
    ['hv1', 12, 12, 'Healthy', Color::Success],
    ['hv2', 9, 8, 'One container stopped', Color::Warning],
    ['hv3', 4, 0, 'Not responding', Color::Danger],
    ['hv4', 0, 0, 'New', null],
  ];
  $Total = 0;
  $Running = 0;
  foreach ($Servers as [$Name, $Containers, $Up, $Status, $Color])
  {
    $Row = $Table->Body()->AddRow();
    $Row->AddRowHeadingCell($Name);
    $Row->AddCell((string)$Containers);
    $Row->AddCell((string)$Up);
    $Row->AddCell($Status);
    if ($Color)
    {
      $Row->Color($Color);
    }
    $Total += $Containers;
    $Running += $Up;
  }
  $Row = $Table->Footer()->AddRow();
  $Row->AddRowHeadingCell('Total');
  $Row->AddCell((string)$Total);
  $Row->AddCell((string)$Running);
  $Row->AddCell('');

  // Too wide for phones
  Page::AddContent($Card = new Card());
  $Card->AddHeader('Needs a wider screen');
  $Card->AddParagraph('WideContent hides wide content below a screen size and asks the user to turn their phone. Make the window narrow to see it.');
  $Card->AddChild($Wide = new WideContent(ScreenSize::Medium));
  $Wide->AddChild($Table = new Table());
  $Months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  $Row = $Table->Header()->AddRow();
  $Row->AddHeadingCell('Server');
  foreach ($Months as $Month)
  {
    $Row->AddHeadingCell($Month);
  }
  foreach (['hv1', 'hv2', 'hv3'] as $Index => $Name)
  {
    $Row = $Table->Body()->AddRow();
    $Row->AddRowHeadingCell($Name);
    foreach ($Months as $Number => $Month)
    {
      $Row->AddCell((string)(90 + ($Index * 7 + $Number * 3) % 10) . '%');
    }
  }
