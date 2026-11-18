<?php

  namespace Chart;

  /**
   * Prometheus' HTTP API as a chart's data: range queries become a LineChart's lines on a time axis, instant queries
   * give the numbers for Stat tiles. Where it is comes from the settings' prometheus section (url, timeout in seconds,
   * default 10), or the constructor.
   *
   *   $Prometheus = new Chart\Prometheus();
   *   $Chart = new Chart\LineChart('Requests per minute');
   *   $Prometheus->AddTo($Chart, '60 * sum by (instance) (rate(sc_rest_requests_total[5m]))', Chart\TimeRange::Last('6h'), '{{instance}}');
   *   $Chart->OpenSeries('/servers/view.php?name={instance}', Chart\Open::SidePanel);
   *   $Tile = new Chart\Stat('Up', $Prometheus->Value('count(up == 1)') ?? '–');
   *
   * Queries may use Grafana's $__interval (the step) and $__rate_interval (the step plus a scrape, at least four
   * scrapes: rate()'s window that grows with the range), e.g. rate(x[$__rate_interval]). The scrape interval is the
   * settings' prometheus.scrape_interval (seconds, default 15).
   *
   * Instant queries over a dashboard's range (Instant($Query, Range: $Range)) may use $__range (its length).
   *
   * A query that fails doesn't stop the page: AddTo() puts the reason on the chart (Chart::Message()); Range(),
   * Instant() and Value() throw a RuntimeException for the page to catch. Instants() and Ranges() run several at once.
   * What's shared with Chart\Loki is in Chart\MetricsApi.
   */
  class Prometheus extends MetricsApi
  {
    protected static function Name(): string
    {
      return 'Prometheus';
    }

    protected static function Section(): string
    {
      return 'prometheus';
    }

    protected static function ApiPath(): string
    {
      return '/api/v1/';
    }

    /**
     * A label's values, sorted (e.g. every instance, for a Dashboard variable); with $Match only those of series it
     * selects ('up{job="oneapi"}'), and of the last $Within ('6h') only, as Prometheus keeps a label's values for as long
     * as it keeps the series
     *
     * @return string[]
     */
    public function LabelValues(string $Label, ?string $Match = null, string $Within = '6h'): array
    {
      $Parameters = ['start' => time() - TimeRange::Seconds($Within), 'end' => time()];
      if ($Match !== null)
      {
        $Parameters['match[]'] = $Match;
      }
      $Values = $this->Call('label/' . rawurlencode($Label) . '/values', $Parameters);
      $Values = array_map('strval', array_values($Values));
      sort($Values, SORT_NATURAL);
      return $Values;
    }

    /**
     * $Query with Grafana's $__interval and $__rate_interval as durations for a range in steps of $Step seconds
     */
    public function Intervals(string $Query, int $Step): string
    {
      return $this->Variables($Query, $Step);
    }

    /**
     * Grafana's variables: $__interval (the step), $__rate_interval (the step plus a scrape, at least four scrapes) and
     * $__range (an instant query's range); the scrape interval is the settings' prometheus.scrape_interval (15 s)
     */
    public function Variables(string $Query, ?int $Step, ?int $Span = null): string
    {
      $Scrape = max(1, (int)self::Setting('prometheus.scrape_interval', 15));
      // An instant query has no step: a scrape, as before
      $Step ??= $Scrape;
      return strtr($Query, ['$__rate_interval' => max($Step + $Scrape, 4 * $Scrape) . 's', '$__interval' => $Step . 's',
        '$__range' => max(1, $Span ?? $Step) . 's']);
    }
  }
