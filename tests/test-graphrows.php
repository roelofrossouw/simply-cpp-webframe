<?php

  // The charts in another app (One Web's ResultTable): only sc-webframe/charts.php loaded, beside the app's own global
  // Config (without AppPage()); rows in One Web's graph form; the item attributes its drill-down reads; the browser's own
  // tooltips and inline styles, as there is no sc-webframe app.js or style.css there.
  set_include_path(__DIR__ . '/include' . PATH_SEPARATOR . get_include_path());

  // The other app's own Config, loaded first
  class Config
  {
    public static function Get(string $Key): string
    {
      return 'the app\'s own';
    }
  }

  require_once 'sc-webframe/charts.php';

  $Checks = 0;
  $Failures = 0;
  function Check(bool $Passed, string $What): void
  {
    global $Checks, $Failures;
    $Checks++;
    if (!$Passed)
    {
      $Failures++;
      echo "FAILED: $What\n";
    }
  }

  Check(!class_exists('Page', true) && !class_exists('DB', true) && !class_exists('User', true), 'charts.php loads no global classes of its own');

  // One Web's rows: category1 and _graph_value..._graph_value5, as ResultTable gives sysutil.chart()
  $Rows = [
    ['category1' => 202609, '_graph_value' => 120, '_graph_value2' => 30, 'broker' => 'A'],
    ['category1' => 202610, '_graph_value' => 150, '_graph_value2' => null, 'broker' => 'B'],
    ['category1' => 202611, '_graph_value' => 90, '_graph_value2' => 45, 'broker' => 'C'],
  ];
  $Months = [202609 => 'Sep 2026', 202610 => 'Oct 2026', 202611 => 'Nov 2026'];
  $Chart = Chart\Chart::ForGraphType(2);
  Check($Chart instanceof Chart\BarChart, 'graph type 2: bars');
  Check(Chart\Chart::ForGraphType(1) instanceof Chart\PieChart && Chart\Chart::ForGraphType(3) instanceof Chart\LineChart, 'types 1 and 3: pie and lines');
  Check(Chart\Chart::ForGraphType(7) === null, 'another type: none');
  $Chart->FromGraphRows($Rows, ['Premium', 'Claims'], fn($Category) => $Months[$Category] ?? (string)$Category);
  $Chart->ItemAttributes(['value' => '{category1}', 'data' => 'bar{number}']);
  $Html = (string)$Chart;
  Check(substr_count($Html, 'class="sc-bar"') === 5, 'a bar per value (the missing one left out)');
  Check(str_contains($Html, 'value="202610" data="bar1"') && str_contains($Html, 'value="202611" data="bar2"'),
    "each bar carries its category and series, as One Web's drill-down reads them");
  Check(str_contains($Html, '>Oct 2026</text>'), 'the categories as text');
  Check(str_contains($Html, '<title>Nov 2026' . "\n" . 'Claims: 45</title>'), "the browser's own tooltip");
  Check(!str_contains(preg_replace('/<script\b.*?<\/script>/s', '', $Html), 'data-href') && !str_contains($Html, 'role="link"'), 'no sc-webframe links outside its app');
  Check(str_contains($Html, 'style="max-width:100%;height:auto"'), 'the drawing scales without style.css');
  Check(str_contains($Html, 'display:flex') && str_contains($Html, 'display:inline-block;width:.75em'), 'the legend is laid out without style.css');
  Check(str_contains($Html, '>Premium</span>') && str_contains($Html, '>Claims</span>'), 'the series named');

  Check(str_contains($Html, 'data-series="1" role="button" tabindex="0" aria-pressed="true" style="cursor:pointer">') && !str_contains($Html, 'onclick='),
    'outside the app a legend item is plain: the hover script hides and shows its series');
  Check(str_contains(file_get_contents(__DIR__ . '/../php/Chart/standalone.js'), "const toggle = function (event)"), 'the hover script has the legend toggle');
  // Only the series the rows have; unnamed ones numbered
  $Html = (string)(new Chart\LineChart())->FromGraphRows([['category1' => 1, '_graph_value' => 5], ['category1' => 2, '_graph_value' => 6]]);
  Check(substr_count($Html, '<path d="M') === 1 && !str_contains($Html, 'sc-chart-legend'), 'one series: one line, no legend');
  $Pie = (string)Chart\Chart::ForGraphType(1)->FromGraphRows($Rows, ['Premium'])->ItemAttributes(['value' => '{category1}']);
  Check(substr_count($Pie, 'class="sc-slice"') === 3 && str_contains($Pie, 'value="202609"'), 'a pie: a slice per category, from the first value');
  $Line = (string)Chart\Chart::ForGraphType(3)->FromGraphRows($Rows, ['Premium', 'Claims'])->ItemAttributes(['value' => '{category1}', 'data' => 'bar{number}']);
  preg_match('/<script type="application\/json" class="sc-chart-data">(.*?)<\/script>/s', $Line, $Found);
  $Data = json_decode($Found[1] ?? 'null', true);
  Check(($Data['attrs']['value'] ?? null) === ['202609', '202610', '202611'] && !isset($Data['attrs']['data']) && !str_contains($Line, '{number}'),
    "a line chart's times carry their category for the drill-down (the script puts it on the area), and no unfilled placeholder");
  // Open() still works outside the app: SVG links
  $Html = (string)(new Chart\BarChart())->FromGraphRows($Rows, ['Premium'])->Open('/report.php?month={category1}&broker={broker}');
  Check(str_contains($Html, '<a href="/report.php?month=202609&amp;broker=A">'), 'Open() gives SVG links, with the row\'s columns');

  $Script = file_get_contents(__DIR__ . '/../php/Chart/standalone.js');
  Check(str_contains($Script, 'if (window.scChartHover) return;') && str_contains($Script, "title.remove()"),
    'the script runs once per page, and replaces the browser\'s own tooltip');

  echo "$Checks checks, $Failures failed\n";
  exit($Failures > 0 ? 1 : 0);
