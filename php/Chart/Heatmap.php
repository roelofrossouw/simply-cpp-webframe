<?php

  namespace Chart;

  /**
   * A heatmap, like Grafana's: a row per series (a job), a column per stretch of time, each cell's colour from its value
   * (light to dark in one colour, on a log scale by default, or by thresholds); no value (or 0), no cell. The time range is cut into as many columns as fit
   * (Columns(), at most one per four pixels), each holding the sum (or the highest, or the mean) of its points.
   *
   *   $Heat = (new Chart\Heatmap('Job runs'))->Aggregate('sum');
   *   $Prometheus->AddTo($Heat, '60 * sum by (name) (rate(hcq_runs_total[$__rate_interval]))', $Dashboard->Range(), '{{name}}');
   *
   * Equal neighbours in a row are one rectangle, and the pointer reads one block of data (Chart/standalone.js), so a
   * hundred rows stay small. Rows are as many as there are series; Top() keeps the busiest.
   */
  class Heatmap extends Chart
  {
    private const float TOP = 4;
    private const float BOTTOM = 24;
    private const float ROW = 14;
    private const float CHARACTER = 6.2;
    // How many shades the colour comes in (light to dark)
    private const int SHADES = 9;

    private string $Color = '#f2495c';
    /** @var array<int|string, string> from => colour, when given */
    private array $Thresholds = [];
    private string $Aggregate = 'sum';
    private string $Scale = 'log';
    private int $Columns = 0;
    private float $Left = 80;

    public function __construct(?string $Title = null, ?string $ID = null)
    {
      parent::__construct($Title, $ID);
      $this->Time();
      $this->ShowLegend = false;
    }

    /**
     * The colour of the highest value (lighter shades of it below); '#f2495c' (red) by default
     */
    public function Color(string $Color): static
    {
      $this->Color = $Color;
      return $this;
    }

    /**
     * Colour by ranges instead ([0 => green, 10 => orange, 50 => red], as BarGauge's)
     *
     * @param array<int|string, string> $Thresholds
     */
    public function Thresholds(array $Thresholds): static
    {
      ksort($Thresholds, SORT_NUMERIC);
      $this->Thresholds = $Thresholds;
      return $this;
    }

    /**
     * What a cell holds of its points: 'sum' (the default: runs per stretch), 'max' or 'mean'
     */
    public function Aggregate(string $How): static
    {
      if (!in_array($How, ['sum', 'max', 'mean'], true))
      {
        throw new \InvalidArgumentException("Aggregate(): sum, max or mean, not '$How'");
      }
      $this->Aggregate = $How;
      return $this;
    }

    /**
     * How shades follow values: 'log' (the default: counts that differ by orders of magnitude all show), 'linear', or
     * 'row' (each row from its own lowest to its highest: when each row is in a pattern of its own)
     */
    public function Scale(string $Scale): static
    {
      if (!in_array($Scale, ['log', 'linear', 'row'], true))
      {
        throw new \InvalidArgumentException("Scale(): log, linear or row, not '$Scale'");
      }
      $this->Scale = $Scale;
      return $this;
    }

    /**
     * How many columns (stretches of time); by default as many as fit, one per four pixels, no more than the points
     */
    public function Columns(int $Columns): static
    {
      $this->Columns = max(1, $Columns);
      return $this;
    }

    protected function Prepare(): void
    {
      $this->Height = (int)ceil(self::TOP + max(1, count($this->Series)) * self::ROW + self::BOTTOM);
    }

    protected function PlotBox(): ?array
    {
      return [$this->Left, self::TOP, $this->Left + $this->PlotWidth(), $this->Height - self::BOTTOM];
    }

    private function PlotWidth(): float
    {
      return $this->Width - $this->Left - 12;
    }

    protected function Draw(): string
    {
      $Count = count($this->Labels);
      $Widest = max(1, max(array_map(fn(Series $Series) => mb_strlen($Series->Name), $this->Series ?: [new Series('')])));
      $this->Left = min($this->Width * 0.35, ceil($Widest * self::CHARACTER) + 12);
      $Fits = (int)floor(($this->Left - 12) / self::CHARACTER);
      $First = min($this->Times);
      $End = max($this->Times) + TimeTicks::Step($this->Times);
      $Columns = $this->Columns ?: (int)min($Count, max(1, floor($this->PlotWidth() / 4)));
      $Span = ($End - $First) / $Columns;
      // Each point into its column; per row and column the sum, highest or mean
      $Cells = [];
      $Low = INF;
      $RowLow = [];
      $RowHigh = [];
      $High = -INF;
      foreach ($this->Series as $Number => $Series)
      {
        $Sums = [];
        $Counts = [];
        $Highs = [];
        for ($Index = 0; $Index < $Count; $Index++)
        {
          $Value = $Series->Values[$Index] ?? null;
          if ($Value === null)
          {
            continue;
          }
          $Column = (int)min($Columns - 1, floor(($this->Times[$Index] - $First) / $Span));
          $Sums[$Column] = ($Sums[$Column] ?? 0.0) + $Value;
          $Counts[$Column] = ($Counts[$Column] ?? 0) + 1;
          $Highs[$Column] = max($Highs[$Column] ?? -INF, $Value);
        }
        foreach ($Sums as $Column => $Sum)
        {
          $Cell = match ($this->Aggregate)
          {
            'sum' => $Sum,
            'max' => $Highs[$Column],
            'mean' => $Sum / $Counts[$Column],
          };
          if ($Cell == 0.0)
          {
            continue;
          }
          $Cells[$Number][$Column] = $Cell;
          $Low = min($Low, $Cell);
          $High = max($High, $Cell);
          $RowLow[$Number] = min($RowLow[$Number] ?? INF, $Cell);
          $RowHigh[$Number] = max($RowHigh[$Number] ?? -INF, $Cell);
        }
      }
      $Width = $this->PlotWidth() / $Columns;
      $Svg = '';
      foreach ($this->Series as $Number => $Series)
      {
        $Y = self::TOP + $Number * self::ROW;
        $Svg .= '<text class="sc-y-label" x="' . self::Coordinate($this->Left - 6) . '" y="' . self::Coordinate($Y + self::ROW / 2 + 4) . '" text-anchor="end" fill="#444" font-size="10">' .
          self::Escape(self::Fit($Series->Name, (int)floor($Fits * 11 / 10))) . '</text>';
        // A run of neighbours in the same shade is one rectangle
        $Run = null;
        for ($Column = 0; $Column <= $Columns; $Column++)
        {
          $Fill = $Column < $Columns && isset($Cells[$Number][$Column]) ? ($this->Scale === 'row'
            ? $this->Shade($Cells[$Number][$Column], $RowLow[$Number], $RowHigh[$Number]) : $this->Shade($Cells[$Number][$Column], $Low, $High)) : null;
          if ($Run !== null && $Fill !== $Run[1])
          {
            $X = $this->Left + $Run[0] * $Width;
            $Svg .= '<rect x="' . self::Coordinate($X) . '" y="' . self::Coordinate($Y + 1) . '" width="' . self::Coordinate(($Column - $Run[0]) * $Width) .
              '" height="' . self::Coordinate(self::ROW - 2) . '" ' . $Run[1] . '/>';
            $Run = null;
          }
          if ($Fill !== null && $Run === null)
          {
            $Run = [$Column, $Fill];
          }
        }
      }
      // The time axis
      $Svg .= '<g class="sc-axis" fill="#666">';
      foreach (TimeTicks::Between($First, $End, max(2, (int)floor($this->PlotWidth() / 70))) as [$Time, $Text])
      {
        $Svg .= '<text class="sc-x-label" x="' . self::Coordinate($this->Left + $this->PlotWidth() * ($Time - $First) / max(1, $End - $First)) . '" y="' .
          self::Coordinate($this->Height - 8) . '" text-anchor="middle">' . self::Escape($Text) . '</text>';
      }
      $Svg .= '</g>';
      return $Svg . $this->HoverArea($Cells, $Columns, $First, $Span);
    }

    // A cell's fill: by thresholds, else the colour at one of SHADES opacities from its place between low and high
    private function Shade(float $Value, float $Low, float $High): string
    {
      if ($this->Thresholds)
      {
        $Color = reset($this->Thresholds);
        foreach ($this->Thresholds as $From => $Threshold)
        {
          if ($Value >= (float)$From)
          {
            $Color = $Threshold;
          }
        }
        return 'fill="' . self::Escape($Color) . '"';
      }
      if ($this->Scale === 'log' && $Low > 0)
      {
        $Share = $High > $Low ? (log($Value) - log($Low)) / (log($High) - log($Low)) : 1.0;
      }
      else
      {
        $Share = $High > $Low ? ($Value - $Low) / ($High - $Low) : 1.0;
      }
      $Step = (int)round($Share * (self::SHADES - 1));
      return 'fill="' . self::Escape($this->Color) . '" fill-opacity="' . round(0.12 + 0.88 * $Step / (self::SHADES - 1), 2) . '"';
    }

    // One see-through area over the cells for the hover, and one block of data: the rows' names, the columns' times,
    // and each row's values by column as runs
    private function HoverArea(array $Cells, int $Columns, int $First, float $Span): string
    {
      $Heads = [];
      for ($Column = 0; $Column < $Columns; $Column++)
      {
        $From = (int)round($First + $Column * $Span);
        $Until = (int)round($First + ($Column + 1) * $Span);
        $Heads[] = date('D j M H:i', $From) . ' – ' . date(date('Ymd', $From) === date('Ymd', $Until) ? 'H:i' : 'D j M H:i', $Until);
      }
      $Values = [];
      $Texts = [];
      foreach ($this->Series as $Number => $Series)
      {
        $Row = [];
        for ($Column = 0; $Column < $Columns; $Column++)
        {
          $Row[] = isset($Cells[$Number][$Column]) ? $this->Value($Cells[$Number][$Column]) : null;
        }
        $Texts[] = LineChart::Runs($Row);
      }
      $Data = ['heat' => true, 'rows' => array_map(fn(Series $Series) => $Series->Name, $this->Series), 'heads' => $Heads, 'f' => $Texts,
        'left' => round($this->Left, 1), 'width' => round($this->PlotWidth(), 1), 'top' => self::TOP, 'row' => self::ROW, 'columns' => $Columns,
        'color' => $this->Color];
      $Json = json_encode($Data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
      return '<rect class="sc-heat-area" x="' . self::Coordinate($this->Left) . '" y="' . self::TOP . '" width="' . self::Coordinate($this->PlotWidth()) .
        '" height="' . (count($this->Series) * self::ROW) . '" fill="#888" fill-opacity="0" role="img" aria-label="' . self::Escape(($this->Series ? count($this->Series) : 0) . ' rows over time') . '"/>' .
        '<script type="application/json" class="sc-chart-data">' . $Json . '</script>';
    }
  }
