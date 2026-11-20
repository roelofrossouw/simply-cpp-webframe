<?php
  require_once __DIR__ . '/_demo.php';

  use BS\{AlertInfo, Card, Grid, ScreenSize};
  use Chart\{BarChart, LineChart, Open, PieChart, Prometheus, Stat, TimeRange};

  Config::Init(Access: Config::FrameworkURL(DEMOS));

  // Charts drawn on the server as SVG (php/Chart), from rows such as a query answers (here fixed lists). Every bar,
  // slice, point and tile opens a page when clicked: here chart-item.php in a popup or the side panel, which shows what
  // it was given. The pointer on an item shows its values; a series in the legend hides or shows it.
  $Months = ['Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov'];
  $Rows = [];
  foreach ($Months as $Number => $Month)
  {
    $Rows[] = ['month' => $Month, 'month_no' => $Number + 6, 'sent' => 4200 + 380 * $Number + ($Number % 2) * 250,
      'bounced' => 120 + 15 * ($Number % 3), 'complaints' => 3 + $Number % 2];
  }
  $Popup = Demo('chart-item.php');

  // Tiles
  Page::AddContent($Grid = new Grid());
  $Grid->Columns(3, ScreenSize::Medium);
  $Sent = array_column($Rows, 'sent');
  $Grid->AddChild((new Stat('Mails sent in November', end($Sent)))->Change(end($Sent) - $Sent[count($Sent) - 2], 'since October')
    ->Trend($Sent)->Open("$Popup?what=Mails+sent", Open::Popup));
  $Bounced = array_column($Rows, 'bounced');
  $Grid->AddChild((new Stat('Bounced in November', end($Bounced)))->Change(end($Bounced) - $Bounced[count($Bounced) - 2], 'since October', UpIsGood: false)
    ->Trend($Bounced)->Open("$Popup?what=Bounced&in=panel", Open::SidePanel));
  $Grid->AddChild((new Stat('Delivered', '97.3%'))->Change(0.4, 'points since October'));

  // Bars: each bar opens its month and series in a popup
  Page::AddContent($Card = new Card());
  $Card->AddHeader('Bars');
  $Card->AddParagraph('BarChart->FromRows($Rows, \'month\', [\'sent\' => \'Sent\', \'bounced\' => \'Bounced\'])->Open(\'chart-item.php?month={month_no}&what={series}\', Open::Popup): click a bar.');
  $Card->AddChild((new BarChart())->FromRows($Rows, 'month', ['sent' => 'Sent', 'bounced' => 'Bounced'])
    ->Open("$Popup?month={month_no}&label={label}&what={series}&value={value}", Open::Popup));

  // Lines with a gap, and an area
  Page::AddContent($Grid = new Grid());
  $Grid->Columns(2, ScreenSize::Large);
  $Line = new LineChart('Complaints and bounces');
  $Line->FromRows($Rows, 'month', ['complaints' => 'Complaints', 'bounced' => 'Bounced']);
  $Line->Open("$Popup?month={month_no}&label={label}&in=panel", Open::SidePanel);
  $Grid->AddChild($Line);
  $Hours = [];
  for ($Hour = 0; $Hour < 24; $Hour++)
  {
    $Hours[sprintf('%02d:00', $Hour)] = $Hour === 13 ? null : round(40 + 30 * sin($Hour / 3.8) + $Hour, 1);
  }
  $Area = new LineChart('Messages per minute (13:00 missing)');
  $Area->AddSeries('Messages', $Hours)->Color('#20c997');
  $Area->Area()->FromZero();
  $Grid->AddChild($Area);
  $Smooth = new LineChart('Schedules read per hour: Smooth()');
  $Smooth->AddSeries('Read', [0, 0, 0, 2, 9, 14, 6, 0, 0, 3, 11, 12, 4, 1, 0, 0, null, 5, 8, 2, 0, 0, 0, 1])->Color('#8e44ad');
  $Smooth->Area()->FromZero()->Smooth();
  $Grid->AddChild($Smooth);

  // Stacked bars and a doughnut
  Page::AddContent($Grid = new Grid());
  $Grid->Columns(2, ScreenSize::Large);
  $Stacked = new BarChart('Delivery by month (stacked)');
  $Stacked->FromRows($Rows, 'month', ['sent' => 'Delivered', 'bounced' => 'Bounced', 'complaints' => 'Complaints'])->Stacked()
    ->Open("$Popup?month={month_no}&label={label}&what={series}&value={value}", Open::Popup);
  $Grid->AddChild($Stacked);
  $Domains = [['domain' => 'gmail.com', 'mails' => 4210], ['domain' => 'outlook.com', 'mails' => 2870], ['domain' => 'example.com', 'mails' => 1260],
    ['domain' => 'yahoo.com', 'mails' => 640], ['domain' => 'other', 'mails' => 980]];
  $Pie = new PieChart('Recipients by domain');
  $Pie->FromRows($Domains, 'domain', 'mails')->Open("$Popup?domain={domain}&value={value}", Open::Popup);
  $Grid->AddChild($Pie);

  // A time axis: points placed by their time, round times marked; 40 minutes without samples leave a gap
  Page::AddContent($Card = new Card());
  $Card->AddHeader('On a time axis');
  $Card->AddParagraph('LineChart->Time(): the labels are times (Unix seconds, or text such as 2026-11-17 14:05), so the points lie by their time and the axis marks round times. A stretch without samples is a gap. Click a point to open its time ({time}, {iso}).');
  $Now = intdiv(time(), 300) * 300;
  $Points = [];
  for ($Time = $Now - 6 * 3600; $Time <= $Now; $Time += 300)
  {
    if ($Time < $Now - 3 * 3600 || $Time > $Now - 3 * 3600 + 2400)
    {
      $Points[$Time] = round(120 + 60 * sin($Time / 3000) + 15 * cos($Time / 700));
    }
  }
  $Timed = (new LineChart())->Time();
  $Timed->AddSeries('Requests per minute', $Points);
  $Timed->Open("$Popup?what=Requests+per+minute&label={iso}", Open::Popup);
  $Card->AddChild($Timed);

  // From Prometheus, when the site's settings name one (prometheus.url)
  Page::AddContent($Card = new Card());
  $Card->AddHeader('From Prometheus');
  if (empty(Config::Get('prometheus.url')))
  {
    $Card->AddChild(new AlertInfo('No Prometheus', 'Set prometheus.url in the site\'s settings (e.g. http://prometheus.example.com:9090) to see its metrics here.'));
  }
  else
  {
    $Card->AddParagraph('Chart\Prometheus->AddTo($Chart, $Query, TimeRange::Last(\'6h\'), \'{{job}}\'): a line per result, named by its labels. OpenSeries() opens a line\'s page with its labels as placeholders ({job}, {instance}): click a line or its legend item.');
    $Prometheus = new Prometheus();
    try
    {
      $Up = $Prometheus->Value('count(up == 1)');
      $Down = $Prometheus->Value('count(up == 0)') ?? 0;
      $Card->AddChild($Tiles = new Grid());
      $Tiles->Columns(2, ScreenSize::Medium);
      $Tiles->AddChild((new Stat('Targets up', $Up ?? '–')));
      $Tiles->AddChild((new Stat('Targets down', $Down))->Change(0, UpIsGood: false));
    }
    catch (RuntimeException $Problem)
    {
      $Card->AddChild(new AlertInfo('Prometheus', $Problem->getMessage()));
    }
    $Jobs = new LineChart('Targets up, by job (last 6 hours)');
    $Prometheus->AddTo($Jobs, 'sum by (job) (up)', TimeRange::Last('6h'), '{{job}}');
    $Jobs->OpenSeries("$Popup?what={job}", Open::Popup);
    $Card->AddChild($Jobs);
  }

  // Many series (a job queue's 125 job names, 6 hours in 90-second steps): one path per line and one block of data
  // for the hover, so it stays a few hundred KB; Top(10) keeps the ten highest and adds the rest up as Others
  Page::AddContent($Card = new Card());
  $Card->AddHeader('Many series');
  $Card->AddParagraph('125 series of 240 points each. The pointer shows the 25 highest at that time (and how many more). Top(10, \'max\') keeps the ten highest and adds the rest up as "Others".');
  $Start = intdiv(time(), 90) * 90 - 239 * 90;
  $Jobs = (new LineChart('All 125 jobs'))->Time();
  $Ten = (new LineChart('Top(10): the ten busiest, the rest as Others'))->Time();
  for ($Job = 0; $Job < 125; $Job++)
  {
    $Points = [];
    for ($Point = 0; $Point < 240; $Point++)
    {
      $Points[$Start + 90 * $Point] = round(abs(sin($Point / 20 + $Job)) * (1 + $Job % 13) * 6, 2);
    }
    $Jobs->AddSeries(sprintf('job_%03d', $Job), $Points)->Labels = ['name' => sprintf('job_%03d', $Job)];
    $Ten->AddSeries(sprintf('job_%03d', $Job), $Points);
  }
  $Jobs->OpenSeries("$Popup?what={name}", Open::Popup);
  $Card->AddChild($Jobs);
  $Card->AddChild($Ten->Top(10));

  // Table legends, as Grafana's: values per series, sorted, beside or under the drawing
  Page::AddContent($Card = new Card());
  $Card->AddHeader('Table legends');
  $Card->AddParagraph('Legend(\'table\', [\'last\', \'max\'], Placement: \'right\', SortBy: \'last\'): a row per series with its values over the range. Click a header to sort by it (again to turn it round), point at a row to bring its series forward, click a row to hide or show it. Others stays last, in grey. A stacked chart stacks its bands in the legend\'s first order: the top row is the top band, Others the bottom one (StackOrder() sets an order without a table legend).');
  $Side = (new LineChart('Top(8), stacked, legend on the right'))->Time()->Area()->Stacked();
  $Under = (new LineChart('Legend under the drawing'))->Time();
  for ($Job = 0; $Job < 30; $Job++)
  {
    $Points = [];
    for ($Point = 0; $Point < 240; $Point++)
    {
      $Points[$Start + 90 * $Point] = round(abs(sin($Point / 20 + $Job)) * (1 + $Job % 13) * 6, 2);
    }
    $Side->AddSeries(sprintf('job_%03d', $Job), $Points);
    if ($Job < 6)
    {
      $Under->AddSeries(sprintf('job_%03d', $Job), $Points);
    }
  }
  $Card->AddChild($Side->Top(8)->Legend('table', ['last', 'max', 'mean'], Placement: 'right', SortBy: 'last'));
  // On the right the legend is as wide as its names and values need (19% to 41%): short names leave the drawing more room
  $Short = (new LineChart('Short names: a narrow legend on the right'))->Time();
  $Long = (new LineChart('Long names: the legend at its widest, names cut'))->Time();
  foreach (['VMS', '2WC', 'MTA'] as $Number => $Name)
  {
    $Points = [];
    for ($Point = 0; $Point < 240; $Point++)
    {
      $Points[$Start + 90 * $Point] = round(abs(sin($Point / 25 + $Number)) * 40, 1);
    }
    $Short->AddSeries($Name, $Points);
    $Long->AddSeries("$Name positions received from the devices of every account", $Points);
  }
  $Card->AddChild($Short->Legend('table', ['last', 'max'], Placement: 'right'));
  $Card->AddChild($Long->Legend('table', ['last', 'max'], Placement: 'right'));
  $Card->AddChild($Under->Area()->Legend('table', ['last', 'min', 'max', 'sum'], SortBy: 'name', Desc: false));

  // Stacked, below zero, per-series settings and thresholds
  Page::AddContent($Grid = new Grid());
  $Grid->Columns(2, ScreenSize::Large);
  $Made = function (callable $Value): array {
    $Points = [];
    $Start = intdiv(time(), 300) * 300 - 119 * 300;
    for ($Point = 0; $Point < 120; $Point++)
    {
      $Points[$Start + 300 * $Point] = round($Value($Point), 2);
    }
    return $Points;
  };
  $Cpu = (new LineChart('CPU by mode: Stacked()->Area(), Thresholds()'))->Time()->Stacked()->Area()
    ->Thresholds([0 => Chart\BarGauge::GREEN, 80 => Chart\BarGauge::ORANGE, 90 => Chart\BarGauge::RED]);
  $Cpu->AddSeries('user', $Made(fn($I) => 30 + 15 * sin($I / 9)));
  $Cpu->AddSeries('system', $Made(fn($I) => 12 + 5 * cos($I / 7)));
  $Cpu->AddSeries('iowait', $Made(fn($I) => $I > 60 && $I < 75 ? 30 : 4));
  $Cpu->Override('user', Color: '#5794f2')->Override('system', Color: '#ff9830')->Override('iowait', Color: '#f2495c');
  $Grid->AddChild($Cpu);
  $Network = (new LineChart('Network: Negative() draws sent below zero'))->Time()->Area();
  $Network->AddSeries('received', $Made(fn($I) => 50 + 30 * sin($I / 10)));
  $Network->AddSeries('sent', $Made(fn($I) => 20 + 10 * cos($I / 8)))->Negative();
  $Grid->AddChild($Network);
  $Status = (new LineChart('Requests by status: Stacked(\'percent\'), Override(\'/^5/\', ...)'))->Time()->Stacked('percent')->Area();
  foreach (['200' => 80, '404' => 12, '503' => 3] as $Code => $Share)
  {
    $Status->AddSeries((string)$Code, $Made(fn($I) => $Share * (1 + 0.3 * sin($I / 5 + $Share))));
  }
  $Status->Override('/^2/', Color: Chart\BarGauge::GREEN)->Override('/^4/', Color: Chart\BarGauge::ORANGE)->Override('/^5/', Color: Chart\BarGauge::RED);
  $Grid->AddChild($Status);
  $Load = (new LineChart('Load: a Dashed() limit, Thresholds(..., \'area\')'))->Time()
    ->Thresholds([0 => Chart\BarGauge::GREEN, 70 => Chart\BarGauge::ORANGE, 90 => Chart\BarGauge::RED], 'area');
  $Load->AddSeries('load', $Made(fn($I) => 50 + 40 * sin($I / 15)));
  $Load->AddSeries('limit', $Made(fn($I) => 85))->Dashed()->Width(1.5)->Color('#888');
  $Grid->AddChild($Load);
