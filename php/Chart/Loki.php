<?php

  namespace Chart;

  /**
   * Loki's HTTP API, on the server only (Loki has no sign-in: never let the browser at it). Metric queries work as
   * Chart\Prometheus' do: Range() and AddTo() for lines, heatmaps and timelines, Instant(), Value(), TableRows() and
   * JoinedRows() for tiles and tables, Instants() and Ranges() for several at once; Lines() gives log lines, for
   * Chart\Logs. Where it is comes from the settings' loki section (url, timeout in seconds, default 30: log queries take
   * seconds), or the constructor.
   *
   *   $Loki = new Chart\Loki();
   *   $Day = Chart\TimeRange::Last('24h');
   *   $Rows = $Loki->JoinedRows([
   *     'requests' => 'topk(10, sum by (name) (count_over_time({job="oneweb",env="production"} | json [$__range])))',
   *     'ms' => 'sum by (name) (sum_over_time({job="oneweb",env="production"} | json | unwrap ms [$__range]))',
   *   ], $Day);
   *   $Lines = $Loki->Lines('{job="oneweb",env="production"} | json | code >= 500', Chart\TimeRange::Last('1h'), 200);
   *
   * Queries may use Grafana's $__interval (a range query's step), $__range (an instant query's range) and $__auto (the
   * step for a range query, the range for an instant one).
   */
  class Loki extends MetricsApi
  {
    public function __construct(?string $URL = null, ?int $Timeout = null)
    {
      parent::__construct($URL, $Timeout ?? (int)self::Setting('loki.timeout', 30));
    }

    protected static function Name(): string
    {
      return 'Loki';
    }

    protected static function Section(): string
    {
      return 'loki';
    }

    protected static function ApiPath(): string
    {
      return '/loki/api/v1/';
    }

    public function Variables(string $Query, ?int $Step, ?int $Span = null): string
    {
      $Range = max(1, $Span ?? $Step ?? 60);
      $Step ??= $Range;
      // $__auto: a range query's step; an instant query has none, so its range
      return strtr($Query, ['$__interval' => $Step . 's', '$__range' => $Range . 's', '$__auto' => $Step . 's']);
    }

    /**
     * Log lines: newest first (or oldest first: $Direction 'forward'), at most $Limit (Loki caps it, 5000 by default),
     * each ['time' => Unix seconds with a fraction, 'ns' => nanoseconds as text, 'labels' => the stream's labels,
     * 'line' => the text, 'fields' => the line's JSON as an array, or null when it isn't JSON]
     *
     * @return array<int, array{time: float, ns: string, labels: array<string, string>, line: string, fields: array|null}>
     */
    public function Lines(string $Query, TimeRange $Range, int $Limit = 100, string $Direction = 'backward'): array
    {
      $Forward = $Direction === 'forward';
      // A range ending within a step of now (a dashboard's, its end rounded down to the step) ends now: the newest lines
      // aren't left out
      $End = $Range->End + $Range->Step >= time() ? max($Range->End, time()) : $Range->End;
      $Data = $this->Call('query_range', ['query' => $this->Variables($Query, $Range->Step), 'start' => $Range->Start, 'end' => $End,
        'limit' => max(1, $Limit), 'direction' => $Forward ? 'forward' : 'backward']);
      if (($Data['resultType'] ?? '') !== 'streams')
      {
        throw new \RuntimeException('Loki: a log query should give streams, not ' . ($Data['resultType'] ?? 'nothing') . ' (a metric query? use Range())');
      }
      $Lines = [];
      foreach ($Data['result'] ?? [] as $Stream)
      {
        $Labels = array_map('strval', $Stream['stream'] ?? []);
        foreach ($Stream['values'] ?? [] as $Entry)
        {
          [$Nanoseconds, $Text] = [(string)$Entry[0], (string)$Entry[1]];
          $Fields = str_starts_with(ltrim($Text), '{') ? json_decode($Text, true) : null;
          $Lines[] = ['time' => self::Seconds($Nanoseconds), 'ns' => $Nanoseconds, 'labels' => $Labels, 'line' => $Text,
            'fields' => is_array($Fields) ? $Fields : null];
        }
      }
      // Streams come one after another: in time order across them, as asked
      usort($Lines, fn(array $A, array $B) => $Forward ? strcmp(str_pad($A['ns'], 20, '0', STR_PAD_LEFT), str_pad($B['ns'], 20, '0', STR_PAD_LEFT))
        : strcmp(str_pad($B['ns'], 20, '0', STR_PAD_LEFT), str_pad($A['ns'], 20, '0', STR_PAD_LEFT)));
      return array_slice($Lines, 0, max(1, $Limit));
    }

    /**
     * How many lines a log query has over a range (for "showing 200 of 4 512"); null when Loki doesn't say
     */
    public function LineCount(string $Query, TimeRange $Range): ?int
    {
      $Value = $this->Value('sum(count_over_time(' . $Query . ' [$__range]))', null, $Range);
      return $Value === null ? null : (int)$Value;
    }

    /**
     * A label's values, sorted; with $Match only those of the streams it selects ('{job="oneweb"}'), over the last
     * $Within ('6h')
     *
     * @return string[]
     */
    public function LabelValues(string $Label, ?string $Match = null, string $Within = '6h'): array
    {
      $Parameters = ['start' => time() - TimeRange::Seconds($Within), 'end' => time()];
      if ($Match !== null)
      {
        $Parameters['query'] = $Match;
      }
      $Values = array_map('strval', array_values($this->Call('label/' . rawurlencode($Label) . '/values', $Parameters)));
      sort($Values, SORT_NATURAL);
      return $Values;
    }

    // Unix seconds (with a fraction) from nanoseconds as text
    private static function Seconds(string $Nanoseconds): float
    {
      $Nanoseconds = ltrim($Nanoseconds, '0') ?: '0';
      $Whole = strlen($Nanoseconds) > 9 ? substr($Nanoseconds, 0, -9) : '0';
      $Part = str_pad(strlen($Nanoseconds) > 9 ? substr($Nanoseconds, -9) : $Nanoseconds, 9, '0', STR_PAD_LEFT);
      return (float)($Whole . '.' . $Part);
    }
  }
