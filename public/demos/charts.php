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
