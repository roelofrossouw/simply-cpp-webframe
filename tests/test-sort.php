<?php

  // Sortable headings: the link keeps the page's own query parameters (?part=), sets ?_order= and ?_desc=, turns the order
  // round on the column it's sorted on; and Action() called again replaces the heading's handler instead of adding one
  require __DIR__ . '/check.inc';

  $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/myApp/net.php';

  // A heading's script: where clicking it goes
  function Goes(HTML\Element $Cell): array
  {
    preg_match_all('~window\.location = "((?:[^"\\\\]|\\\\.)*)"~', (string) $Cell, $Found);
    return array_map(fn($Each) => json_decode('"' . $Each . '"'), $Found[1]);
  }

  $_GET = ['part' => 'switchports', 'filter' => 'a b'];
  Check(DB::SortAction('port') === '/myApp/net.php?part=switchports&filter=a+b&_order=port&_desc=1', 'the page\'s own parameters kept, then the order');
  $_GET = ['part' => 'switchports', '_order' => 'port', '_desc' => '1'];
  Check(DB::SortAction('port') === '/myApp/net.php?part=switchports&_order=port&_desc=0', 'the column it\'s sorted on: the order turned round');
  Check(DB::SortAction('speed') === '/myApp/net.php?part=switchports&_order=speed&_desc=1', 'another column: its own order, the old one replaced');
  $_GET = [];
  Check(DB::SortAction('name') === '/myApp/net.php?_order=name&_desc=1', 'a page without parameters');

  $_GET = ['part' => 'addresses', '_order' => 'address', '_desc' => ''];
  $Cell = (new HTML\TableHeadingCell('Address'))->Sort('address');
  Check(Goes($Cell) === ['/myApp/net.php?part=addresses&_order=address&_desc=1'], 'the heading goes there, ?part= kept');
  Check(str_contains((string) $Cell, 'aria-sort="ascending"'), 'and says how it\'s sorted now');

  // Action() again: one handler, the last one (off() before on(), in its own namespace)
  $Cell = (new HTML\TableHeadingCell('Port'))->Sort('port')->Action('/elsewhere.php');
  $Html = (string) $Cell;
  Check(substr_count($Html, '.off("click.action")') === 2 && substr_count($Html, '.on("click.action"') === 2, 'each Action() takes off the one before');
  Check(strrpos($Html, '/elsewhere.php') > strrpos($Html, '_order=port'), 'the last one set is the one left');
  $Trigger = (string) (new HTML\TableHeadingCell('Both'))->Action('/a.php')->Trigger(HTML\Event::Click, 'console.log(1);');
  Check(str_contains($Trigger, ".on('click'") && str_contains($Trigger, '.on("click.action"'), 'Trigger()\'s own handlers stay beside it');
