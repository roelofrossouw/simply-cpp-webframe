<?php
  // test-browser.php's page: charts as the server draws them, and the checks run on them in the browser (charts.js)
  use Chart\{BarChart, LineChart};
  $A = (new LineChart('a'))->Area()->Stacked();
  $A->AddSeries('a', [1, 2, 3]); $A->AddSeries('b', [4, 5, 6]); $A->AddSeries('c', [1, 1, 1]);
  $P = (new LineChart('p'))->Area()->Stacked('percent');
  $P->AddSeries('a', [1, 1]); $P->AddSeries('b', [1, 3]);
  $B = (new BarChart('b'))->Stacked();
  $B->AddSeries('a', [1, 2]); $B->AddSeries('b', [3, 4]);
  $T = (new LineChart('t'))->Area()->Stacked()->Legend('table', ['last']);
  $T->AddSeries('a', [1, 2, 3]); $T->AddSeries('b', [4, 5, 6]);
  echo '<!doctype html><html><body><div id="A">' . $A . '</div><div id="P">' . $P . '</div><div id="B">' . $B . '</div><div id="T">' . $T . '</div><pre id="out"></pre>' .
    '<script>' . file_get_contents(__DIR__ . '/charts.js') . '</script></body></html>';
