<?php
  require_once __DIR__ . '/_demo.php';

  use Chart\{BarChart, Dashboard, LineChart, Open, PieChart, Prometheus, Stat};

  Config::Init(Access: Config::FrameworkURL(DEMOS));

  // A dashboard (Chart\Dashboard): a bar with a variable, the time range and the refresh, then rows of panels. The
  // made-up figures follow the range and the server chosen; with the site's settings naming a Prometheus
  // (prometheus.url) a row of its metrics follows, its server list from Prometheus. Every item opens chart-item.php.
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

  $Dashboard->Row('Traffic');
  $Traffic = (new LineChart('Mails per minute'))->Time();
  foreach ($Rates as $Name => $Points)
  {
    $Traffic->AddSeries($Name, $Points)->Labels = ['server' => $Name];
  }
  $Traffic->Open("$Popup?label={iso}&what=Mails+per+minute", Open::Popup)->OpenSeries("$Popup?what={server}&in=panel", Open::SidePanel);
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
    $Up = new LineChart('Targets up, by job');
    $Prometheus->AddTo($Up, "sum by (job) (up{{$Matcher}})", $Dashboard->Range(), '{{job}}');
    $Up->OpenSeries("$Popup?what={job}", Open::Popup);
    $Dashboard->Add($Up, 6);
  }

  Page::AddContent($Dashboard);
