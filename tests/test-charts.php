<?php

  // php/Chart: the scales, what each chart draws, the links an item opens (in the app and outside it), the escaping,
  // and app.js's and style.css's parts (their behaviour in a browser: public/demos/charts.php)
  require __DIR__ . '/check.inc';

  use Chart\{BarChart, LineChart, Open, PieChart, Scale, Stat};

  function InApp(bool $On): void
  {
    (new ReflectionProperty(Config::class, 'AppPage'))->setValue(null, $On);
  }

  // Scales: rounded steps, 0 kept for bars, one value
  $Scale = Scale::Around(3, 97);
  Same([0.0, 20.0, 40.0, 60.0, 80.0, 100.0], $Scale->Ticks(), 'Scale 3..97: steps of 20 from 0 to 100');
  $Scale = Scale::Around(1200, 1450, 5);
  Check($Scale->Low <= 1200 && $Scale->High >= 1450 && in_array($Scale->Step, [50.0, 100.0], true), 'Scale 1200..1450 keeps to the values, steps of 50 or 100');
  Same(0.0, Scale::Around(40, 60, 5, true)->Low, 'Scale from zero starts at 0');
  $Scale = Scale::Around(7, 7);
  Check($Scale->Low < 7 && $Scale->High > 7, 'one value gets an axis around it');
  Check(in_array(0.0, Scale::Around(-0.3, 0.3)->Ticks(), true), 'a tick at zero is exactly 0');
  Same(0.5, Scale::Around(0, 10, 3)->Share(5), 'Share() places a value');

  $Rows = [
    ['month' => 'Jan', 'id' => 1, 'sent' => 100, 'bounced' => 5],
    ['month' => 'Feb', 'id' => 2, 'sent' => 150, 'bounced' => null],
    ['month' => 'Mar & Apr', 'id' => 3, 'sent' => 90, 'bounced' => 9],
  ];

  // Bars, outside the app (an email, a PDF): plain SVG links
  InApp(false);
  $Html = (string)(new BarChart('Mail <sent>'))->FromRows($Rows, 'month', ['sent' => 'Sent', 'bounced' => 'Bounced'])
    ->Open('/mail/month.php?id={id}&what={series}');
  Same(5, substr_count($Html, 'class="sc-bar"'), 'a bar per value: 3 sent, 2 bounced (none for the missing one)');
  Check(str_contains($Html, '<figcaption class="sc-chart-title">Mail &lt;sent&gt;</figcaption>'), 'the title, escaped');
  Check(str_contains($Html, 'Mar &amp; Apr'), 'labels are escaped in the SVG');
  Check(str_contains($Html, '<a href="/mail/month.php?id=2&amp;what=Sent">'), 'outside the app an item is an SVG link, placeholders filled in');
  Check(!str_contains($Html, 'data-href'), 'no data-href outside the app');
  Check(str_contains($Html, 'class="sc-chart-legend"') && substr_count($Html, 'class="sc-legend-item"') === 2, 'a legend item per series');
  Check(preg_match('/<svg [^>]*viewBox="0 0 640 240"/', $Html) === 1, 'drawn at its size (640 x 240 by default)');
  Check(str_contains($Html, 'data-tip="Feb' . "\n" . 'Sent: 150"'), "a bar's tooltip: label, series and value");

  // In the app: data-href for app.js, with where to open it
  InApp(true);
  $Html = (string)(new BarChart())->FromRows($Rows, 'month', ['sent' => 'Sent'])->Open('/m.php?label={label}&v={value}', Open::Popup);
  Check(str_contains($Html, 'data-href="/m.php?label=Mar%20%26%20Apr&amp;v=90"'), 'in the app: data-href, placeholders URL-encoded');
  Check(str_contains($Html, 'class="sc-bar modal-link"'), 'Open::Popup marks items modal-link (as BS\Button::Modal())');
  Check(str_contains($Html, 'role="link"') && str_contains($Html, 'tabindex="0"'), 'linked items take the keyboard and read as links');
  Check(!str_contains($Html, '<a href'), 'no SVG links in the app');
  Check(!str_contains($Html, 'sc-chart-legend'), 'one series: no legend');
  $Html = (string)(new BarChart())->FromRows($Rows, 'month', 'sent');
  Check(!str_contains($Html, 'data-href') && !str_contains($Html, 'role="link"'), 'without Open() items only have tooltips');

  // Stacked: the axis covers the totals
  $Stacked = (string)(new BarChart())->FromRows([['m' => 'a', 'x' => 60, 'y' => 70]], 'm', ['x', 'y'])->Stacked();
  Check(str_contains($Stacked, '>140</text>') || str_contains($Stacked, '>150</text>'), 'stacked bars: the axis reaches the total (130)');

  // Lines: a gap for a missing value, a strip per label to point at
  $Line = new LineChart();
  $Line->AddSeries('Rate', ['00:00' => 1, '01:00' => 2, '02:00' => null, '03:00' => 4, '04:00' => 5]);
  $Line->Open('/hour.php?at={label}', Open::SidePanel);
  $Html = (string)$Line;
  Same(2, substr_count($Html, '<polyline'), 'a missing value splits the line in two');
  Same(5, substr_count($Html, 'class="sc-band oc-link"'), 'a strip per label, opening in the side panel');
  Check(str_contains($Html, 'data-href="/hour.php?at=02%3A00"'), "the strip's link names its label");
  Check(str_contains($Html, "02:00\nRate: –"), 'a missing value reads as – in the tooltip');
  Same(0, substr_count((string)(new LineChart())->Dots(false)->FromRows($Rows, 'month', 'sent'), '<circle'), 'Dots(false): no dots');
  Check(str_contains((string)(new LineChart())->Area()->FromRows($Rows, 'month', 'sent'), '<polygon'), 'Area() fills under the line');
  Check(str_contains((string)(new LineChart())->Format(fn(float $V) => 'R ' . $V)->FromRows($Rows, 'month', 'sent'), 'sent: R 150'), 'Format() writes the values');

  // Pie: slices with shares, links on the legend too
  $Pie = (new PieChart())->FromRows([['d' => 'a.example', 'n' => 3], ['d' => 'b.example', 'n' => 1], ['d' => 'none', 'n' => 0]], 'd', 'n')
    ->Open('/d.php?d={label}', Open::Popup);
  $Html = (string)$Pie;
  Same(2, substr_count($Html, 'class="sc-slice modal-link"'), 'a slice per value above 0');
  Check(str_contains($Html, '75%') && str_contains($Html, '25%'), 'the legend gives the shares');
  Check(str_contains($Html, '<span class="sc-legend-item modal-link" data-href="/d.php?d=a.example"'), "a legend item opens its slice's page");
  Check(str_contains((string)(new PieChart())->FromRows([['d' => 'x', 'n' => 5]], 'd', 'n'), ' A'), 'one slice: a whole circle (two arcs)');

  // Nothing to draw
  Check(str_contains((string)new BarChart(), '>No data</text>'), 'a chart without data says so');

  // Stat
  $Html = (string)(new Stat('Sent today', 12345))->Change(-12, 'since yesterday')->Trend([1, 3, 2])->Open('/sent.php', Open::SidePanel);
  Check(str_contains($Html, '12 345'), 'a number gets its thousands grouped');
  Check(str_contains($Html, 'text-danger') && str_contains($Html, '▼'), 'down is red, with an arrow');
  Check(str_contains((string)(new Stat('Failures', 3))->Change(-1, UpIsGood: false), 'text-success'), 'down is green when less is better');
  Check(str_contains($Html, 'data-href="/sent.php"') && str_contains($Html, 'oc-link') && str_contains($Html, 'sc-stat-trend'), 'a tile opens its page and draws its trend');
  InApp(false);
  Check(str_starts_with((string)(new Stat('x', 1))->Open('/x.php'), '<a href="/x.php"'), 'outside the app a tile is a link');

  // The browser's part
  $Js = file_get_contents(__DIR__ . '/../public/js/app.js');
  Check(str_contains($Js, 'setupCharts();') && str_contains($Js, "'.sc-chart [data-href], .sc-stat[data-href]'"), 'app.js opens chart items and tiles');
  Check(str_contains($Js, "loadPage.call(this, this.getAttribute('data-href'))"), 'chart links load like buttons (modal-link, oc-link)');
  $Css = file_get_contents(__DIR__ . '/../public/css/style.css');
  Check(str_contains($Css, '.sc-chart svg .sc-off') && str_contains($Css, '.sc-chart-tip'), 'style.css hides series and styles the tooltip');
