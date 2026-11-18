<?php

  // Chart\Heatmap and LineChart annotations: cells by column and aggregate, shades (log, linear, row, thresholds), empty
  // cells, merged runs, the hover data, fitted names; annotation lines, stretches, markers and links
  require __DIR__ . '/check.inc';

  use Chart\{Heatmap, LineChart};

  function InApp(bool $On): void
  {
    (new ReflectionProperty(Config::class, 'AppPage'))->setValue(null, $On);
  }

  function Data(string $Html): array
  {
    preg_match('/<script type="application\/json" class="sc-chart-data">(.*?)<\/script>/s', $Html, $Found);
    $Data = json_decode($Found[1], true);
    foreach ($Data['f'] as &$Runs)
    {
      $Values = [];
      for ($At = 0; $At < count($Runs); $At += 2)
      {
        array_push($Values, ...array_fill(0, $Runs[$At + 1], $Runs[$At]));
      }
      $Runs = $Values;
    }
    return $Data;
  }

  date_default_timezone_set('UTC');
  InApp(true);
  $Start = gmmktime(10, 0, 0, 11, 17, 2026);
  $Heat = (new Heatmap('Runs'))->Columns(4);
  $Busy = [];
  $Quiet = [];
  for ($At = 0; $At < 8; $At++)
  {
    $Busy[$Start + 60 * $At] = 10 * ($At + 1);
    $Quiet[$Start + 60 * $At] = $At < 4 ? 0 : 1;
  }
  $Heat->AddSeries('busy', $Busy);
  $Heat->AddSeries('a job with a name far too long to fit the space for names', $Quiet);
  $Html = (string)$Heat;
  $Data = Data($Html);
  Same(['30', '70', '110', '150'], $Data['f'][0], 'sums of two points per column');
  Same([null, null, '2', '2'], $Data['f'][1], 'no cell where the sum is 0');
  Same(4, $Data['columns'], 'Columns(4)');
  Check(str_contains($Data['heads'][0], 'Tue 17 Nov 10:00 – 10:02'), "a column's time span");
  Check(str_contains($Html, '…</text>') && $Data['rows'][1] === 'a job with a name far too long to fit the space for names', 'a long name cut, whole in the hover data');
  Check(preg_match('/viewBox="0 0 640 (\d+)"/', $Html, $Found) && (int)$Found[1] === 56, 'as tall as its rows');
  Same(1, preg_match_all('/<rect x="[\d.]+" y="19"/', $Html), "the quiet job's two equal cells: one rectangle");
  $Max = (new Heatmap())->Columns(2)->Aggregate('max');
  $Max->AddSeries('m', $Busy);
  Same(['40', '80'], Data((string)$Max)['f'][0], "Aggregate('max')");
  $Mean = (new Heatmap())->Columns(2)->Aggregate('mean');
  $Mean->AddSeries('m', $Busy);
  Same(['25', '65'], Data((string)$Mean)['f'][0], "Aggregate('mean')");
  $Shades = new Heatmap();
  $Shades->Columns(3);
  $Shades->AddSeries('s', [$Start => 1, $Start + 60 => 10, $Start + 120 => 100]);
  preg_match_all('/<rect x[^>]*fill-opacity="([\d.]+)"/', (string)$Shades, $Found);
  Same(['0.12', '0.56', '1'], $Found[1], 'log scale: 1, 10, 100 evenly shaded');
  $Shades->Scale('linear');
  preg_match_all('/<rect x[^>]*fill-opacity="([\d.]+)"/', (string)$Shades, $Found);
  Same(['0.12', '0.23', '1'], $Found[1], 'linear scale');
  $Cut = (new Heatmap())->Columns(2)->Aggregate('mean')->Thresholds([0 => 'green', 50 => 'red']);
  $Cut->AddSeries('t', $Busy);
  Check(str_contains((string)$Cut, 'fill="green"') && str_contains((string)$Cut, 'fill="red"'), 'by thresholds');
  Check(str_contains($Html, 'class="sc-heat-area"'), 'one area for the hover');

  // Annotations
  $Line = (new LineChart())->Time();
  $Line->AddSeries('x', [$Start => 1, $Start + 3600 => 2, $Start + 7200 => 3]);
  $Line->Annotate($Start + 1800, 'Deploy <1.31>', null, '/git/view.php?id=7')->Annotate('2026-11-17 11:15', 'Window', '#ff9830', null, $Start + 6000);
  $Line->Annotate($Start - 86400, 'Before the range');
  $Line->Annotations([['at' => $Start + 600, 'what' => 'Job changed', 'id' => 5]], 'at', 'what', null, '/jobs/view.php?id={id}');
  $Html = (string)$Line;
  Same(3, substr_count($Html, 'class="sc-annotation-marker"'), 'a marker per annotation in the range');
  Same(1, substr_count($Html, '<rect class="sc-annotation"'), 'a stretch for the one with Until');
  Check(str_contains($Html, 'data-href="/git/view.php?id=7"') && str_contains($Html, 'data-href="/jobs/view.php?id=5"'), 'links, also from rows');
  Check(str_contains(html_entity_decode($Html), '"n":"Deploy <1.31>","c":"#6f42c1","v":"Tue 17 Nov 10:30"') && str_contains($Html, 'Deploy &lt;1.31&gt;'), "a marker's hover: text and time (escaped)");
  Check(str_contains(html_entity_decode($Html), '"v":"Tue 17 Nov 11:15 – 11:40"'), "a stretch's time: from and until");
  InApp(false);
  Check(str_contains((string)$Line, '<a href="/git/view.php?id=7"><polygon'), 'outside the app a marker is an SVG link');
