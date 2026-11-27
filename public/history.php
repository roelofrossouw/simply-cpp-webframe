<?php
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README

  use BS\{Badge, Button, Color, Grid, Icon, Input, ModalDialog, ScreenSize, Select, Toast};

  // The change history popup a screen's BS\History::Glyph() opens (since 1.49.0): its tables' changes, newest first,
  // with a time range, a search and the table; a change's values in full (?id=); Undo for those who may edit the screen.
  // What it may show comes only from the glyph's signed token (?t=), never from the URL itself.
  $Scope = History::Scope((string)QueryString::Get('t'));
  Config::Init(Access: $Scope['page'] ?? Config::ANY_USER, Shown: \BS\Dismiss::Modal);
  $Here = Config::FrameworkURL('history.php');
  $Title = $Scope === null || $Scope['title'] === '' ? 'History' : 'History of ' . $Scope['title'];
  Page::AddContent($Dialog = new ModalDialog($Title, ScreenSize::ExtraLarge));
  if ($Scope === null)
  {
    $Dialog->AddChild(new \BS\AlertWarning('No history', 'This link has expired: load the page again and open its history from there.'));
    return;
  }
  $CanUndo = \Auth\Base::CanEdit($Scope['page']);
  $Token = (string)QueryString::Get('t');
  $Link = fn(array $More = []) => $Here . '?' . http_build_query(['t' => $Token] + $More);

  // Who and from where, short: the user, else the program (the application_name)
  $Who = fn(array $Change) => $Change['changed_by'] ?? ($Change['source'] ?? '');
  $When = fn(string $At) => (new DateTimeImmutable($At))->setTimezone(new DateTimeZone(date_default_timezone_get()))->format('Y-m-d H:i:s');
  $Action = fn(array $Change) => match ($Change['action'])
  {
    'insert' => new Badge('Added', Color::Success),
    'delete' => new Badge('Deleted', Color::Danger),
    default => new Badge('Changed', Color::Secondary),
  };
  // Undo: its answer as a toast, then the list (or the change) as it is now
  $Notice = null;
  if (QueryString::Get('action') === 'undo' && ($UndoID = filter_var(QueryString::Get('undo'), FILTER_VALIDATE_INT)) !== false)
  {
    if (!$CanUndo)
    {
      $Notice = Toast::Notice('Not undone', 'You may look at this history, but not undo its changes.', false);
    }
    else
    {
      try
      {
        $Undone = History::Undo($Scope, $UndoID);
        $Notice = Toast::Notice($Undone['undone'] ? 'Undone' : 'Not undone', $Undone['message'], $Undone['undone'], Icon::ArrowCounterclockwise);
      }
      catch (RuntimeException $Problem)
      {
        $Notice = Toast::Notice('Not undone', $Problem->getMessage(), false);
      }
    }
  }
  if ($Notice !== null)
  {
    $Dialog->AddChild($Notice);
  }
  $UndoButton = function (array $Change, array $Back) use ($CanUndo, $Link): ?Button
  {
    if (!$CanUndo || $Change['undone_by'] !== null)
    {
      return null;
    }
    $Undo = new Button('Undo', Icon::ArrowCounterclockwise, $Link(['action' => 'undo', 'undo' => $Change['id']] + $Back));
    $Undo->Modal();
    $Undo->SkipForm();
    $Undo->Color(Color::Warning);
    $Undo->AddClass('btn-sm');
    $Undo->AddAttribute('aria-label', 'Undo change ' . $Change['id']);
    $Undo->AddAttribute('title', 'Put this change back');
    return $Undo;
  };
  $State = function (array $Change): ?HTML\Element
  {
    if ($Change['undone_by'] !== null)
    {
      return new Badge('Undone by #' . $Change['undone_by'], Color::Info);
    }
    return $Change['undo_of'] !== null ? new Badge('Undoes #' . $Change['undo_of'], Color::Info) : null;
  };

  // One change in full: every column it touched, before and after
  if (($ID = filter_var(QueryString::Get('id'), FILTER_VALIDATE_INT)) !== false)
  {
    $Change = History::Change($Scope, $ID);
    if ($Change === null)
    {
      $Dialog->AddChild(new \BS\AlertWarning('No such change', 'It isn\'t in this history.'));
      return;
    }
    $Dialog->AddChild($Facts = new HTML\Paragraph(''));
    $Facts->AddChild($Action($Change));
    $Facts->AddChild(' ' . $Change['tbl'] . ' ' . History::KeyText($Change['row_key']) . ', ' . $When($Change['at']) . ' by ' . ($Who($Change) ?: 'unknown') . ' ');
    if (($Badge = $State($Change)) !== null)
    {
      $Facts->AddChild($Badge);
    }
    $Dialog->AddChild($Wrapper = new \BS\ResponsiveTable());
    $Table = $Wrapper->Table();
    $Table->Small();
    $Head = $Table->Header()->AddRow();
    foreach (['Column', 'Before', 'After'] as $Caption)
    {
      $Head->AddHeadingCell($Caption);
    }
    foreach (History::Columns($Change) as $Column => [$Before, $After])
    {
      $Row = $Table->Body()->AddRow();
      $Row->AddCell($Column);
      foreach ([$Before, $After] as $Value)
      {
        $Row->AddCell($Pre = new HTML\Preformatted(is_array($Value) ? json_encode($Value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
          : History::ValueText($Value)));
        $Pre->AddClasses(['mb-0', 'small', 'text-wrap', 'text-break']);
      }
    }
    if (($Undo = $UndoButton($Change, ['id' => $ID])) !== null)
    {
      $Dialog->AddButton($Undo);
    }
    return;
  }

  // The list, with what to look for
  $Days = (string)(Post::Get('days') ?? QueryString::Get('days') ?? '7');
  $Days = array_key_exists($Days, History::RANGES) ? $Days : '7';
  $Search = trim((string)(Post::Get('search') ?? QueryString::Get('search') ?? ''));
  $Table = (string)(Post::Get('table') ?? QueryString::Get('table') ?? '');
  $Table = in_array($Table, $Scope['tables'], true) ? $Table : '';

  $Dialog->AddChild($Form = new HTML\Form());
  $Form->AddChild($Fields = new Grid(1));
  $Fields->Columns(count($Scope['tables']) > 1 ? 4 : 3, ScreenSize::Medium);
  $Fields->Col(new Select('days', 'When', History::RANGES, $Days));
  $Fields->Col($SearchInput = new Input('search', 'Search (a value, a key, who)'));
  $SearchInput->Value($Search);
  $SearchInput->InputElement()->AddAttribute('autocomplete', 'off');
  if (count($Scope['tables']) > 1)
  {
    $Fields->Col(new Select('table', 'Table', array_combine($Scope['tables'], $Scope['tables']), $Table === '' ? null : $Table, 'Every table'));
  }
  $Fields->Col($Show = new Button('Show', Icon::Search, $Link()));
  $Show->Modal();
  $Show->Color(Color::Primary);
  $Show->AddClasses(['h-100', 'w-100']);

  $Back = ['days' => $Days, 'search' => $Search, 'table' => $Table];
  $Changes = History::Changes($Scope, (int)$Days, $Search, $Table === '' ? null : $Table);
  if ($Changes === [])
  {
    $Dialog->AddChild($None = new HTML\Paragraph('No changes ' . ($Days === '0' ? 'at all' : ($Days === '1' ? 'today' : 'in ' . History::RANGES[$Days])) . ($Search === '' ? '.' : ' with that text.')));
    $None->AddClass('mt-3');
    return;
  }
  $Dialog->AddChild($Wrapper = new \BS\ResponsiveTable());
  $Wrapper->AddClass('mt-3');
  $List = $Wrapper->Table();
  $List->Small();
  $List->Striped();
  $Head = $List->Header()->AddRow();
  foreach (['When', 'Who', 'Row', 'What', ''] as $Caption)
  {
    $Head->AddHeadingCell($Caption);
  }
  foreach ($Changes as $Change)
  {
    $Row = $List->Body()->AddRow();
    $Row->AddCell($When($Change['at']))->AddClass('text-nowrap');
    $Row->AddCell(History::ValueText($Who($Change), 40));
    $Row->AddCell((count($Scope['tables']) > 1 ? $Change['tbl'] . ' ' : '') . History::KeyText($Change['row_key']));
    $What = $Row->AddCell($Action($Change));
    $Columns = History::Columns($Change);
    // An update shows each column before → after; an insert or a delete its first few values
    $Shown = 0;
    foreach ($Columns as $Column => [$Before, $After])
    {
      if (++$Shown > 6)
      {
        $What->AddChild(new HTML\Div(null, '… ' . (count($Columns) - 6) . ' more'));
        break;
      }
      $What->AddChild(new HTML\Div(null, $Column . ': ' . match ($Change['action'])
      {
        'insert' => History::ValueText($After, 60),
        'delete' => History::ValueText($Before, 60),
        default => History::ValueText($Before, 60) . ' → ' . History::ValueText($After, 60),
      }));
    }
    if (($Badge = $State($Change)) !== null)
    {
      $What->AddChild($Badge);
    }
    $Buttons = $Row->AddCell(null);
    $Buttons->AddClasses(['text-nowrap', 'text-end']);
    $Buttons->AddChild($Open = new Button(null, Icon::Eye, $Link(['id' => $Change['id']])));
    $Open->Push(RefreshOnReturn: true);
    $Open->AddClasses(['btn-sm', 'btn-outline-secondary', 'me-1']);
    $Open->AddAttribute('aria-label', 'Change ' . $Change['id'] . ' in full');
    $Open->AddAttribute('title', 'In full');
    if (($Undo = $UndoButton($Change, $Back)) !== null)
    {
      $Buttons->AddChild($Undo);
    }
  }
  if (count($Changes) >= History::LIMIT)
  {
    $Dialog->AddChild($More = new HTML\Small('The newest ' . History::LIMIT . ' are shown: narrow the time or search to see older ones.'));
    $More->AddClass('d-block');
  }
