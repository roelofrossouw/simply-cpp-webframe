<?php

  // Table cells are one line unless asked to wrap: Wrap() on a cell, a column's heading or the table; CellWidth() sets
  // how wide a one-line cell gets. The ellipsis and popover are style.css's and app.js's (checked in a browser).
  require __DIR__ . '/check.inc';

  $Table = new HTML\Table();
  $Headings = $Table->Header()->AddRow();
  $Headings->AddHeadingCell('Name');
  $Headings->AddHeadingCell('Notes')->Wrap();
  $Row = $Table->Body()->AddRow();
  $Row->AddCell('switch1');
  $Row->AddCell('A long note')->Wrap();
  $Html = (string) $Table;
  Check(substr_count($Html, 'class="cell-wrap"') === 2, 'Wrap() marks the heading cell (its column) and the body cell');
  Check(!str_contains($Html, 'table-wrap'), 'the table itself doesn\'t wrap');

  Check(str_contains((string) (new HTML\Table())->Wrap(), 'class="table table-wrap"'), 'Table::Wrap(): every cell wraps');
  Check(str_contains((string) (new HTML\Table())->CellWidth('30rem'), 'style="--sc-cell-max-width:30rem;"'), 'CellWidth() sets the one-line width');
  foreach (['30', 'red', '1rem;color:red', ''] as $Wrong)
  {
    try
    {
      (new HTML\Table())->CellWidth($Wrong);
      $Refused = false;
    }
    catch (InvalidArgumentException)
    {
      $Refused = true;
    }
    Check($Refused, "CellWidth('$Wrong') isn't a length");
  }

  $Css = file_get_contents(__DIR__ . '/../public/css/style.css');
  Check(str_contains($Css, '.table:not(.table-wrap) > tbody > tr > :is(td, th):not(.cell-wrap)') && str_contains($Css, 'text-overflow: ellipsis'),
        'style.css: body cells one line with an ellipsis, unless they or the table wrap');
  $Js = file_get_contents(__DIR__ . '/../public/js/app.js');
  Check(str_contains($Js, 'cutCells(root);') && str_contains($Js, 'cutCells(document.body);'), 'app.js marks cut cells on every load');
