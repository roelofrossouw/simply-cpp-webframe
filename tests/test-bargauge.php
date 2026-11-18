<?php

  // Chart\BarGauge: the cells lit and their colours by threshold, the value's colour and text, the info icon, the
  // meter for screen readers, links in and outside the app
  require __DIR__ . '/check.inc';

  use Chart\{BarGauge, Open};

  function InApp(bool $On): void
  {
    (new ReflectionProperty(Config::class, 'AppPage'))->setValue(null, $On);
  }

  InApp(false);
  $Html = (string)(new BarGauge('db3 <CPU>', 29.0))->Unit('%')->Info('CPU busy, the last 5 minutes');
  Same(20, preg_match_all('/class="sc-bargauge-cell[ "]/', $Html), '20 cells by default');
  Same(6, substr_count($Html, 'sc-bargauge-cell sc-lit'), '29% of 20 cells: 6 lit');
  Same(16, substr_count($Html, 'background:' . BarGauge::GREEN), 'green to 80: 16 cells');
  Same(2, substr_count($Html, 'background:' . BarGauge::ORANGE), 'orange 80 to 90: 2 cells');
  Same(2, substr_count($Html, 'background:' . BarGauge::RED), 'red from 90: 2 cells');
  Check(str_contains($Html, 'opacity:.18'), 'the cells beyond the value are faint');
  Check(str_contains($Html, 'color:' . BarGauge::GREEN . ';font-variant-numeric:tabular-nums">29.0%</div>'), 'the value in its threshold\'s colour');
  Check(str_contains($Html, 'db3 &lt;CPU&gt;'), 'the title, escaped');
  Check(str_contains($Html, 'class="sc-bargauge-info" title="CPU busy, the last 5 minutes"'), 'the info icon with its explanation');
  Check(str_contains($Html, 'role="meter"') && str_contains($Html, 'aria-valuenow="29"') && str_contains($Html, 'aria-valuetext="29.0%"'), 'a meter for screen readers');

  $Hot = (string)(new BarGauge('Disk', 95))->Unit('%', 0);
  Check(str_contains($Hot, 'color:' . BarGauge::RED . ';') && str_contains($Hot, '>95%</div>'), 'above 90: red');
  Same(19, substr_count($Hot, 'sc-bargauge-cell sc-lit'), '95%: 19 of 20 lit');
  $Custom = (string)(new BarGauge('Queue age', 41))->Range(0, 60)->Cells(12)->Thresholds([30 => '#ff9830', 0 => '#56a64b', 50 => '#f2495c'])->Unit(' min', 0);
  Same(12, preg_match_all('/class="sc-bargauge-cell[ "]/', $Custom), 'Cells(12)');
  Same(8, substr_count($Custom, 'sc-bargauge-cell sc-lit'), '41 of 60 over 12 cells: 8 lit');
  Check(str_contains($Custom, 'color:#ff9830;') && str_contains($Custom, '>41 min</div>'), 'a range and thresholds of its own (given in any order)');
  $None = (string)new BarGauge('Nothing', null);
  Check(str_contains($None, '>–</div>') && !str_contains($None, 'sc-lit') && !str_contains($None, 'aria-valuenow'), 'no value: a dash, nothing lit');
  Check(str_contains((string)(new BarGauge('x', 3.14159))->Format(fn(float $V) => round($V, 2) . ' s'), '>3.14 s</div>'), 'Format()');

  // Links
  Check(str_starts_with((string)(new BarGauge('x', 1))->Open('/x.php'), '<a href="/x.php"'), 'outside the app a link around it');
  InApp(true);
  $Linked = (string)(new BarGauge('x', 1))->Open('/x.php', Open::SidePanel);
  Check(str_contains($Linked, 'data-href="/x.php"') && str_contains($Linked, 'oc-link') && str_contains($Linked, 'role="link"'), 'in the app: data-href for app.js');
  $Js = file_get_contents(__DIR__ . '/../public/js/app.js');
  Check(str_contains($Js, '.sc-bargauge[data-href]'), 'app.js opens a gauge\'s page');

  // On a dashboard it stands on its own, like a Stat
  $_SERVER['SCRIPT_NAME'] = '/d.php';
  $_GET = [];
  $Dashboard = new Chart\Dashboard('D');
  $Dashboard->Add(new BarGauge('db3 CPU', 29), 4);
  Check(str_contains((string)$Dashboard, 'class="col-6 col-md-4 col-xl-4"><div class="sc-bargauge"'), 'a gauge stands on its own on a dashboard');
