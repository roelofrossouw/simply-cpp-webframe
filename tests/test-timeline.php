<?php

  // Chart\StateTimeline and Chart\Table: stretches of a state, gaps, states and thresholds, links; table cells by
  // thresholds (text, background, bar), sparklines, sorting, row links, Prometheus->TableRows()'s matching
  require __DIR__ . '/check.inc';

  use Chart\{BarGauge, Open, StateTimeline, Table};

  function InApp(bool $On): void
  {
    (new ReflectionProperty(Config::class, 'AppPage'))->setValue(null, $On);
  }

  date_default_timezone_set('UTC');
  InApp(true);
  $Start = gmmktime(10, 0, 0, 11, 17, 2026);
  $Up = [];
  $Patchy = [];
  for ($At = 0; $At < 12; $At++)
  {
    $Up[$Start + 600 * $At] = $At >= 4 && $At < 6 ? 0 : 1;
    if ($At < 3 || $At > 7)
    {
      $Patchy[$Start + 600 * $At] = 1;
    }
  }
  $Timeline = new StateTimeline('Up');
  $Timeline->AddSeries('api1', $Up)->Labels = ['instance' => 'api1'];
  $Timeline->AddSeries('api2', $Patchy)->Labels = ['instance' => 'api2'];
  $Timeline->OpenSeries('/servers/view.php?name={instance}', Open::SidePanel)->Open('/at.php?name={instance}&t={time}', Open::Popup);
  $Html = (string)$Timeline;
  Same(3, preg_match_all('/class="sc-state[ "][^>]*data-series="0"/', $Html), 'api1: up, down, up again: three stretches');
  Same(2, preg_match_all('/class="sc-state[ "][^>]*data-series="1"/', $Html), 'api2: a gap where it has no samples: two stretches');
  Check(str_contains($Html, 'fill="' . BarGauge::RED . '"') && str_contains($Html, '>Down</text>') && str_contains($Html, '>Up</text>'), 'Down red, Up green, named in the band');
  Check(str_contains(html_entity_decode($Html), '"n":"Down","c":"' . BarGauge::RED . '","v":"Tue 17 Nov 10:40 – 11:00 (20m)"'), "a stretch's hover: state, from, until, how long");
  Check(preg_match('/<text [^>]*data-href="\/servers\/view\.php\?name=api1"[^>]*class="sc-row-name oc-link"/', $Html) === 1, "a row's name opens its series' page");
  Check(str_contains($Html, 'data-href="/at.php?name=api1&amp;t=' . ($Start + 2400) . '"'), "a stretch opens its page with its start time");
  Check(str_contains($Html, '<span class="sc-legend-item"><i style=') && !str_contains($Html, 'sc-legend-item" data-series'), 'the legend lists the states, not the rows');
  Check(preg_match('/viewBox="0 0 640 (\d+)"/', $Html, $Found) && (int)$Found[1] === 80, 'as tall as its rows (two)');
  $Three = (new StateTimeline())->States([0 => ['Down', BarGauge::RED], 1 => ['Up', BarGauge::GREEN], 2 => ['Degraded', BarGauge::ORANGE]]);
  $Three->AddSeries('x', [$Start => 2, $Start + 60 => 2, $Start + 120 => 7]);
  $Html = (string)$Three;
  Check(str_contains($Html, 'fill="' . BarGauge::ORANGE . '"') && str_contains($Html, 'fill="#adb5bd"'), 'states of its own; a value without one in grey');
  $Load = (new StateTimeline())->Thresholds([0 => BarGauge::GREEN, 80 => BarGauge::RED]);
  $Load->AddSeries('cpu', [$Start => 20, $Start + 60 => 95]);
  Check(str_contains((string)$Load, 'fill="' . BarGauge::RED . '"') && !str_contains((string)$Load, 'sc-chart-legend'), 'by thresholds, without a legend');

  // Table
  $Rows = [
    ['client' => 'beta', 'value' => 120.5, 'trend' => [1, 2, 3, null, 2]],
    ['client' => 'alpha', 'value' => 900, 'trend' => [3, 2, 1]],
    ['client' => 'gamma & co', 'value' => 12, 'trend' => null],
  ];
  $Table = new Table('Top clients');
  $Table->Column('client', 'Client');
  $Table->Column('value', 'Requests/min')->Thresholds([0 => BarGauge::GREEN, 100 => BarGauge::ORANGE, 500 => BarGauge::RED])->Show('bar');
  $Table->Column('trend', 'Last 6 hours')->Sparkline();
  $Table->Rows($Rows)->Sort('value')->Open('/clients/view.php?name={client}', Open::SidePanel);
  $Html = (string)$Table;
  Check(strpos($Html, 'alpha') < strpos($Html, 'beta') && strpos($Html, 'beta') < strpos($Html, 'gamma'), 'sorted by value, the highest first');
  Check(str_contains($Html, '<tr data-href="/clients/view.php?name=gamma%20%26%20co" role="link" tabindex="0" class="oc-link"'), 'a row opens its page');
  Check(str_contains($Html, 'width:100%"></span></span><span style="color:' . BarGauge::RED . ';font-weight:600">900</span>'), 'the highest fills its bar, in its colour');
  Check(str_contains($Html, '>120.5</span>') && str_contains($Html, 'color:' . BarGauge::ORANGE), 'orange from 100');
  Same(2, substr_count($Html, '<svg'), 'a sparkline per row with points');
  Check(str_contains($Html, 'gamma &amp; co'), 'text escaped');
  $Background = (string)(new Table())->Rows([['v' => 95]]);
  Check(str_contains($Background, '<th') && str_contains($Background, '>95</td>'), 'without columns: every key a column');
  $Tinted = new Table();
  $Tinted->Column('v')->Thresholds([0 => '#56a64b', 90 => '#f2495c'])->Show('background')->Unit('%', 1);
  Check(str_contains((string)$Tinted->Rows([['v' => 95]]), 'background:#f2495c2e;font-weight:600">95.0%</td>'), 'a tinted background, with a unit');
  InApp(false);
  Check(str_contains((string)(new Table())->Rows([['a' => 'x']])->Open('/x.php?a={a}'), '<a href="/x.php?a=x">x</a>'), 'outside the app the first cell is the link');

  $Js = file_get_contents(__DIR__ . '/../public/js/app.js');
  Check(str_contains($Js, '.sc-table tr[data-href]'), 'app.js opens a table row\'s page');
