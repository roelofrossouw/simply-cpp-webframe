<?php

  namespace Chart;

  /**
   * What Prometheus and Loki share: an HTTP API on the server only (neither is ever called from the browser), range
   * queries as a chart's lines, instant queries as numbers and table rows, Grafana's $__ variables, several queries at
   * once, and a failure said on the chart instead of stopping the page (Chart\Prometheus, Chart\Loki)
   */
  abstract class MetricsApi
  {
    protected string $URL;
    protected int $Timeout;

    /**
     * $URL and $Timeout (seconds), else the settings' <section>.url and <section>.timeout (default 10)
     */
    public function __construct(?string $URL = null, ?int $Timeout = null)
    {
      $URL ??= (string)self::Setting(static::Section() . '.url', '');
      if ($URL === '')
      {
        throw new \RuntimeException(static::Name() . ': no address (the settings\' ' . static::Section() . '.url)');
      }
      $this->URL = rtrim($URL, '/');
      $this->Timeout = max(1, $Timeout ?? (int)self::Setting(static::Section() . '.timeout', 10));
    }

    // Its name in messages, its settings' section and where its API is under the address
    abstract protected static function Name(): string;

    abstract protected static function Section(): string;

    abstract protected static function ApiPath(): string;

    /**
     * $Query with Grafana's variables filled in, for a range query in steps of $Step seconds ($Span null) or an instant
     * query over a range $Span seconds long ($Step null)
     */
    abstract public function Variables(string $Query, ?int $Step, ?int $Span = null): string;

    /**
     * A range query: a series per result, each ['labels' => [name => value], 'points' => [time => value or null]]
     * (NaN and infinities are null: no point)
     *
     * @return array<int, array{labels: array<string, string>, points: array<int, float|null>}>
     */
    public function Range(string $Query, TimeRange $Range): array
    {
      return self::Matrix($this->Call(...$this->RangeRequest($Query, $Range)));
    }

    /**
     * An instant query (now, at $At, or at the end of $Range, whose length is then $__range): ['labels' => [...],
     * 'value' => float or null] per result
     *
     * @return array<int, array{labels: array<string, string>, value: float|null}>
     */
    public function Instant(string $Query, ?int $At = null, ?TimeRange $Range = null): array
    {
      return self::Vector($this->Call(...$this->InstantRequest($Query, $At, $Range)));
    }

    /**
     * An instant query's first value (null when it has none), e.g. for a Stat tile
     */
    public function Value(string $Query, ?int $At = null, ?TimeRange $Range = null): ?float
    {
      return $this->Instant($Query, $At, $Range)[0]['value'] ?? null;
    }

    /**
     * Several instant queries at once (in parallel), keyed as given: each key's results as Instant() gives them, or the
     * RuntimeException saying why that query failed (the others still answer)
     *
     * @param array<string|int, string> $Queries
     * @return array<string|int, array|\RuntimeException>
     */
    public function Instants(array $Queries, ?TimeRange $Range = null): array
    {
      $Requests = array_map(fn(string $Query) => $this->InstantRequest($Query, null, $Range), $Queries);
      return array_map(fn($Answer) => $Answer instanceof \RuntimeException ? $Answer : self::Vector($Answer), $this->CallMany($Requests));
    }

    /**
     * Several range queries at once (in parallel), keyed as given: each key's series as Range() gives them, or the
     * RuntimeException saying why that query failed
     *
     * @param array<string|int, string> $Queries
     * @return array<string|int, array|\RuntimeException>
     */
    public function Ranges(array $Queries, TimeRange $Range): array
    {
      $Requests = array_map(fn(string $Query) => $this->RangeRequest($Query, $Range), $Queries);
      return array_map(fn($Answer) => $Answer instanceof \RuntimeException ? $Answer : self::Matrix($Answer), $this->CallMany($Requests));
    }

    /**
     * An instant query's results as table rows (Chart\Table): each result's labels as columns, its value as 'value'; with
     * $Sparkline a range query whose results match by labels (the same set, or those the instant results have) adds
     * each row's points as 'trend', for a sparkline column. With $Range the instant query is taken at its end, over it
     * ($__range).
     *
     * @return array<int, array<string, mixed>>
     */
    public function TableRows(string $Query, ?string $Sparkline = null, ?TimeRange $Range = null, int $Points = 60): array
    {
      $Rows = [];
      foreach ($this->Instant($Query, null, $Range) as $Result)
      {
        $Rows[] = $Result['labels'] + ['value' => $Result['value']];
      }
      if ($Sparkline !== null && $Rows)
      {
        $Range ??= TimeRange::Last('6h', $Points);
        $Range = TimeRange::Between($Range->Start, $Range->End, $Points);
        $Trends = [];
        foreach ($this->Range($Sparkline, $Range) as $Series)
        {
          $Labels = $Series['labels'];
          unset($Labels['__name__']);
          ksort($Labels);
          $Trends[json_encode($Labels)] = array_values($Series['points']);
        }
        foreach ($Rows as &$Row)
        {
          $Labels = array_diff_key($Row, ['value' => 1, '__name__' => 1]);
          ksort($Labels);
          $Row['trend'] = $Trends[json_encode($Labels)] ?? null;
        }
        unset($Row);
      }
      return $Rows;
    }

    /**
     * Instant queries joined into one table row per label set: each query's value under its key ('requests' => ...,
     * 'ms' => ...), the labels as columns, e.g. a top-10 table with a count and a total. The first query's results are
     * the rows (a topk() there keeps the table to its top ten), the others fill in their columns where their labels
     * match; in the first query's order. Queries run in parallel; one that fails throws.
     *
     * @param array<string, string> $Queries column => query
     * @return array<int, array<string, mixed>>
     */
    public function JoinedRows(array $Queries, ?TimeRange $Range = null): array
    {
      $Rows = [];
      $First = true;
      foreach ($this->Instants($Queries, $Range) as $Column => $Results)
      {
        if ($Results instanceof \RuntimeException)
        {
          throw $Results;
        }
        if (!$First)
        {
          foreach ($Rows as &$Row)
          {
            $Row[$Column] ??= null;
          }
          unset($Row);
        }
        foreach ($Results as $Result)
        {
          $Labels = $Result['labels'];
          unset($Labels['__name__']);
          ksort($Labels);
          $Key = json_encode($Labels);
          if (!isset($Rows[$Key]))
          {
            if (!$First)
            {
              continue;
            }
            $Rows[$Key] = $Labels;
          }
          $Rows[$Key][$Column] = $Result['value'];
        }
        if ($First)
        {
          // The first query's results in its order (topk() gives them highest first)
          uasort($Rows, fn(array $A, array $B) => ($B[$Column] ?? -INF) <=> ($A[$Column] ?? -INF));
          $First = false;
        }
      }
      return array_values($Rows);
    }

    /**
     * A range query's results as the chart's series, its labels the times (the chart is switched to Time()), each named
     * by $Legend: Grafana's {{label}} form ('{{instance}} {{route}}'), or by default the result's labels. A series
     * keeps its labels for OpenSeries()'s and Open()'s placeholders. When the query fails, the chart says why. A range
     * with more points than the chart can show apart (Chart::Resolution(): 290 at the default size) is fetched in larger
     * steps.
     */
    public function AddTo(Chart $Chart, string $Query, TimeRange $Range, string $Legend = ''): static
    {
      $Chart->Time();
      // No more points than the chart can show apart: a long range in small steps is fetched in larger ones
      $Points = intdiv($Range->End - $Range->Start, $Range->Step);
      if ($Points > 1.25 * $Chart->Resolution())
      {
        $Range = TimeRange::Between($Range->Start, $Range->End, $Chart->Resolution());
      }
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
        throw new \RuntimeException(static::Name() . ': a range query should give a matrix, not ' . ($Data['resultType'] ?? 'nothing'));
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
        throw new \RuntimeException(static::Name() . ': an instant query should give a vector, not ' . ($Type ?: 'nothing'));
      }
      $Values = [];
      foreach ($Data['result'] ?? [] as $Result)
      {
        $Values[] = ['labels' => array_map('strval', $Result['metric'] ?? []), 'value' => self::Number($Result['value'][1] ?? null)];
      }
      return $Values;
    }

    // The request for a range query: [path, parameters]
    protected function RangeRequest(string $Query, TimeRange $Range): array
    {
      return ['query_range', ['query' => $this->Variables($Query, $Range->Step), 'start' => $Range->Start, 'end' => $Range->End, 'step' => $Range->Step]];
    }

    // The request for an instant query: [path, parameters]
    protected function InstantRequest(string $Query, ?int $At, ?TimeRange $Range): array
    {
      $Parameters = ['query' => $this->Variables($Query, null, $Range !== null ? $Range->End - $Range->Start : null)];
      $At ??= $Range?->End;
      if ($At !== null)
      {
        $Parameters['time'] = $At;
      }
      return ['query', $Parameters];
    }

    // A setting from sc-webframe's settings file, when there is one (not in another app's)
    protected static function Setting(string $Path, mixed $Default): mixed
    {
      return class_exists('Config', false) && method_exists('Config', 'Get') && method_exists('Config', 'AppPage')
        ? \Config::Get($Path, $Default) : $Default;
    }

    // Values come as text; NaN and infinities aren't points
    protected static function Number(mixed $Value): ?float
    {
      if (!is_numeric($Value))
      {
        return null;
      }
      $Number = (float)$Value;
      return is_finite($Number) ? $Number : null;
    }

    // A curl handle for GET <api>/<path>
    private function Handle(string $Path, array $Parameters): \CurlHandle
    {
      // match[] and the like: several values under one name, as name=a&name=b
      $Query = preg_replace('/%5B\d+%5D=/', '%5B%5D=', http_build_query($Parameters));
      $Curl = curl_init($this->URL . static::ApiPath() . $Path . '?' . $Query);
      curl_setopt_array($Curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $this->Timeout, CURLOPT_CONNECTTIMEOUT => min(5, $this->Timeout),
        CURLOPT_HTTPHEADER => ['Accept: application/json']]);
      return $Curl;
    }

    // An answer's data, or a RuntimeException saying what went wrong
    private function Decode(string|false|null $Body, int $Status, string $Error): array|\RuntimeException
    {
      if ($Body === false || $Body === null || $Status === 0)
      {
        return new \RuntimeException(static::Name() . ' did not answer (' . $Error . ')');
      }
      $Answer = json_decode($Body, true);
      if (!is_array($Answer))
      {
        return new \RuntimeException(static::Name() . " answered $Status: " . trim(mb_substr(strip_tags($Body), 0, 200)));
      }
      if (($Answer['status'] ?? '') !== 'success')
      {
        return new \RuntimeException(static::Name() . ': ' . ($Answer['error'] ?? "status $Status"));
      }
      return $Answer['data'] ?? [];
    }

    // GET <api>/<path>: its data, or a RuntimeException
    protected function Call(string $Path, array $Parameters): array
    {
      $Curl = $this->Handle($Path, $Parameters);
      $Body = curl_exec($Curl);
      $Answer = $this->Decode($Body, curl_getinfo($Curl, CURLINFO_RESPONSE_CODE), curl_error($Curl));
      if ($Answer instanceof \RuntimeException)
      {
        throw $Answer;
      }
      return $Answer;
    }

    // Several requests at once, keyed as given: each one's data or its RuntimeException
    protected function CallMany(array $Requests): array
    {
      $Multi = curl_multi_init();
      $Handles = [];
      foreach ($Requests as $Key => [$Path, $Parameters])
      {
        $Handles[$Key] = $this->Handle($Path, $Parameters);
        curl_multi_add_handle($Multi, $Handles[$Key]);
      }
      do
      {
        $Status = curl_multi_exec($Multi, $Running);
        if ($Running)
        {
          curl_multi_select($Multi, 1.0);
        }
      }
      while ($Running && $Status === CURLM_OK);
      $Answers = [];
      foreach ($Handles as $Key => $Curl)
      {
        $Answers[$Key] = $this->Decode(curl_multi_getcontent($Curl), curl_getinfo($Curl, CURLINFO_RESPONSE_CODE), curl_error($Curl));
        curl_multi_remove_handle($Multi, $Curl);
      }
      return $Answers;
    }
  }
