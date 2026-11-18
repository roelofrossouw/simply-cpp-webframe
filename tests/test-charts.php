<?php

  // php/Chart: the scales, what each chart draws, the links an item opens (in the app and outside it), the escaping,
  // and app.js's and style.css's parts (their behaviour in a browser: public/demos/charts.php)
  require __DIR__ . '/check.inc';

  use Chart\{BarChart, LineChart, Open, PieChart, Scale, Stat};

  function InApp(bool $On): void
  {
    (new ReflectionProperty(Config::class, 'AppPage'))->setValue(null, $On);
  }

  // A line chart's hover data block, and the HTML without its scripts (the hover script mentions data-href itself)
  function HoverData(string $Html): ?array
  {
    return preg_match('/<script type="application\/json" class="sc-chart-data">(.*?)<\/script>/s', $Html, $Found) ? json_decode($Found[1], true) : null;
  }

  function NoScripts(string $Html): string
  {
    return preg_replace('/<script\b.*?<\/script>/s', '', $Html);
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
  Check(!str_contains(NoScripts($Html), 'data-href'), 'no data-href outside the app');
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
  $Data = HoverData($Html);
  Same(2, substr_count($Html, '<path d="M'), 'a missing value splits the line in two');
  Check(str_contains($Html, 'class="sc-hover-area oc-link"') && count($Data['x']) === 5, 'one hover area over the five labels, opening in the side panel');
  Same('/hour.php?at=02%3A00', $Data['href'][2], "each label's link, its label filled in");
  Same([1, 2, null, 4, 5], $Data['v'][0], 'the values, a missing one null');
  Same(['n' => 'Rate', 'c' => '#0d6efd'], $Data['series'][0], "the series' name and colour");
  Check(preg_match('/<path d="M\d+ \d+l-?\d+ -?\d+"/', $Html) === 1, 'a line as one path in whole pixels, each point a step from the last');
  Same(0, substr_count((string)(new LineChart())->Dots(false)->FromRows($Rows, 'month', 'sent'), '<circle'), 'Dots(false): no dots');
  Check(preg_match('/<path d="M[^"]*Z" fill="#0d6efd" fill-opacity="0.12"/', (string)(new LineChart())->Area()->FromRows($Rows, 'month', 'sent')) === 1, 'Area() fills under the line');
  Same(['R 100', 'R 150', 'R 90'], HoverData((string)(new LineChart())->Format(fn(float $V) => 'R ' . $V)->FromRows($Rows, 'month', 'sent'))['f'][0], 'Format() writes the values');
  Check(!isset(HoverData((string)(new LineChart())->FromRows($Rows, 'month', 'sent'))['f']), 'without Format() the script writes them');

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
  Check(str_contains($Js, 'setupCharts();') && str_contains($Js, "'.sc-chart [data-href], .sc-stat[data-href], .sc-bargauge[data-href]'"), 'app.js opens chart items and tiles');
  Check(str_contains($Js, "loadPage.call(this, this.getAttribute('data-href'))"), 'chart links load like buttons (modal-link, oc-link)');
  $Css = file_get_contents(__DIR__ . '/../public/css/style.css');
  Check(str_contains($Css, '.sc-chart svg .sc-off') && str_contains($Css, '.sc-chart-tip'), 'style.css hides series and styles the tooltip');

  // Time ranges: durations, steps rounded so queries share times, ends on a step
  Same(21600, Chart\TimeRange::Seconds('6h'), "Seconds('6h')");
  Same(604800, Chart\TimeRange::Seconds('1w'), "Seconds('1w')");
  $Range = Chart\TimeRange::Last('6h', 240, 1_000_000_000);
  Same(120, $Range->Step, '6 hours in about 240 points: 2-minute steps');
  Check($Range->Start % 120 === 0 && $Range->End % 120 === 0 && $Range->End <= 1_000_000_000, 'the ends fall on a step, not after now');
  try
  {
    Chart\TimeRange::Seconds('6 hours');
    $Refused = false;
  }
  catch (InvalidArgumentException)
  {
    $Refused = true;
  }
  Check($Refused, "'6 hours' isn't a duration");

  // Round times, in the site's time zone
  date_default_timezone_set('UTC');
  $Start = gmmktime(9, 7, 0, 11, 17, 2026);
  $Texts = array_column(Chart\TimeTicks::Between($Start, $Start + 6 * 3600 - 600, 6), 1);
  Same(['10:00', '11:00', '12:00', '13:00', '14:00'], $Texts, '6 hours: hourly ticks on the hour');
  $Texts = array_column(Chart\TimeTicks::Between(gmmktime(18, 0, 0, 11, 15, 2026), gmmktime(12, 0, 0, 11, 17, 2026), 8), 1);
  Check(in_array('Mon 16 Nov', $Texts, true) && in_array('06:00', $Texts, true), "2 days: 6-hourly, the day's name at midnight");
  $Texts = array_column(Chart\TimeTicks::Between(gmmktime(0, 0, 0, 1, 10, 2026), gmmktime(0, 0, 0, 11, 10, 2026), 6), 1);
  Same(['Apr 2026', 'Jul', 'Oct'], $Texts, 'months: quarters, the year with the first');
  date_default_timezone_set('Africa/Johannesburg');
  $Local = Chart\TimeTicks::Between(gmmktime(20, 0, 0, 11, 16, 2026), gmmktime(4, 0, 0, 11, 17, 2026), 3);
  Same(['00:00', '03:00', '06:00'], array_column($Local, 1), 'ticks fall on round local hours (22:00 to 06:00 at +02:00, 3-hourly)');

  // A chart on a time axis: in time order, placed by time, a gap where samples are missing
  InApp(true);
  $Base = gmmktime(10, 0, 0, 11, 17, 2026);
  $Timed = (new LineChart())->Time();
  $Values = [];
  foreach ([0, 1, 2, 3, 7, 8, 9] as $Minute)
  {
    $Values[$Base + 60 * $Minute] = $Minute;
  }
  $Values = array_reverse($Values, true); // given out of order
  $Timed->AddSeries('Rate', $Values);
  $Timed->Open('/at.php?t={time}&iso={iso}');
  $Html = (string)$Timed;
  $Data = HoverData($Html);
  Same(2, substr_count($Html, '<path d="M'), 'four minutes without samples split the line');
  Same(array_map(fn(int $Time) => '/at.php?t=' . $Time . '&iso=' . rawurlencode(date('c', $Time)), array_keys(array_reverse($Values, true))), $Data['href'],
    'the links in time order, {time} and {iso} filled in');
  Check($Data['x'][0] < $Data['x'][1] && $Data['x'][4] - $Data['x'][3] > 3 * ($Data['x'][1] - $Data['x'][0]), 'each label where its time lies across');
  Check(str_contains($Html, 'iso=' . rawurlencode(date('c', $Base))), '{iso} filled in');
  Check(str_contains($Html, '>12:00</text>') || str_contains($Html, '>12:02</text>'), 'the axis shows round local times');
  Same('Tue 17 Nov 12:00', $Data['head'][0], "a time's box names it");
  $Text = (new LineChart())->Time()->Labels(['2026-11-17 10:00', 'not a time', '2026-11-17 11:00']);
  $Text->AddSeries('x', ['2026-11-17 10:00' => 1, '2026-11-17 11:00' => 2]);
  Same(2, count(HoverData((string)$Text)['x']), 'text times are read; what isn\'t a time is left out');

  // Series links: a line opens its series' page with its labels
  $Series = (new LineChart())->Time();
  $Series->AddSeries('api1', [$Base => 1, $Base + 60 => 2])->Labels = ['instance' => 'api1', 'job' => 'oneapi'];
  $Series->AddSeries('api2', [$Base => 3, $Base + 60 => 4])->Labels = ['instance' => 'api2', 'job' => 'oneapi'];
  $Series->OpenSeries('/servers/view.php?name={instance}&job={job}', Open::SidePanel);
  $Html = (string)$Series;
  Check(str_contains($Html, 'class="sc-series-link oc-link"') && str_contains($Html, 'data-href="/servers/view.php?name=api2&amp;job=oneapi"'),
    "a line opens its series' page, its labels filled in");
  Check(str_contains($Html, '<span class="sc-legend-item oc-link" data-href="/servers/view.php?name=api1&amp;job=oneapi"'),
    "with OpenSeries() a legend item opens the series' page instead of hiding it");

  // Prometheus: answers parsed, legends, intervals, and a chart that says why there's nothing
  $Matrix = Chart\Prometheus::Matrix(['resultType' => 'matrix', 'result' => [
    ['metric' => ['__name__' => 'up', 'instance' => 'api1', 'job' => 'oneapi'], 'values' => [[1700000000, '1'], [1700000015.000, 'NaN'], [1700000030, '0.5']]]]]);
  Same([1700000000 => 1.0, 1700000015 => null, 1700000030 => 0.5], $Matrix[0]['points'], 'a matrix: times to values, NaN as no point');
  Same('api1 (oneapi)', Chart\Prometheus::Legend('{{instance}} ({{ job }})', $Matrix[0]['labels']), 'a legend fills in {{label}}s');
  Same('up{instance="api1", job="oneapi"}', Chart\Prometheus::Legend('', $Matrix[0]['labels']), 'without a legend: the metric as Prometheus writes it');
  Same([['labels' => [], 'value' => 42.0]], Chart\Prometheus::Vector(['resultType' => 'scalar', 'result' => [1700000000, '42']]), 'a scalar');
  Same(2.5, Chart\Prometheus::Vector(['resultType' => 'vector', 'result' => [['metric' => ['job' => 'x'], 'value' => [1, '2.5']]]])[0]['value'], 'a vector');
  $Prometheus = new Chart\Prometheus('http://127.0.0.1:1', 2);
  Same('rate(x[135s]) step 120s', $Prometheus->Intervals('rate(x[$__rate_interval]) step $__interval', 120), "Grafana's \$__rate_interval and \$__interval");
  Same('rate(x[60s])', $Prometheus->Intervals('rate(x[$__rate_interval])', 15), '$__rate_interval is at least four scrapes');
  $Down = new LineChart();
  $Prometheus->AddTo($Down, 'up', Chart\TimeRange::Last('1h'));
  Check(str_contains((string)$Down, 'Prometheus did not answer'), 'a Prometheus that does not answer: the chart says so');
  try
  {
    $Prometheus->Value('up');
    $Threw = false;
  }
  catch (RuntimeException)
  {
    $Threw = true;
  }
  Check($Threw, 'Value() throws when Prometheus does not answer');

  // The hover box's data (app.js draws the crosshair, the dots and the box from it)
  InApp(true);
  $Hover = (new LineChart())->Time();
  $Hover->AddSeries('Submitted', [$Base => 1603, $Base + 60 => 1610]);
  $Hover->AddSeries('Approved', [$Base => 1535, $Base + 60 => null]);
  $Html = (string)$Hover;
  $Data = HoverData($Html);
  Same([['n' => 'Submitted', 'c' => '#0d6efd'], ['n' => 'Approved', 'c' => '#20c997']], $Data['series'], 'the series for the box');
  Same([[1603, 1610], [1535, null]], $Data['v'], 'the values for the box and the dots, a missing one null (no dot)');
  Same(['top', 'bottom', 'low', 'high'], array_values(array_intersect(['top', 'bottom', 'low', 'high'], array_keys($Data['y']))), 'the scale, for the dots');
  Same(1, substr_count($Html, 'class="sc-chart-data"'), 'one data block for the chart');
  $Many = new LineChart();
  $Many->AddSeries('x', [1.23456, 123.456, 12345.6, 7.0]);
  Same([1.23, 123.5, 12346.0, 7.0], array_map('floatval', HoverData((string)$Many)['v'][0]), 'values rounded as the box shows them');
  $Odd = new LineChart('a');
  $Odd->AddSeries('</script><b>&', [1, 2]);
  Check(str_contains((string)$Odd, '\u003C/script\u003E\u003Cb\u003E\u0026') && HoverData((string)$Odd)['series'][0]['n'] === '</script><b>&',
    "names with markup characters can't break out of the data block");
  $Bars = (string)(new BarChart())->FromRows($Rows2 = [['m' => 'Jan', 'a' => 3]], 'm', ['a' => 'Sent']);
  Check(str_contains(html_entity_decode($Bars), 'data-rows="[{"n":"Sent","c":"#0d6efd","v":"3","r":3,"s":0}]"') && str_contains($Bars, 'data-head="Jan"'), 'a bar\'s box: its own value');
  $Slice = (string)(new PieChart())->FromRows([['d' => 'a', 'n' => 3], ['d' => 'b', 'n' => 1]], 'd', 'n');
  Check(str_contains(html_entity_decode($Slice), '"n":"a","c":"#0d6efd","v":"3 (75%)"'), "a slice's box: its name, value and share");
  $Js = file_get_contents(__DIR__ . '/../public/js/app.js');
  $Script = file_get_contents(__DIR__ . '/../php/Chart/standalone.js');
  Check(str_contains($Script, 'const showAt = function (area, index, clientX, clientY)') && str_contains($Script, "line.setAttribute('class', 'sc-crosshair')")
    && !str_contains($Js, 'const crosshair'), 'the hover (crosshair, dots, box) is standalone.js, in the app too; app.js has no copy');
  $Top = new LineChart();
  foreach (range(1, 8) as $Number)
  {
    $Top->AddSeries("s$Number", [$Number, $Number * 2, $Number]);
  }
  $Top->Top(3);
  $Data = HoverData((string)$Top);
  Same(['s6', 's7', 's8', 'Others (5)'], array_column($Data['series'], 'n'), 'Top(3): the three highest, the rest as Others');
  Same([15, 30, 15], $Data['v'][3], 'Others adds the rest up');
  Same('#adb5bd', $Data['series'][3]['c'], 'Others in grey');
  $Last = new LineChart();
  $Last->AddSeries('early', [9, 1]);
  $Last->AddSeries('late', [1, 5]);
  $Last->AddSeries('flat', [2, 2]);
  Same(['late', 'Others (2)'], array_column(HoverData((string)$Last->Top(1, 'last'))['series'], 'n'), "Top(1, 'last'): by the last value");
  $Wide = new LineChart();
  foreach (range(1, 14) as $Number)
  {
    $Wide->AddSeries("s$Number", [1, 2]);
  }
  Check(str_contains((string)$Wide, 'max-height:7.5rem;overflow-y:auto'), 'a legend of more than 12 series scrolls');
