<?php
  require_once __DIR__ . '/_demo.php';

  use Chart\{BarChart, BarGauge, Dashboard, Heatmap, LineChart, Logs, Loki, Open, PieChart, Prometheus, Stat, StateTimeline, Table};

  Config::Init(Access: Config::FrameworkURL(DEMOS));

  // A dashboard (Chart\Dashboard): a bar with a variable, the time range and the refresh, then rows of panels. The
  // made-up figures follow the range and the server chosen; with the site's settings naming a Prometheus
  // (prometheus.url) a row of its metrics follows, its server list from Prometheus; with a Loki (loki.url) a row of
  // a log's figures and latest lines follows, the log chosen by its job. Every item opens chart-item.php.
  $Popup = Demo('chart-item.php');
  $Dashboard = new Dashboard('Mail servers', Range: '24h', Refresh: 0);
  $Server = $Dashboard->Variable('server', 'Server', ['smtp1' => 'smtp1', 'smtp2' => 'smtp2', 'mail1' => 'mail1'], All: true);
  $Servers = $Dashboard->Values('server');
  $Range = $Dashboard->Range(120);

  // Made-up per-minute rates for each server chosen, steady for a time so refreshes don't jump about
  $Rates = [];
  foreach ($Servers as $Number => $Name)
  {
    for ($Time = $Range->Start; $Time <= $Range->End; $Time += $Range->Step)
    {
      $Rates[$Name][$Time] = round(40 + 25 * sin($Time / 9000 + $Number) + 10 * cos($Time / 1700 + 2 * $Number), 1);
    }
  }
  $Latest = array_sum(array_map(fn(array $Points) => end($Points), $Rates));
  $First = array_sum(array_map(fn(array $Points) => reset($Points), $Rates));

  $Dashboard->Row('Now');
  $Dashboard->Add((new Stat('Mails per minute', round($Latest)))->Change(round($Latest - $First), 'over ' . $Dashboard->RangeName())
    ->Trend(array_map(fn(...$Values) => array_sum($Values), ...array_values($Rates)))->Open("$Popup?what=Mails+per+minute", Open::Popup), 3);
  $Dashboard->Add((new Stat('Queued', 12 * count($Servers)))->Change(-3, 'since the last hour', UpIsGood: false), 3);
  $Dashboard->Add((new Stat('Bounced today', 37))->Change(5, 'since yesterday', UpIsGood: false)->Open("$Popup?what=Bounced&in=panel", Open::SidePanel), 3);
  $Dashboard->Add(new Stat('Servers', count($Servers) . ' of 3'), 3);

  $Dashboard->Row('Load');
  $Dashboard->Add((new BarGauge('smtp1 CPU', 29.0))->Unit('%')->Info('CPU busy, the last 5 minutes')->Open("$Popup?what=smtp1+CPU&in=panel", Open::SidePanel), 4);
  $Dashboard->Add((new BarGauge('smtp2 disk', 84.6))->Unit('%')->Info('/var/spool used'), 4);
  $Dashboard->Add((new BarGauge('Queue age', 41))->Range(0, 60)->Thresholds([0 => BarGauge::GREEN, 30 => BarGauge::ORANGE, 50 => BarGauge::RED])
    ->Unit(' min', 0)->Info('The oldest mail waiting, in minutes'), 4);

  $Dashboard->Row('Traffic');
  $Traffic = (new LineChart('Mails per minute'))->Time();
  foreach ($Rates as $Name => $Points)
  {
    $Traffic->AddSeries($Name, $Points)->Labels = ['server' => $Name];
  }
  $Traffic->Open("$Popup?label={iso}&what=Mails+per+minute", Open::Popup)->OpenSeries("$Popup?what={server}&in=panel", Open::SidePanel);
  // Annotations: a moment (a deploy) and a stretch (a maintenance window), from rows as a query gives them
  $Traffic->Annotations([
    ['at' => $Range->Start + intdiv($Range->End - $Range->Start, 3), 'what' => 'mail-post 1.2.0 installed', 'id' => 1],
    ['at' => $Range->Start + intdiv(2 * ($Range->End - $Range->Start), 3), 'until' => $Range->Start + intdiv(3 * ($Range->End - $Range->Start), 4),
      'what' => 'Maintenance window', 'id' => 2],
  ], 'at', 'what', 'until', "$Popup?what={what}");
  $Dashboard->Add($Traffic, 8);
  $Domains = [['domain' => 'gmail.com', 'mails' => 4210], ['domain' => 'outlook.com', 'mails' => 2870], ['domain' => 'example.com', 'mails' => 1260],
    ['domain' => 'other', 'mails' => 980]];
  $Dashboard->Add((new PieChart('Recipients by domain'))->FromRows($Domains, 'domain', 'mails')->Open("$Popup?domain={domain}", Open::Popup), 4);

  $Dashboard->Row('Delivery');
  $Hours = [];
  foreach (range(0, 23, 3) as $Hour)
  {
    $Hours[] = ['hour' => sprintf('%02d:00', $Hour), 'delivered' => 300 + 40 * ($Hour % 7), 'deferred' => 10 + $Hour, 'bounced' => 4 + $Hour % 5];
  }
  $Dashboard->Add((new BarChart('Today, every 3 hours'))->FromRows($Hours, 'hour', ['delivered' => 'Delivered', 'deferred' => 'Deferred', 'bounced' => 'Bounced'])
    ->Stacked()->Open("$Popup?label={hour}&what={series}&value={value}", Open::Popup), 12);

  // Up and down over the range, and a table of the servers with a bar and a sparkline each
  // A heatmap: a row per job, a column per stretch of time
  $Dashboard->Row('Jobs');
  $Heat = (new Heatmap('Job runs per minute'))->Aggregate('sum');
  foreach (['Allocations', 'Debit run', 'Letter queue', 'Process policy', 'Scheduled reports', 'Recalc requests', 'WhatsApp replies', 'Heartbeat'] as $Number => $Job)
  {
    $Runs = [];
    for ($Time = $Range->Start, $Step = 0; $Time <= $Range->End; $Time += $Range->Step, $Step++)
    {
      $Runs[$Time] = max(0, round((2 + $Number) * (1 + sin($Step / (4 + $Number))) * ($Step % (7 + $Number) < 3 ? 6 : 1) - 3, 1));
    }
    $Heat->AddSeries($Job, $Runs);
  }
  $Dashboard->Add($Heat, 12);

  $Dashboard->Row('Servers');
  $Timeline = new StateTimeline('Up');
  foreach ($Servers as $Number => $Name)
  {
    $States = [];
    for ($Time = $Range->Start, $Step = 0; $Time <= $Range->End; $Time += $Range->Step, $Step++)
    {
      $States[$Time] = ($Step + 17 * $Number) % 53 < 3 ? 0 : 1;
    }
    $Timeline->AddSeries($Name, $States)->Labels = ['server' => $Name];
  }
  $Timeline->OpenSeries("$Popup?what={server}&in=panel", Open::SidePanel);
  $Dashboard->Add($Timeline, 6);
  $Table = new Table();
  $Table->Column('server', 'Server');
  $Table->Column('rate', 'Mails per minute')->Thresholds([0 => BarGauge::GREEN, 60 => BarGauge::ORANGE, 70 => BarGauge::RED])->Show('bar', 80);
  $Table->Column('trend', 'Trend')->Sparkline();
  $Table->Rows(array_map(fn(string $Name) => ['server' => $Name, 'rate' => end($Rates[$Name]), 'trend' => array_values($Rates[$Name])], $Servers))
    ->Sort('rate')->Open("$Popup?what={server}", Open::Popup);
  $Dashboard->Add($Table, 6, 'Servers now');

  // Prometheus, when the site names one
  if (!empty(Config::Get('prometheus.url')))
  {
    $Prometheus = new Prometheus();
    try
    {
      $Instances = $Prometheus->LabelValues('instance', 'up');
    }
    catch (RuntimeException)
    {
      $Instances = [];
    }
    $Dashboard->Variable('instance', 'Target', $Instances, All: true);
    $Matcher = $Dashboard->Matcher('instance');
    $Dashboard->Row('From Prometheus');
    try
    {
      $Dashboard->Add(new Stat('Targets up', $Prometheus->Value("count(up{{$Matcher}} == 1)") ?? 0), 3);
      $Dashboard->Add((new Stat('Targets down', $Prometheus->Value("count(up{{$Matcher}} == 0)") ?? 0))->Change(0, UpIsGood: false), 3);
    }
    catch (RuntimeException $Problem)
    {
      $Dashboard->Add(new Stat('Prometheus', 'not answering'), 6);
    }
    try
    {
      $Busy = $Prometheus->Value('100 * (1 - avg(rate(node_cpu_seconds_total{mode="idle"}[5m])))');
      $Dashboard->Add((new BarGauge('CPU, all nodes', $Busy))->Unit('%')->Info('Average CPU busy of every node Prometheus scrapes, the last 5 minutes'), 6);
    }
    catch (RuntimeException)
    {
    }
    $Targets = new StateTimeline('Every target, up or down');
    $Prometheus->AddTo($Targets, "up{{$Matcher}}", $Dashboard->Range(), '{{job}} {{instance}}');
    $Dashboard->Add($Targets, 12);
    $Slowest = new Table('Slowest scrapes');
    $Slowest->Column('job', 'Job');
    $Slowest->Column('instance', 'Instance');
    $Slowest->Column('value', 'Scrape time')->Unit(' ms', 0)->Thresholds([0 => BarGauge::GREEN, 100 => BarGauge::ORANGE, 500 => BarGauge::RED])->Show('bar');
    $Slowest->Column('trend', 'Over the range')->Sparkline();
    try
    {
      $Slowest->Rows($Prometheus->TableRows("topk(8, 1000 * scrape_duration_seconds{{$Matcher}})", "1000 * scrape_duration_seconds{{$Matcher}}", $Dashboard->Range()))->Sort('value');
    }
    catch (RuntimeException)
    {
    }
    $Dashboard->Add($Slowest, 12);
    $Up = new LineChart('Targets up, by job');
    $Prometheus->AddTo($Up, "sum by (job) (up{{$Matcher}})", $Dashboard->Range(), '{{job}}');
    $Up->OpenSeries("$Popup?what={job}", Open::Popup);
    $Dashboard->Add($Up, 6);
  }

  // Loki, when the site names one: a log chosen by its job label, its lines per minute by level (Loki's detected_level,
  // so nothing needs parsing), the range's figures from buckets added up, and its latest lines
  if (!empty(Config::Get('loki.url')))
  {
    $Loki = new Loki();
    try
    {
      $Jobs = $Loki->LabelValues('job', null, '24h');
    }
    catch (RuntimeException)
    {
      $Jobs = [];
    }
    $Job = $Dashboard->Variable('job', 'Log', $Jobs);
    $Dashboard->Row('From Loki');
    if ($Job === '')
    {
      $Dashboard->Add(new Stat('Loki', $Jobs === [] ? 'not answering' : 'no logs'), 6);
    }
    else
    {
      $Selector = '{job="' . addcslashes($Job, '"\\') . '"}';
      $Range = $Dashboard->Range();
      // Figures over the range from buckets added up: hours for a long range, five minutes for a short one
      $Bucket = $Range->End - $Range->Start > 6 * 3600 ? '1h' : '5m';
      try
      {
        $Levels = $Loki->PeriodValues("sum by (detected_level) (count_over_time($Selector [\$__interval]))", $Range, 'sum', $Bucket);
        $Now = $Loki->Value("sum(count_over_time($Selector [5m]))");
        $Dashboard->Add((new Stat('Lines, ' . $Dashboard->RangeName(), array_sum(array_column($Levels, 'value'))))
          ->Open("$Popup?what=" . rawurlencode($Job), Open::Popup), 3);
        $Dashboard->Add(new Stat('Lines, last 5 minutes', $Now ?? 0), 3);
        $ByLevel = (new PieChart('Lines by level'))->FromRows(array_map(fn(array $Value) => ['level' => $Value['labels']['detected_level'] ?? 'unknown',
          'lines' => $Value['value']], $Levels), 'level', 'lines')->Open("$Popup?what={level}", Open::Popup);
      }
      catch (RuntimeException $Problem)
      {
        $Dashboard->Add(new Stat('Loki', 'not answering'), 6);
        $ByLevel = null;
      }
      $PerMinute = new LineChart('Lines per minute, by level');
      $Loki->AddTo($PerMinute, "sum by (detected_level) (count_over_time($Selector [\$__interval])) * 60 / " . max(1, $Range->Step), $Range,
        '{{detected_level}}');
      $PerMinute->Open("$Popup?what=" . rawurlencode($Job) . '&label={iso}', Open::Popup);
      $Dashboard->Add($PerMinute, $ByLevel !== null ? 8 : 12);
      if ($ByLevel !== null)
      {
        $Dashboard->Add($ByLevel, 4);
      }
      // The latest lines, newest first; a row expands to all its fields
      $Lines = new Logs('Latest lines');
      try
      {
        // The total from the figures above (the levels' lines added up): no second count of the whole range
        $Lines->Lines($Loki->Lines($Selector, $Range, 100))->Total(isset($Levels) ? (int)array_sum(array_column($Levels, 'value')) : null);
      }
      catch (RuntimeException $Problem)
      {
        $Lines->Lines([]);
      }
      $Lines->Filter()->Open("$Popup?what=" . rawurlencode($Job) . '&label={time}', Open::Popup);
      $Dashboard->Add($Lines, 12);
    }
  }

  Page::AddContent($Dashboard);
