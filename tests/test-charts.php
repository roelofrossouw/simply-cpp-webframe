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
    if (!preg_match('/<script type="application\/json" class="sc-chart-data">(.*?)<\/script>/s', $Html, $Found))
    {
      return null;
    }
    // The values come as runs (value, count, ...), expanded here as the script does
    $Data = json_decode($Found[1], true);
    $Expand = function (array $Runs): array {
      $Values = [];
      for ($At = 0; $At < count($Runs); $At += 2)
      {
        array_push($Values, ...array_fill(0, $Runs[$At + 1], $Runs[$At]));
      }
      return $Values;
    };
    $Data['v'] = array_map($Expand, $Data['v']);
    if (isset($Data['f']))
    {
      $Data['f'] = array_map($Expand, $Data['f']);
    }
    return $Data;
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
  Same(['n' => 'Rate', 'c' => '#2a78d6'], $Data['series'][0], "the series' name and colour");
  Check(preg_match('/<path d="M\d+ \d+l-?\d+ -?\d+"/', $Html) === 1, 'a line as one path in whole pixels, each point a step from the last');
  Same(0, substr_count((string)(new LineChart())->Dots(false)->FromRows($Rows, 'month', 'sent'), '<circle'), 'Dots(false): no dots');
  $Faded = (string)(new LineChart())->Area()->FromRows($Rows, 'month', 'sent');
  Check(preg_match('/<linearGradient id="(sc[0-9a-f]{8}-fade0)" gradientUnits="userSpaceOnUse" x1="0" y1="10" x2="0" y2="\d+"><stop offset="0" stop-color="#2a78d6" stop-opacity="0.35"\/><stop offset="1" stop-color="#2a78d6" stop-opacity="0"\/>/', $Faded, $Gradient) === 1
    && str_contains($Faded, 'fill="url(#' . $Gradient[1] . ')"'), 'Area(): a gradient in the series\' colour, 0.35 at the top down to none at the baseline');
  $Two = (new LineChart())->Area()->FromRows($Rows, 'month', 'sent') . (new LineChart())->Area()->FromRows($Rows, 'month', 'sent');
  preg_match_all('/<linearGradient id="([^"]+)"/', $Two, $Ids);
  Check(count(array_unique($Ids[1])) === 2, 'two charts on a page: two different gradient ids');
  Check(preg_match('/<path d="M[^"]*Z" fill="url\(#/', (string)(new LineChart())->Area()->FromRows($Rows, 'month', 'sent')) === 1, 'Area() fills under the line');
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

  // The palette: 24 colours by position, repeating only past 24; a pie's Others slice in grey
  $Many = new BarChart();
  for ($Number = 0; $Number < 26; $Number++)
  {
    $Many->AddSeries("s$Number", [1]);
  }
  preg_match_all('/<rect class="sc-bar"[^>]* fill="(#[0-9a-f]{6})"/', (string)$Many, $Fills);
  Same(24, count(array_unique(array_slice($Fills[1], 0, 24))), '24 series: 24 different colours');
  Check($Fills[1][0] === '#2a78d6' && $Fills[1][23] === '#ffcd7a' && $Fills[1][24] === '#2a78d6', 'in the order given, the 25th starting over');
  $Pie = (string)(new PieChart())->FromRows([['n' => 'a', 'v' => 3], ['n' => 'Others', 'v' => 2]], 'n', 'v');
  Check(str_contains($Pie, 'fill="#9a9aa0"') && str_contains($Pie, 'fill="#2a78d6"'), 'a pie: an Others slice in grey, the rest from the palette');

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
  Check(str_contains($Js, 'setupCharts();') && str_contains($Js, "'.sc-chart [data-href], .sc-stat[data-href], .sc-bargauge[data-href], .sc-table tr[data-href]'"), 'app.js opens chart items and tiles');
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
  Same([['n' => 'Submitted', 'c' => '#2a78d6'], ['n' => 'Approved', 'c' => '#eb6834']], $Data['series'], 'the series for the box');
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
  Check(str_contains(html_entity_decode($Bars), 'data-rows="[{"n":"Sent","c":"#2a78d6","v":"3","r":3,"s":0}]"') && str_contains($Bars, 'data-head="Jan"'), 'a bar\'s box: its own value');
  $Slice = (string)(new PieChart())->FromRows([['d' => 'a', 'n' => 3], ['d' => 'b', 'n' => 1]], 'd', 'n');
  Check(str_contains(html_entity_decode($Slice), '"n":"a","c":"#2a78d6","v":"3 (75%)"'), "a slice's box: its name, value and share");
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
  Same('#9a9aa0', $Data['series'][3]['c'], 'Others in the neutral grey');
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

  // Thinning: only the points that show
  Same('M0 50l40 0', LineChart::PathOf(array_map(fn(int $X) => [$X, 50], range(0, 40))), 'a flat stretch is one step');
  Same('M0 0l10 10 10 -10', LineChart::PathOf([[0, 0], [5, 5], [10, 10], [15, 5], [20, 0]]), 'steps in one direction merge, a turn stays');
  Same('M0 10l0 -10 0 20 0 -10 5 0', LineChart::PathOf([[0, 10], [0, 3], [0, 0], [0, 7], [0, 20], [0, 12], [0, 10], [5, 10]]),
    "in one pixel column: its first, lowest, highest and last point (a spike still shows)");
  Same('M3 3l0 0', LineChart::PathOf([[3, 3], [3, 3]]), 'all in one pixel: a dot');
  Same([0, 3, 1.5, 1, null, 2, 0, 1], LineChart::Runs([0, 0, 0, 1.5, null, null, 0]), 'values as runs: value, count');
  $Busy = new LineChart();
  $Busy->AddSeries('flat', array_fill(0, 240, 5));
  Check(preg_match('/<path d="M\d+ \d+l\d+ 0"/', (string)$Busy) === 1, '240 equal values: one step');
  Same(290, (new LineChart())->Resolution(), 'the default size shows 290 points apart');
  Same(170, (new LineChart())->Size(400, 200)->Resolution(), 'a smaller chart fewer');

  // Stacked, below zero, overrides, thresholds
  function Tops(LineChart $Chart): array
  {
    (string)$Chart;
    // By series (a stacked chart fills them in its stack order)
    $Tops = (new ReflectionProperty(LineChart::class, 'Tops'))->getValue($Chart);
    ksort($Tops);
    return $Tops;
  }
  $Stack = new LineChart();
  $Stack->AddSeries('a', [1, 2, null]);
  $Stack->AddSeries('b', [3, 4, 5]);
  Same([[1.0, 2.0, null], [4.0, 6.0, 5.0]], Tops($Stack->Stacked()), 'stacked: each on the ones before (a gap stands on nothing)');
  Check(str_contains((string)$Stack, '"stack":"normal"'), 'the hover knows the stacking, for its dots');
  $Percent = new LineChart();
  $Percent->AddSeries('a', [1, 3]);
  $Percent->AddSeries('b', [3, 1]);
  Same([[25.0, 75.0], [100.0, 100.0]], Tops($Percent->Stacked('percent')), "percent: each time's total is 100");
  Check(str_contains((string)$Percent, '>100%</text>'), 'percent: the axis in %');
  $Net = new LineChart();
  $Net->AddSeries('received', [5, 6]);
  $Net->AddSeries('sent', [2, 3])->Negative();
  Same([[5.0, 6.0], [-2.0, -3.0]], Tops($Net), 'Negative(): below zero');
  Same([2, 3], HoverData((string)$Net)['v'][1], 'the hover shows its own value');
  Check(HoverData((string)$Net)['series'][1]['neg'] ?? false, 'and knows it is drawn below zero, for its dot');
  $Both = new LineChart();
  $Both->AddSeries('up', [2]);
  $Both->AddSeries('down', [3])->Negative();
  $Both->AddSeries('up2', [1]);
  Same([[2.0], [-3.0], [3.0]], Tops($Both->Stacked()), 'stacked: above and below zero apart');
  $Area = (string)(new LineChart())->Area()->Stacked()->FromRows([['m' => 'a', 'x' => 1, 'y' => 2], ['m' => 'b', 'x' => 2, 'y' => 2]], 'm', ['x', 'y']);
  Same(2, preg_match_all('/<path d="M[^"]*L[^"]*Z" fill="[^"]*" fill-opacity="0.4"/', $Area), 'stacked areas: each fills down to the one below, at 0.4');
  // A series at zero stacked on another: its edge lies on the other's top, so the edges go top series first and the
  // lower band's own edge ends up on top; the fills stay bottom-up
  $Zero = new LineChart();
  $Zero->Area()->Stacked();
  $Zero->AddSeries('low', [3, 4, 5]);
  $Zero->AddSeries('zero', [0, 0, 0]);
  $Zero->AddSeries('high', [1, 1, 1]);
  preg_match_all('/<g data-series="(\d)"><path d="[^"]*" (fill="[^"]*" fill-opacity="0.4"|fill="none" stroke)/', (string)$Zero, $Found);
  Same(['0', '1', '2', '2', '1', '0'], $Found[1], 'stacked: fills bottom-up, then edges from the top series down');
  Same(3, count(array_filter($Found[2], fn($Kind) => str_starts_with($Kind, 'fill="none"'))), 'stacked: each series has its edge');
  $Plain = new LineChart();
  $Plain->AddSeries('a', [1, 2]);
  $Plain->AddSeries('b', [2, 3]);
  preg_match_all('/<g data-series="(\d)">/', (string)$Plain, $Found);
  Same(['0', '1'], $Found[1], 'not stacked: one group per series, in order');
  $Codes = new LineChart();
  foreach (['200', '404', '503', 'other'] as $Code)
  {
    $Codes->AddSeries($Code, [1, 2]);
  }
  $Codes->Override('/^2/', Color: '#56a64b')->Override('/^5/', Color: '#f2495c', Dashed: true)->Override('other', Width: 1);
  $Data = HoverData($Html = (string)$Codes);
  Same(['#56a64b', '#eb6834', '#f2495c'], array_slice(array_column($Data['series'], 'c'), 0, 3), 'Override(): colours by name or pattern');
  Check(str_contains($Html, 'stroke="#f2495c" stroke-width="1" vector-effect="non-scaling-stroke" stroke-dasharray="6 4"')
    && str_contains($Html, 'stroke-width="1" vector-effect="non-scaling-stroke" stroke-linejoin'), 'dashed, and a width (on screen, non-scaling)');
  Check(str_contains($Html, '<line vector-effect="non-scaling-stroke" x1='), 'grid lines keep their width on screen');
  Check(preg_match('/<svg [^>]*data-plot="[\d.]+ 10 628 214"/', $Html) === 1, 'the svg names its plot box for the hover script\'s layout');
  Check(str_contains($Html, '<text class="sc-x-label"') && str_contains($Html, '<text class="sc-y-label"'), 'x and y labels are marked');
  Check(!str_contains((string)(new PieChart())->FromRows([['d' => 'a', 'n' => 1]], 'd', 'n'), 'data-plot'), 'a pie has no plot box');
  $Js = file_get_contents(__DIR__ . '/../php/Chart/standalone.js');
  Check(str_contains($Js, 'let stretch = Math.max(1, width / saved.view[2]);') && str_contains($Js, 'new MutationObserver(')
    && str_contains($Js, 'resized.observe(drawing)'), 'the hover script keeps chart text at its size on screen, for charts loaded later too');
  Check(str_contains($Js, 'const placeLabels = function (drawing, saved, plot, stretch)') && str_contains($Js, "drawing.setAttribute('height', (across * view[3] / view[2]).toFixed(2));"),
    'and lays the labels out in screen pixels around the plot, trimming the drawing');
  $Cpu = new LineChart();
  $Cpu->AddSeries('cpu', [20, 95]);
  Same(2, substr_count((string)(clone $Cpu)->Thresholds([0 => 'green', 80 => 'orange', 90 => 'red']), 'class="sc-threshold"'), 'threshold lines at 80 and 90, none at the lowest');
  Same(3, substr_count((string)$Cpu->Thresholds([0 => 'green', 80 => 'orange', 90 => 'red'], 'area'), 'class="sc-threshold"'), 'threshold bands: one per threshold');

  // Legend('table'): a row per series with its calculations, sorted, beside or under the drawing
  $Jobs = new LineChart();
  $Jobs->AddSeries('beta', [1, 5, 3]);
  $Jobs->AddSeries('alpha', [2, 2, 9]);
  $Jobs->AddSeries('gamma', [4, null, null]);
  $Jobs->AddSeries('empty', [null, null, null]);
  $Html = (string)$Jobs->Legend('table', ['last', 'max', 'mean', 'min', 'sum'], SortBy: 'last');
  Check(str_contains($Html, 'class="sc-chart-legend sc-legend-table"') && !str_contains($Html, 'sc-legend-right'), 'a table legend, under the drawing by default');
  preg_match_all('/<th scope="col" data-sort="(\w+)"[^>]*>([^<]*)</', $Html, $Heads);
  Same(['name', 'last', 'max', 'mean', 'min', 'sum'], $Heads[1], 'columns in the order given');
  Same(['Name', 'Last *', 'Max', 'Mean', 'Min', 'Total'], $Heads[2], "Grafana's headers");
  preg_match_all('/<tr data-row="(\d+)"/', $Html, $Order);
  Same(['1', '2', '0', '3'], $Order[1], 'sorted by the last value, highest first; a series without values last');
  Check(str_contains($Html, 'data-sort="last" tabindex="0" aria-sort="descending"') && str_contains($Html, '<span class="sc-sort-arrow"> ↓</span>'), 'the sorted column marked');
  Check(preg_match('/<tr data-row="1"[^>]*><td[^>]*><i style="[^"]*background:#eb6834"><\/i>alpha<\/td><td[^>]*data-value="9">9<\/td><td[^>]*data-value="9">9<\/td>' .
    '<td[^>]*data-value="4.3333333333333">4.33<\/td><td[^>]*data-value="2">2<\/td><td[^>]*data-value="13">13<\/td><\/tr>/', $Html) === 1,
    "a row: a line swatch in its series' colour (by position, not by the sort), last, max, mean, min and total");
  Check(preg_match('/<tr data-row="2"[^>]*>.*?data-value="4">4<\/td>/', $Html) === 1, 'last: the last value there is');
  Check(preg_match('/<tr data-row="3"[^>]*><td[^>]*>.*?empty<\/td><td[^>]*data-value="">–<\/td>/', $Html) === 1, 'no values: a dash');
  Check(str_contains($Html, 'class="sc-legend-item" data-series="0" role="button" tabindex="0" aria-pressed="true"'), 'a row hides or shows its series');
  Check(str_contains($Html, 'text-align:right" data-value') && str_contains($Html, 'font-variant-numeric:tabular-nums') && str_contains($Html, 'position:sticky;top:0'),
    'values right-aligned in tabular figures, the header staying on top');
  Check(str_contains($Html, 'overflow:auto;max-height:96px'), 'under the drawing: at most 40% of its height, scrolling');
  preg_match_all('/<tr data-row="(\d+)"/', (string)(clone $Jobs)->Legend('table', ['max'], SortBy: 'name', Desc: false), $Order);
  Same(['1', '0', '3', '2'], $Order[1], 'by name, A to Z');
  preg_match_all('/<tr data-row="(\d+)"/', (string)(clone $Jobs)->Legend('table', ['max']), $Order);
  Same(['0', '1', '2', '3'], $Order[1], 'no sort: the series\' order');
  Check(str_contains((string)(clone $Jobs)->Legend('table', ['last'])->Format(fn(float $V) => $V . ' ms'), '>3 ms</td>'), 'values as Format() writes them');

  // Beside the drawing: the legend as wide as its content needs in pixels (longest name, widest values, headers), held
  // between 19% and 41% of the real width; the drawing drawn at what Size() leaves (taking it as pixels) and filling
  // the rest on the page; as tall as the drawing, scrolling
  $Right = (clone $Jobs)->Legend('table', ['last'], Placement: 'right');
  $Html = (string)$Right;
  // Five-letter names and one value column: 21 + 5 x 7.2 + 16 + 16 for the name, 6 x 7.5 + 16 for "Last *": 150 of 640 px
  Check(str_contains($Html, '<div class="sc-chart-body sc-legend-right" style="display:flex;align-items:stretch;gap:.75rem"><div style="flex:1 1 0;min-width:0"><svg')
    && str_contains($Html, 'viewBox="0 0 484 240" width="484"'), 'right: the drawing takes what the short legend leaves, at the chart\'s height');
  Check(preg_match('/data-plot="[\d.]+ 10 472 214"/', $Html) === 1, 'its plot box in the drawing, for the hover script');
  Check(str_contains($Html, 'flex:0 0 150px;min-width:19%;max-width:41%;position:relative') && str_contains($Html, '<div class="sc-legend-scroll" style="position:absolute;inset:0;overflow:auto">'),
    'and the legend beside it, as wide as its content, scrolling inside the drawing\'s height');
  Same(212, $Right->Resolution(), 'points to fetch for the drawing\'s width');
  // Long names: at most 41% (a longer name ends in an ellipsis), the drawing 58%
  $Long = new LineChart();
  $Long->AddSeries('a rather long series name that will not fit beside the drawing', [1, 2]);
  $Long->AddSeries('short', [3, 4]);
  $Html = (string)$Long->Legend('table', ['last', 'max'], Placement: 'right');
  Check(preg_match('/flex:0 0 (\d+)px;min-width:19%;max-width:41%/', $Html, $Basis) === 1 && (int)$Basis[1] > 0.41 * 640
    && str_contains($Html, 'viewBox="0 0 371 240" width="371"'), 'long names: wider than 41%, so held at 41% and drawn at 58%');
  Check(str_contains($Html, ';width:100%;max-width:0;overflow:hidden;text-overflow:ellipsis" title="a rather long series name'), 'the name cut with an ellipsis, in full on hover');
  Same(155, $Long->Resolution(), 'fewer points to fetch for the narrower drawing');
  // Tiny names and values: at least 19%
  $Tiny = new LineChart();
  $Tiny->AddSeries('a', [1]);
  Check(str_contains((string)$Tiny->Legend('table', ['max'], Placement: 'right'), 'flex:0 0 101px;min-width:19%;max-width:41%'), 'tiny content: its pixels (the headers wider than a and 1), held at 19% at the least');
  // Before any series (Prometheus asks Resolution() before the data): the most the legend may take
  Same(155, (new LineChart())->Legend('table', ['last'], Placement: 'right')->Resolution(), 'no series yet: the drawing as narrow as it can be');
  // A sorted column's arrow counts in its header's width
  $Sorted = (string)(clone $Jobs)->Legend('table', ['last'], SortBy: 'last', Placement: 'right');
  Check(str_contains($Sorted, 'flex:0 0 165px;min-width:19%'), 'the sort arrow widens its header');
  Check(str_contains($Sorted, 'style="width:100%;height:auto" role="img"'), 'beside the legend the drawing fills what the legend leaves');
  Check(str_contains((string)(clone $Jobs)->Legend('table', ['last']), 'style="max-width:100%;height:auto" role="img"'), 'under it, as before');
  // A nominal Size() (1web: 1000 units a full-width panel, so 333 a third): still the legend's own pixels, not 41% of 333
  $Nominal = (string)(clone $Jobs)->Size(333, 240)->Legend('table', ['last'], Placement: 'right');
  Check(str_contains($Nominal, 'flex:0 0 150px;min-width:19%;max-width:41%') && str_contains($Nominal, 'viewBox="0 0 193 240"'),
    'a nominal Size(): the legend in pixels, the drawing drawn at 58% of it and stretched');
  // Before any series: the most
  Check(str_contains((string)(new LineChart())->Legend('table', ['last'], Placement: 'right'), 'flex:0 0 41%;min-width:19%;max-width:41%')
    || !str_contains((string)(new LineChart())->Legend('table', ['last'], Placement: 'right'), 'sc-legend-table'), 'no series yet: the most, or no legend');

  // Top(): Others last, whatever the sort, in grey, with the summed values
  $Many = new LineChart();
  foreach ([1, 2, 30, 40] as $Number => $Value)
  {
    $Many->AddSeries("s$Number", [$Value, $Value]);
  }
  $Html = (string)$Many->Top(1)->Legend('table', ['last', 'sum'], SortBy: 'last');
  preg_match_all('/<tr data-row="(\d+)"( data-others="")?/', $Html, $Order);
  Same(['0', '1'], $Order[1], 'the kept series, then Others');
  Check(str_contains($Html, '<tr data-row="1" data-others=""') && preg_match('/data-others=""[^>]*color:var\(--bs-secondary-color[^>]*><td[^>]*><i style="[^"]*background:#9a9aa0"><\/i>Others \(3\)<\/td><td[^>]*data-value="33">33<\/td><td[^>]*data-value="66">66</', $Html) === 1,
    'Others (3): grey, its values summed');

  // Bars over time, OpenSeries() rows, list and none
  $Bars = (new BarChart())->FromRows([['m' => 'Jan', 'a' => 1, 'b' => 2], ['m' => 'Feb', 'a' => 3, 'b' => 1]], 'm', ['a' => 'A', 'b' => 'B']);
  Check(str_contains((string)(clone $Bars)->Legend('table', ['sum']), '<td style="padding:.2rem .5rem;white-space:nowrap;border-bottom:1px solid var(--bs-border-color-translucent,rgba(0,0,0,.08));text-align:right" data-value="4">4</td>'),
    'a bar chart: the same table');
  InApp(true);
  $Html = (string)(clone $Bars)->Legend('table', ['sum'])->OpenSeries('/s.php?s={series}', Open::SidePanel);
  InApp(false);
  Check(str_contains($Html, '<tr data-row="0" class="sc-legend-item oc-link" data-href="/s.php?s=A"') && !str_contains($Html, 'data-series="0" role="button"'),
    "with OpenSeries() a row opens the series' page instead of hiding it");
  Check(str_contains((string)(clone $Bars)->Legend('list'), 'class="sc-chart-legend" style="display:flex'), "Legend('list'): the list, as before");
  Check(!str_contains((string)(clone $Bars)->Legend('none'), 'sc-chart-legend') && !str_contains((string)(clone $Bars)->NoLegend(), 'sc-chart-legend'), "Legend('none') and NoLegend(): none");
  foreach ([
    'an unknown mode' => fn() => (new LineChart())->Legend('grid'),
    'an unknown calculation' => fn() => (new LineChart())->Legend('table', ['median']),
    'a sort by a column not shown' => fn() => (new LineChart())->Legend('table', ['last'], SortBy: 'max'),
    'values on a list' => fn() => (new LineChart())->Legend('list', ['last']),
    'a placement on a list' => fn() => (new LineChart())->Legend('list', Placement: 'right'),
    'an unknown placement' => fn() => (new LineChart())->Legend('table', Placement: 'left'),
    'a table on a pie' => fn() => (new PieChart())->Legend('table', ['sum']),
  ] as $What => $Call)
  {
    try
    {
      $Call();
      $Threw = false;
    }
    catch (InvalidArgumentException)
    {
      $Threw = true;
    }
    Check($Threw, "Legend(): $What is refused");
  }
  $Js = file_get_contents(__DIR__ . '/../php/Chart/standalone.js');
  Check(str_contains($Js, 'window.scChartSortLegend = sortLegend;') && str_contains($Js, ".sc-chart svg.sc-dim [data-series]:not(.sc-lit){opacity:.15}")
    && str_contains($Js, "row.hasAttribute('data-others')"), 'the script sorts a table legend (Others last) and dims the other series');
  Check(str_contains(file_get_contents(__DIR__ . '/../public/js/app.js'), 'window.scChartSortLegend(this.querySelector'), 'a refreshed dashboard keeps its legends\' sort');

  // A hidden series drops out of a stack: the runs, dots and bars say where they are, for standalone.js to stack again
  $Stack = (new LineChart())->Area()->Stacked();
  $Stack->AddSeries('a', [1, 2, null, 4]);
  $Stack->AddSeries('b', [3, 4, 5, 6]);
  $Html = (string)$Stack;
  Check(str_contains($Html, 'fill-opacity="0.4" stroke="none" data-run="0 1"/>') && str_contains($Html, 'stroke-linecap="round" data-run="0 1"/>'),
    'stacked: an area and its line name the labels of their run');
  Check(preg_match('/<circle cx="\d+" cy="\d+" r="2.5" fill="#2a78d6" data-i="3"\/>/', $Html) === 1, 'and a point on its own its label');
  Check(!str_contains((string)(new LineChart())->Area()->FromRows([['m' => 'a', 'x' => 1], ['m' => 'b', 'x' => 2]], 'm', 'x'), 'data-run'), 'not stacked: nothing to stack again');
  $Html = (string)(new BarChart())->Stacked()->FromRows([['m' => 'a', 'x' => 1, 'y' => 2]], 'm', ['x', 'y']);
  Check(preg_match('/<g class="sc-stack" data-y="0 [\d.]+ \d+ \d+"><\/g>/', $Html) === 1 && str_contains($Html, 'data-i="0" data-v="2"'), 'stacked bars: the scale, and each bar\'s label and value');
  $Js = file_get_contents(__DIR__ . '/../php/Chart/standalone.js');
  Check(str_contains($Js, 'window.scChartRestack = restack;') && str_contains($Js, 'const tops = positions(chart, index, hidden);'),
    'the script stacks again without hidden series, the hover dots too (in a browser: test-browser.php)');
  Check(str_contains(file_get_contents(__DIR__ . '/../public/js/app.js'), "window.scChartRestack(this.closest('.sc-chart'))"), 'app.js stacks again on a legend click');

  // Smooth(): monotone cubic curves, through every point and never past one
  // A path 'M x yC x y x y x y...' as its cubic segments [[x0, y0], [x1, y1], [x2, y2], [x3, y3]]
  function Segments(string $Path): array
  {
    preg_match_all('/-?[\d.]+/', $Path, $Found);
    $Numbers = array_map('floatval', $Found[0]);
    $Segments = [];
    for ($At = 2; $At + 6 <= count($Numbers); $At += 6)
    {
      $Segments[] = [[$Numbers[$At - 2], $Numbers[$At - 1]], [$Numbers[$At], $Numbers[$At + 1]], [$Numbers[$At + 2], $Numbers[$At + 3]], [$Numbers[$At + 4], $Numbers[$At + 5]]];
    }
    return $Segments;
  }
  // Whether every segment stays between its two ends (sampled), x always going one way
  function NoOvershoot(string $Path): bool
  {
    foreach (Segments($Path) as [$P0, $P1, $P2, $P3])
    {
      $Low = min($P0[1], $P3[1]) - 0.05;
      $High = max($P0[1], $P3[1]) + 0.05;
      $LastX = null;
      for ($Step = 0; $Step <= 50; $Step++)
      {
        $T = $Step / 50;
        $U = 1 - $T;
        $X = $U ** 3 * $P0[0] + 3 * $U * $U * $T * $P1[0] + 3 * $U * $T * $T * $P2[0] + $T ** 3 * $P3[0];
        $Y = $U ** 3 * $P0[1] + 3 * $U * $U * $T * $P1[1] + 3 * $U * $T * $T * $P2[1] + $T ** 3 * $P3[1];
        if ($Y < $Low || $Y > $High || ($LastX !== null && ($X - $LastX) * ($P3[0] - $P0[0]) < 0))
        {
          return false;
        }
        $LastX = $X;
      }
    }
    return true;
  }
  // 0, 0, 5, 0, 0 (y grows downwards: zero at 100)
  $Peak = [[0, 100], [10, 100], [20, 50], [30, 100], [40, 100]];
  $Curve = LineChart::SmoothPathOf($Peak);
  Check(str_starts_with($Curve, 'M0 100C'), 'smooth: a curve of cubic segments');
  $Ends = array_map(fn(array $Segment) => $Segment[3], Segments($Curve));
  Same([[10.0, 100.0], [20.0, 50.0], [30.0, 100.0], [40.0, 100.0]], $Ends, 'smooth: through every point');
  Check(NoOvershoot($Curve), 'smooth: 0,0,5,0,0 never above the peak nor below zero');
  Same('M0 100C3.3 100 6.7 100 10 100', substr($Curve, 0, strlen('M0 100C3.3 100 6.7 100 10 100')), 'smooth: between two zeros it stays at zero');
  $Rising = [[0, 100], [10, 98], [20, 60], [30, 59], [40, 10], [50, 0]];
  Check(NoOvershoot(LineChart::SmoothPathOf($Rising)), 'smooth: a rising run keeps rising');
  Check(NoOvershoot(LineChart::SmoothPathOf([[0, 0], [5, 80], [7, 80], [20, 0], [21, 100], [40, 3]])), 'smooth: sharp turns, uneven steps');
  Same(null, LineChart::SmoothPathOf([[0, 1], [5, 2]]), 'smooth: two points stay a straight step');
  Same(null, LineChart::SmoothPathOf([[0, 1], [0, 2], [5, 2]]), 'smooth: points not left to right stay straight');
  $Many = [];
  for ($At = 0; $At <= LineChart::SMOOTH_MOST; $At++)
  {
    $Many[] = [$At / 10, $At % 7];
  }
  Same(null, LineChart::SmoothPathOf($Many), 'smooth: a run of more than SMOOTH_MOST points stays straight');
  Check(LineChart::SmoothPathOf(array_slice($Many, 0, LineChart::SMOOTH_MOST)) !== null, 'smooth: SMOOTH_MOST points are smoothed');
  Check(LineChart::SmoothPathOf($Many, false, 4000) !== null, 'smooth: a higher limit (a wide plot) smooths them');
  $Back = Segments(LineChart::SmoothPathOf($Rising, true));
  Same(array_map(fn(array $Segment) => array_reverse($Segment), array_reverse(Segments(LineChart::SmoothPathOf($Rising)))), $Back,
    'smooth reversed: the same curve from right to left');

  // In a chart: off by default, gaps, one point, the hover
  $Same = fn(string $Html) => preg_replace('/sc[0-9a-f]{8}-/', 'scX-', $Html);
  $Unsmoothed = (string)(new LineChart())->FromRows([['h' => 'a', 'n' => 1], ['h' => 'b', 'n' => 4], ['h' => 'c', 'n' => 2]], 'h', 'n');
  Same($Same($Unsmoothed), $Same((string)(new LineChart())->Smooth(false)->FromRows([['h' => 'a', 'n' => 1], ['h' => 'b', 'n' => 4], ['h' => 'c', 'n' => 2]], 'h', 'n')),
    'Smooth(false): the same drawing as without it');
  $Chart = new LineChart();
  $Chart->AddSeries('n', [0, 0, 5, 0, 0, 3, 1]);
  Check(preg_match('/<path d="M[\d. ]+l[^"C]+" fill="none"/', (string)$Chart) === 1 && !preg_match('/<path d="M[\d. ]+C/', (string)$Chart), 'not smooth: straight steps');
  $Chart = (new LineChart())->Smooth()->Area();
  $Chart->AddSeries('n', [0, 0, 5, 0, 0, 3, 1]);
  $Html = (string)$Chart;
  Check(preg_match('/<path d="(M[\d. ]+C[^"]+)" fill="none"/', $Html, $Found) === 1 && NoOvershoot($Found[1]), 'Smooth(): the line is the curve');
  Check(preg_match('/<path d="M[\d. ]+C[^"]+L[\d. ]+L[\d. ]+Z" fill="url\(#/', $Html) === 1, 'Smooth()->Area(): the gradient fill under the same curve');
  Check(str_contains($Html, 'vector-effect="non-scaling-stroke"'), 'smooth lines keep their width on screen');
  Same([0, 0, 5, 0, 0, 3, 1], HoverData($Html)['v'][0], 'the hover keeps the real points');
  $Gappy = (new LineChart())->Smooth()->Dots(false);
  $Gappy->AddSeries('n', [1, 2, 3, null, 4, 5, 6, null, 7]);
  $Html = (string)$Gappy;
  Same(2, preg_match_all('/<path d="M[\d. ]+C[^"]+" fill="none"/', $Html), 'smooth with gaps: each run a curve of its own');
  Same(1, preg_match_all('/<circle cx="\d+" cy="\d+" r="2.5"/', $Html), 'and a point on its own a dot');

  // 240 points on a small nominal Size() (more points than its pixels, as 1web's panels): still smoothed
  $Dense = (new LineChart())->Smooth()->Dots(false)->Size(240, 160);
  $Dense->AddSeries('n', array_map(fn(int $At) => ($At * 37) % 11, range(0, 239)));
  $Html = (string)$Dense;
  Check(preg_match('/<path d="(M[\d. ]+C[^"]+)" fill="none"/', $Html, $Found) === 1, '240 points on a small plot: smoothed');
  Same(239, count(Segments($Found[1] ?? 'M0 0')), 'and the curve has a segment per step');
  $Huge = (new LineChart())->Smooth()->Dots(false)->Size(240, 160);
  $Huge->AddSeries('n', array_map(fn(int $At) => ($At * 37) % 11, range(0, LineChart::SMOOTH_MOST)));
  Check(!preg_match('/<path d="M[\d. ]+C/', (string)$Huge), 'more than SMOOTH_MOST points: straight');
  $Bands = (new LineChart())->Smooth()->Area()->Stacked()->Size(240, 160);
  $Bands->AddSeries('a', array_map(fn(int $At) => $At % 5, range(0, 239)));
  $Bands->AddSeries('b', array_map(fn(int $At) => $At % 3, range(0, 239)));
  $Data = HoverData((string)$Bands);
  Check(count($Data['sx'] ?? []) === 240 && count(array_unique($Data['sx'])) === 240 && ($Data['most'] ?? 0) >= LineChart::SMOOTH_MOST,
    'stacked smooth: the exact places and the limit go to the script');

  // Stacked: a band's lower edge is the curve of the band below, reversed
  $Bands = (new LineChart())->Smooth()->Area()->Stacked();
  $Bands->AddSeries('a', [1, 4, 2, 0, 3]);
  $Bands->AddSeries('b', [2, 0, 3, 1, 1]);
  $Html = (string)$Bands;
  preg_match_all('/<g data-series="(\d)">(.*?)<\/g>/s', $Html, $Groups, PREG_SET_ORDER);
  $EdgeA = $FillB = null;
  foreach ($Groups as [, $Number, $Inner])
  {
    if ($Number === '0' && preg_match('/<path d="([^"]+)" fill="none"/', $Inner, $Found))
    {
      $EdgeA = $Found[1];
    }
    if ($Number === '1' && preg_match('/<path d="([^"]+)" fill="#[0-9a-f]+" fill-opacity="0.4"/', $Inner, $Found))
    {
      $FillB = $Found[1];
    }
  }
  $ReversedA = array_map(fn(array $Segment) => array_reverse($Segment), array_reverse(Segments((string)$EdgeA)));
  $LowerB = Segments(substr((string)$FillB, strpos((string)$FillB, 'L') + 1, -1));
  Check($EdgeA !== null && $FillB !== null && str_contains($EdgeA, 'C') && $LowerB === $ReversedA, 'stacked smooth: the band above stands on the curve below');
  Check(strpos($Html, 'data-series="1">' . '<path d="M') < strpos($Html, 'stroke-linecap="round" data-run="0 4"'), 'stacked smooth: fills first, the edges after');
  Same(1, HoverData($Html)['smooth'] ?? null, 'stacked smooth: the script draws a restacked band as curves too');
  $Js = file_get_contents(__DIR__ . '/../php/Chart/standalone.js');
  Check(str_contains($Js, 'let path = pathOf(points, chart.smooth, false, chart.most);') && str_contains($Js, 'chart.sx'), 'the script restacks smooth bands with the same curve, at the exact places');

  // Stacked in the table legend's order: its first row the top band (biggest last value on top), Others the bottom one
  $Ordered = (new LineChart())->Area()->Stacked()->Legend('table', ['last'], SortBy: 'last');
  $Ordered->AddSeries('small', [1, 1]);
  $Ordered->AddSeries('big', [5, 9]);
  $Ordered->AddSeries('middle', [3, 4]);
  Same([[1.0, 1.0], [9.0, 14.0], [4.0, 5.0]], Tops($Ordered), 'stacked by the legend: small at the bottom, middle on it, big on top');
  $Html = (string)$Ordered;
  preg_match_all('/<g data-series="(\d)"><path d="[^"]*" (?:fill="[^"]*" fill-opacity="0.4"|fill="none" stroke)/', $Html, $Found);
  Same(['0', '2', '1', '1', '2', '0'], $Found[1], 'fills bottom-up in the stack order, edges from the top band down');
  Check(preg_match('/<g data-series="1"><path d="[^"]*" fill="#eb6834" fill-opacity="0.4"/', $Html) === 1, 'colours stay by series: big keeps the second colour');
  Same([0, 2, 1], HoverData($Html)['order'] ?? null, 'the script gets the stack order (bottom up), to stack again without a hidden series');
  Check(preg_match_all('/data-row="(\d)"/', $Html, $Rows) && $Rows[1] === ['1', '2', '0'], 'the legend lists them top band first');
  $Name = (new LineChart())->Stacked()->Legend('table', ['last'], SortBy: 'name', Desc: false);
  $Name->AddSeries('b', [1]);
  $Name->AddSeries('a', [1]);
  Same([[1.0], [2.0]], Tops($Name), "'name' ascending: A on top");
  $Unsorted = (new LineChart())->Stacked()->Legend('table', ['last']);
  $Unsorted->AddSeries('small', [1]);
  $Unsorted->AddSeries('big', [5]);
  Same([[1.0], [6.0]], Tops($Unsorted), 'a legend without SortBy: the series\' order');
  Check(!isset(HoverData((string)$Unsorted)['order']), 'and no order for the script (as before)');
  // Top()'s Others: the last legend row, the bottom band
  $Many = (new LineChart())->Stacked()->Top(2, 'last')->Legend('table', ['last'], SortBy: 'last');
  foreach (['a' => 1, 'b' => 7, 'c' => 5, 'd' => 2] as $Name => $Last)
  {
    $Many->AddSeries($Name, [$Last]);
  }
  // Top() keeps b and c (by their last values), then Others (2): a + d = 3
  Same([[15.0], [8.0], [3.0]], Tops($Many), 'Others (3) at the bottom, then c (5), then b (7) on top');
  // StackOrder(): without a table legend, and over it; null keeps the series' order
  $Explicit = (new LineChart())->Stacked()->StackOrder('max');
  $Explicit->AddSeries('low', [1, 2]);
  $Explicit->AddSeries('high', [8, 3]);
  Same([[1.0, 2.0], [9.0, 5.0]], Tops($Explicit), "StackOrder('max'): the highest max on top");
  $Kept = (new LineChart())->Stacked()->Legend('table', ['last'], SortBy: 'last')->StackOrder(null);
  $Kept->AddSeries('small', [1]);
  $Kept->AddSeries('big', [5]);
  Same([[1.0], [6.0]], Tops($Kept), 'StackOrder(null): the series\' order, whatever the legend');
  $Threw = false;
  try
  {
    (new LineChart())->StackOrder('median');
  }
  catch (InvalidArgumentException)
  {
    $Threw = true;
  }
  Check($Threw, 'StackOrder(): only name or a calculation');
  // Percent and Smooth() in the new order
  $Shares = (new LineChart())->Stacked('percent')->StackOrder('last');
  $Shares->AddSeries('a', [1]);
  $Shares->AddSeries('b', [3]);
  Same([[25.0], [100.0]], Tops($Shares), 'percent: a (25%) at the bottom, b (75%) on it');
  $Curved = (new LineChart())->Smooth()->Area()->Stacked()->StackOrder('last', false);
  $Curved->AddSeries('a', [1, 4, 2, 0, 3]);
  $Curved->AddSeries('b', [5, 6, 7, 8, 9]);
  Check(str_contains((string)$Curved, 'C') && HoverData((string)$Curved)['order'] === [1, 0], 'smooth bands stack in the order too (lowest last on top: a over b)');
  // Stacked bars: the same order, bottom up in the document (standalone.js stacks again in that order)
  $Bars = (new BarChart())->Stacked()->Legend('table', ['last'], SortBy: 'last')->FromRows([['m' => 'a', 'x' => 5, 'y' => 1]], 'm', ['x', 'y']);
  $Html = (string)$Bars;
  Check(preg_match_all('/<rect class="sc-bar"[^>]*fill="(#[0-9a-f]+)"/', $Html, $Fills) && $Fills[1] === ['#eb6834', '#2a78d6'], 'stacked bars: y (1) at the bottom, x (5) on top, colours by series');
  $Js = file_get_contents(__DIR__ . '/../php/Chart/standalone.js');
  Check(str_contains($Js, '(chart.order || chart.series.map((series, number) => number)).forEach'), 'the script stacks in the server\'s order');
