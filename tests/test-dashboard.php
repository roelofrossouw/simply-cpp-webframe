<?php

  // Chart\Dashboard: its range, refresh and variables from the address (and what it refuses), PromQL matchers, the
  // bar and the panels' layout, and app.js's part (its behaviour in a browser: public/demos/dashboard.php)
  require __DIR__ . '/check.inc';

  use Chart\{Dashboard, LineChart, Stat};

  $_SERVER['SCRIPT_NAME'] = '/dash.php';

  // Defaults, when the address names nothing
  $_GET = [];
  $Dashboard = new Dashboard('Mail', Range: '24h', Refresh: 60);
  Same('24h', $Dashboard->RangeName(), 'the default range');
  $Range = $Dashboard->Range();
  Check(abs(($Range->End - $Range->Start) - 86400) <= $Range->Step, 'Range() covers the range chosen');
  Same('*', $Dashboard->Variable('server', 'Server', ['smtp1', 'smtp2'], All: true), 'a variable with All: All by default');
  Same(['smtp1', 'smtp2'], $Dashboard->Values('server'), 'All: every option');
  Same('server=~".*"', $Dashboard->Matcher('server'), 'All matches every series');
  Same('smtp1', $Dashboard->Variable('first', 'First', ['smtp1', 'smtp2']), 'without All: the first option');

  // From the address, Grafana's way; what isn't offered is ignored
  $_GET = ['range' => '6h', 'refresh' => '300', 'var-server' => 'smtp2', 'var-job' => 'evil"} or vector(1) #'];
  $Dashboard = new Dashboard('Mail', Range: '24h', Refresh: 60);
  Same('6h', $Dashboard->RangeName(), '?range= chooses the range');
  Same('smtp2', $Dashboard->Variable('server', 'Server', ['smtp1' => 'Mail one', 'smtp2' => 'Mail two'], All: true), '?var-server= chooses the value');
  Same('server=~"smtp2"', $Dashboard->Matcher('server'), 'a matcher for one value');
  Same('job="__none__"', (function () use ($Dashboard) {
    $Dashboard->Variable('job', 'Job', []);
    return $Dashboard->Matcher('job');
  })(), 'nothing to choose from: a matcher no series has');
  Same('oneapi', $Dashboard->Variable('app', 'App', ['oneapi', 'streamvms']) === 'oneapi' ? 'oneapi' : 'x', 'a value not offered falls back');
  $_GET = ['range' => '3y', 'refresh' => '7'];
  $Dashboard = new Dashboard('Mail', Range: '24h', Refresh: 60);
  Same('24h', $Dashboard->RangeName(), 'a range not offered is ignored');
  $_GET = ['var-host' => 'a.b'];
  $Dashboard = new Dashboard('X');
  $Dashboard->Variable('host', 'Host', ['a.b', 'a+b', 'q"x']);
  Same('host=~"a\\\\.b"', $Dashboard->Matcher('host'), 'regular expression characters are escaped');

  // The page: bar, rows, panels, its address and refresh for app.js
  $_GET = ['range' => '1h', 'var-server' => 'smtp1', '_' => '123'];
  $Dashboard = new Dashboard('Mail <servers>', Refresh: 30);
  $Dashboard->Variable('server', 'Server', ['smtp1', 'smtp2'], All: true);
  $Dashboard->Row('Now');
  $Dashboard->Add(new Stat('Sent', 5), 3);
  $Dashboard->Row('Traffic');
  $Line = new LineChart('Rate');
  $Line->AddSeries('x', [1, 2, 3]);
  $Dashboard->Add($Line, 8);
  $Dashboard->Add('<p>Text</p>', 4, 'Notes');
  $Html = (string)$Dashboard;
  Check(str_contains($Html, '<h2 class="h4 mb-0 me-auto">Mail &lt;servers&gt;</h2>'), 'the title, escaped');
  Check(str_contains($Html, 'data-url="/dash.php?range=1h&amp;var-server=smtp1"'), 'its address, without the cache buster');
  Check(str_contains($Html, 'data-refresh="30"'), 'its refresh for app.js');
  Check(str_contains($Html, 'data-dashboard-param="range" data-value="1h" aria-pressed="true"'), 'the range chosen is pressed');
  Check(str_contains($Html, '<option value="smtp1" selected>smtp1</option>') && str_contains($Html, '<option value="*">All</option>'), 'the variable\'s drop-down');
  Check(str_contains($Html, '<option value="30" selected>30s</option>'), 'the refresh chosen');
  Check(str_contains($Html, 'class="col-6 col-md-3 col-xl-3"><div class="sc-stat"'), 'a tile stands on its own');
  Check(str_contains($Html, 'class="col-12 col-md-8 col-xl-8"><div class="card h-100 sc-panel"><div class="card-body"><figure class="sc-chart">'), 'a chart on a card, 8 wide');
  Check(str_contains($Html, '<h4 class="h6 mb-2">Notes</h4><p>Text</p>'), 'a panel\'s title over what has none');
  Same(2, substr_count($Html, 'class="sc-dashboard-row'), 'a heading per row');

  $Js = file_get_contents(__DIR__ . '/../public/js/app.js');
  Check(str_contains($Js, "start('.sc-dashboard', armDashboard);") && str_contains($Js, 'function reloadDashboard(dashboard)'), 'app.js refreshes dashboards in place');
  Check(str_contains($Js, "url.searchParams.set(element.getAttribute('data-dashboard-param'), value);"), 'the bar\'s choices go into the address');
