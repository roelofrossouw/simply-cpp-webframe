<?php

  // Chart\Loki against a stand-in (tests/loki/router.php under PHP's built-in server): metric queries in Prometheus'
  // shapes, Grafana's variables, parallel queries joined into rows, log lines in time order with their JSON, line
  // counts, label values, errors; Chart\Logs drawing them; Chart\Prometheus still answering through the shared base.
  require __DIR__ . '/check.inc';

  use Chart\{LineChart, Logs, Loki, Open, Prometheus, TimeRange};

  // The stand-in, on a free port
  $Socket = stream_socket_server('tcp://127.0.0.1:0');
  $Port = (int)substr(strrchr(stream_socket_get_name($Socket, false), ':'), 1);
  fclose($Socket);
  $Server = proc_open([PHP_BINARY, '-S', "127.0.0.1:$Port", __DIR__ . '/loki/router.php'], [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $Pipes);
  register_shutdown_function(fn() => proc_terminate($Server));
  for ($Try = 0; $Try < 50 && !@fsockopen('127.0.0.1', $Port); $Try++)
  {
    usleep(100000);
  }

  $Loki = new Loki("http://127.0.0.1:$Port/", 5);
  $Day = TimeRange::Between(1_700_000_000, 1_700_086_400, 240);

  // Grafana's variables
  Same('x[86400s] y[86400s] z[86400s]', $Loki->Variables('x[$__range] y[$__auto] z[$__interval]', null, 86400), 'an instant query: $__range, $__auto and $__interval all the range');
  Same('x[360s] y[360s]', $Loki->Variables('x[$__interval] y[$__auto]', 360), 'a range query: $__interval and $__auto the step');

  // Metric queries, as Prometheus'
  Same(4512.0, $Loki->Value('sum(count_over_time({job="oneweb"}[$__range]))', null, $Day), 'Value()');
  Same(4512, $Loki->LineCount('{job="oneweb"}', $Day), 'LineCount(): sum(count_over_time()) over the range');
  $Series = $Loki->Range('sum by (host) (count_over_time({job="oneweb"}[$__interval]))', $Day);
  Same([$Day->Start => 1.0, $Day->Start + $Day->Step => 2.0], $Series[0]['points'], 'Range(): a matrix, as Prometheus gives');
  $Rows = $Loki->JoinedRows([
    'requests' => 'topk(10, sum by (name) (count_over_time({job="oneweb"} | json [$__range])))',
    'ms' => 'sum by (name) (sum_over_time({job="oneweb"} | json | unwrap ms [$__range]))',
  ], $Day);
  Same([['name' => 'A', 'requests' => 50.0, 'ms' => 1200.5], ['name' => 'B', 'requests' => 30.0, 'ms' => 900.0]], $Rows,
    'JoinedRows(): the first query\'s rows, highest first, the others\' columns joined by labels (C, not in the top, left out)');
  $Answers = $Loki->Instants(['good' => 'sum(count_over_time({job="oneweb"}[1h]))', 'bad' => 'broken(']);
  Check(is_array($Answers['good']) && $Answers['bad'] instanceof RuntimeException && str_contains($Answers['bad']->getMessage(), 'parse error'),
    'Instants(): side by side, one failing doesn\'t stop the other');
  Same(['apache1', 'apache2', 'apache10'], $Loki->LabelValues('host', '{job="oneweb"}'), 'LabelValues(), in natural order');

  // Totals over a period from buckets (the stand-in answers a range query with points 1 and 2, an edge with 10)
  $Hours = TimeRange::Steps(1_699_999_200, 1_700_006_400, 60);   // on whole hours: buckets only
  Same([['labels' => ['host' => 'apache1'], 'value' => 3.0]], $Loki->PeriodValues('sum by (host) (count_over_time({job="oneweb"}[$__interval]))', $Hours, 'sum', '1h'),
    'PeriodValues(): whole buckets summed');
  $Unaligned = TimeRange::Steps(1_699_999_200 + 600, 1_700_006_400 + 300, 60);
  Same(21.0, $Loki->PeriodValues('sum by (host) (count_over_time({job="oneweb"}[$__interval]))', $Unaligned, 'sum', '1h')[0]['value'],
    'the one whole bucket inside (1; the point after it left out) and the part-buckets at both ends (10 each) as instant queries');
  Same(10.0, $Loki->PeriodValues('max by (host) (max_over_time({job="oneweb"} | unwrap ms by (host) [$__interval]))', $Unaligned, 'max', '1h')[0]['value'],
    'max: the largest of the buckets and the ends');
  Same([['host' => 'apache1', 'requests' => 3.0, 'total' => 3.0, 'avg' => 1.0]], $Loki->PeriodRows([
    'requests' => ['sum by (host) (count_over_time({job="oneweb"}[$__interval]))', 'sum'],
    'total' => ['sum by (host) (sum_over_time({job="oneweb"} | unwrap ms by (host) [$__interval]))', 'sum'],
    'avg' => ['avg', 'total', 'requests'],
  ], $Hours, '1h', 10), 'PeriodRows(): columns from buckets, an average of two of them');
  $Steps = TimeRange::Steps(100, 3700, 3600);
  Check($Steps->Start === 100 && $Steps->End === 3700 && $Steps->Step === 3600, 'TimeRange::Steps(): exact, not rounded');

  // A chart from a range query, and a failure said on it
  $Chart = new LineChart();
  $Loki->AddTo($Chart, 'sum by (host) (count_over_time({job="oneweb"}[$__interval]))', $Day, '{{host}}');
  Check(str_contains((string)$Chart, '>apache1<') || str_contains((string)$Chart, 'apache1'), 'AddTo(): a line per result, named by the legend');
  $Broken = new LineChart();
  $Loki->AddTo($Broken, 'broken(', $Day);
  Check(str_contains((string)$Broken, 'Loki: parse error at line 1'), 'AddTo(): a failed query is said on the chart');
  try
  {
    $Loki->Value('broken(');
    $Threw = false;
  }
  catch (RuntimeException)
  {
    $Threw = true;
  }
  Check($Threw, 'Value() throws for a failed query');

  // Log lines
  $Lines = $Loki->Lines('{job="oneweb"} | json', $Day, 10);
  Same(['1700000003000000000', '1700000002500000000', '1700000001000000000'], array_column($Lines, 'ns'), 'Lines(): newest first across streams');
  Same(1700000002.5, $Lines[1]['time'], 'a line\'s time in seconds, with its fraction');
  Same(['page' => '/a.php', 'code' => 500, 'name' => 'A'], $Lines[0]['fields'], 'a JSON line\'s fields');
  Check($Lines[2]['fields'] === null && $Lines[2]['line'] === 'plain text line', 'a plain line keeps its text, without fields');
  Same(['job' => 'oneweb', 'host' => 'apache2'], $Lines[1]['labels'], 'and its stream\'s labels');
  Same(2, count($Loki->Lines('{job="oneweb"}', $Day, 2)), 'Lines(): at most the limit');
  Same('1700000001000000000', $Loki->Lines('{job="oneweb"}', $Day, 10, 'forward')[0]['ns'], 'forward: oldest first');
  try
  {
    $Loki->Lines('sum(count_over_time({job="oneweb"}[1h]))', $Day);
    $Threw = false;
  }
  catch (RuntimeException $Problem)
  {
    $Threw = str_contains($Problem->getMessage(), 'use Range()');
  }
  Check($Threw, 'Lines() of a metric query says so');

  // The log panel
  date_default_timezone_set('UTC');
  $Logs = (new Logs('Errors <today>'))->Lines($Lines)->Total(4512)->Fields(['page' => 'Page', 'code' => 'Code', 'host' => 'Host'])
    ->Level(fn(array $Line) => ($Line['fields']['code'] ?? 0) >= 500 ? 'error' : 'warn')->Filter()->Open('/pages.php?page={page}&host={host}', Open::SidePanel);
  $Html = (string)$Logs;
  Check(str_contains($Html, 'Errors &lt;today&gt;'), 'the title, escaped');
  Same(3, substr_count($Html, '<details class="sc-log"'), 'a row per line');
  Check(str_contains($Html, 'showing 3 of 4 512'), 'showing N of M');
  Check(str_contains($Html, 'border-left:3px solid #e02f44') && str_contains($Html, 'border-left:3px solid #ff9830'), 'a colour per level');
  Check(strpos($Html, '/a.php') < strpos($Html, '/b.php'), 'newest first, as given');
  Check(str_contains($Html, '22:13:23.000') && str_contains($Html, '22:13:22.500'), 'times to the millisecond');
  Check(str_contains($Html, '&quot;code&quot;: 500') && str_contains($Html, '&quot;labels&quot;: {'), 'expanded: the fields as JSON, and the labels');
  Check(str_contains($Html, 'type="search"') && str_contains($Html, 'querySelectorAll(&apos;.sc-log&apos;)'), 'the filter box, with its own handler');
  Check(str_contains($Html, '<a href="/pages.php?page=%2Fa.php&amp;host=apache1"'), 'outside the app a row\'s link is a plain link, with its fields and labels');
  (new ReflectionProperty(Config::class, 'AppPage'))->setValue(null, true);
  Check(str_contains((string)$Logs, 'data-href="/pages.php?page=%2Fa.php&amp;host=apache1" role="link" tabindex="0" aria-label="Open" class="oc-link"'),
    'in the app a row\'s link opens in the side panel');
  (new ReflectionProperty(Config::class, 'AppPage'))->setValue(null, false);
  Check(str_contains((string)(new Logs())->Lines([['time' => 1, 'line' => 'x', 'labels' => ['level' => 'debug']]]), '#5794f2'), 'the level from a label by default');
  Check(str_contains((string)new Logs(), 'No lines'), 'no lines: says so');
  Check(str_contains((string)(new Logs())->Lines([['time' => 1, 'line' => 'plain', 'labels' => []]]), '>plain</span>'), 'without fields: the line\'s text');

  // Prometheus through the shared base: its own path, its own name in messages
  $Prometheus = new Prometheus("http://127.0.0.1:$Port", 5);
  try
  {
    $Prometheus->Value('up');
    $Said = '';
  }
  catch (RuntimeException $Problem)
  {
    $Said = $Problem->getMessage();
  }
  Check(str_starts_with($Said, 'Prometheus answered 404'), 'Prometheus asks its own API path and names itself');
  Same('rate(x[60s]) [3600s]', $Prometheus->Variables('rate(x[$__rate_interval]) [$__range]', null, 3600), 'Prometheus: $__range for an instant query');
