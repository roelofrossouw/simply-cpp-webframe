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
   * A query that fails doesn't stop the page: AddTo() puts the reason on the chart (Chart::Message()); Range(),
   * Instant() and Value() throw a RuntimeException for the page to catch.
   */
  class Prometheus
  {
    private string $URL;
    private int $Timeout;

    public function __construct(?string $URL = null, ?int $Timeout = null)
    {
      $URL ??= (string)\Config::Get('prometheus.url', '');
      if ($URL === '')
      {
        throw new \RuntimeException('Prometheus: no address (the settings\' prometheus.url)');
      }
      $this->URL = rtrim($URL, '/');
      $this->Timeout = max(1, $Timeout ?? (int)\Config::Get('prometheus.timeout', 10));
    }

    /**
     * A range query: a series per result, each ['labels' => [name => value], 'points' => [time => value or null]]
     * (NaN and infinities are null: no point)
     *
     * @return array<int, array{labels: array<string, string>, points: array<int, float|null>}>
     */
    public function Range(string $Query, TimeRange $Range): array
    {
      $Data = $this->Call('query_range', ['query' => $this->Intervals($Query, $Range->Step), 'start' => $Range->Start, 'end' => $Range->End,
        'step' => $Range->Step]);
      return self::Matrix($Data);
    }

    /**
     * An instant query (now, or at $At): ['labels' => [...], 'value' => float or null] per result
     *
     * @return array<int, array{labels: array<string, string>, value: float|null}>
     */
    public function Instant(string $Query, ?int $At = null): array
    {
      $Parameters = ['query' => $Query];
      if ($At !== null)
      {
        $Parameters['time'] = $At;
      }
      return self::Vector($this->Call('query', $Parameters));
    }

    /**
     * An instant query's first value (null when it has none), e.g. for a Stat tile
     */
    public function Value(string $Query, ?int $At = null): ?float
    {
      return $this->Instant($Query, $At)[0]['value'] ?? null;
    }

    /**
     * A range query's results as the chart's series, its labels the times (the chart is switched to Time()), each named
     * by $Legend: Grafana's {{label}} form ('{{instance}} {{route}}'), or by default the result's labels. A series
     * keeps its labels for OpenSeries()'s and Open()'s placeholders. When the query fails, the chart says why.
     */
    public function AddTo(Chart $Chart, string $Query, TimeRange $Range, string $Legend = ''): static
    {
      $Chart->Time();
      try
      {
        $Results = $this->Range($Query, $Range);
      }
      catch (\RuntimeException $Problem)
      {
        $Chart->Message($Problem->getMessage());
        return $this;
      }
      foreach ($Results as $Result)
      {
        $Series = $Chart->AddSeries(self::Legend($Legend, $Result['labels']), $Result['points']);
        $Series->Labels = $Result['labels'];
      }
      return $this;
    }

    /**
     * A series' name from its labels: $Format's {{name}}s filled in, or (without a format) the metric's name with its
     * labels, as Prometheus writes them: up{instance="api1", job="oneapi"}
     *
     * @param array<string, string> $Labels
     */
    public static function Legend(string $Format, array $Labels): string
    {
      if ($Format !== '')
      {
        return trim(preg_replace_callback('/\{\{\s*([A-Za-z_][A-Za-z0-9_]*)\s*}}/', fn(array $Match) => $Labels[$Match[1]] ?? '', $Format));
      }
      $Name = $Labels['__name__'] ?? '';
      unset($Labels['__name__']);
      if (empty($Labels))
      {
        return $Name !== '' ? $Name : 'value';
      }
      $Pairs = [];
      foreach ($Labels as $Label => $Value)
      {
        $Pairs[] = $Label . '="' . $Value . '"';
      }
      return $Name . '{' . implode(', ', $Pairs) . '}';
    }

    /**
     * A range query's data (resultType matrix) as series
     *
     * @return array<int, array{labels: array<string, string>, points: array<int, float|null>}>
     */
    public static function Matrix(array $Data): array
    {
      if (($Data['resultType'] ?? '') !== 'matrix')
      {
        throw new \RuntimeException('Prometheus: a range query should give a matrix, not ' . ($Data['resultType'] ?? 'nothing'));
      }
      $Series = [];
      foreach ($Data['result'] ?? [] as $Result)
      {
        $Points = [];
        foreach ($Result['values'] ?? [] as [$Time, $Value])
        {
          $Points[(int)round((float)$Time)] = self::Number($Value);
        }
        $Series[] = ['labels' => array_map('strval', $Result['metric'] ?? []), 'points' => $Points];
      }
      return $Series;
    }

    /**
     * An instant query's data (vector or scalar) as values
     *
     * @return array<int, array{labels: array<string, string>, value: float|null}>
     */
    public static function Vector(array $Data): array
    {
      $Type = $Data['resultType'] ?? '';
      if ($Type === 'scalar')
      {
        return [['labels' => [], 'value' => self::Number($Data['result'][1] ?? null)]];
      }
      if ($Type !== 'vector')
      {
        throw new \RuntimeException('Prometheus: an instant query should give a vector, not ' . ($Type ?: 'nothing'));
      }
      $Values = [];
      foreach ($Data['result'] ?? [] as $Result)
      {
        $Values[] = ['labels' => array_map('strval', $Result['metric'] ?? []), 'value' => self::Number($Result['value'][1] ?? null)];
      }
      return $Values;
    }

    /**
     * $Query with Grafana's $__interval and $__rate_interval as durations for a range in steps of $Step seconds
     */
    public function Intervals(string $Query, int $Step): string
    {
      $Scrape = max(1, (int)\Config::Get('prometheus.scrape_interval', 15));
      return strtr($Query, ['$__rate_interval' => max($Step + $Scrape, 4 * $Scrape) . 's', '$__interval' => $Step . 's']);
    }

    // Prometheus writes values as text; NaN and infinities aren't points
    private static function Number(mixed $Value): ?float
    {
      if (!is_numeric($Value))
      {
        return null;
      }
      $Number = (float)$Value;
      return is_finite($Number) ? $Number : null;
    }

    // GET /api/v1/<path>, its data, or a RuntimeException saying what went wrong
    private function Call(string $Path, array $Parameters): array
    {
      $Curl = curl_init($this->URL . '/api/v1/' . $Path . '?' . http_build_query($Parameters));
      curl_setopt_array($Curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $this->Timeout, CURLOPT_CONNECTTIMEOUT => min(5, $this->Timeout),
        CURLOPT_HTTPHEADER => ['Accept: application/json']]);
      $Body = curl_exec($Curl);
      $Status = curl_getinfo($Curl, CURLINFO_RESPONSE_CODE);
      if ($Body === false || $Status === 0)
      {
        throw new \RuntimeException('Prometheus did not answer (' . curl_error($Curl) . ')');
      }
      $Answer = json_decode((string)$Body, true);
      if (!is_array($Answer))
      {
        throw new \RuntimeException("Prometheus answered $Status without JSON");
      }
      if (($Answer['status'] ?? '') !== 'success')
      {
        throw new \RuntimeException('Prometheus: ' . ($Answer['error'] ?? "status $Status"));
      }
      return $Answer['data'] ?? [];
    }
  }
