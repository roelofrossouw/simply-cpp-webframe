<?php

  namespace Chart;

  /**
   * A state timeline, like Grafana's: a row per series (a server, a poller, a listener), each a band along the time axis
   * coloured by its state: by default 1 is a green "Up" and 0 a red "Down", from Prometheus' up{} or any 0/1 series.
   * Neighbours in the same state are one stretch; the pointer on a stretch shows its state and how long it lasted, and a
   * stretch or a row's name can open a page (Open(), OpenSeries()).
   *
   *   $Up = new Chart\StateTimeline('Up');
   *   $Prometheus->AddTo($Up, 'up{job=~"oneapi|streamvms"}', $Dashboard->Range(), '{{instance}}');
   *   $Up->OpenSeries('/servers/view.php?name={instance}', Chart\Open::SidePanel);
   *
   * States(): other values and colours ([2 => ['Degraded', BarGauge::ORANGE]]); Thresholds(): colour by ranges instead,
   * the value as the text.
   */
  class StateTimeline extends Chart
  {
    private const float TOP = 4;
    private const float BOTTOM = 24;
    private const float ROW = 22;
    private const float GAP = 4;
    private const float CHARACTER = 6.2;

    /** @var array<string, array{0: string, 1: string}> value => [text, colour] */
    private array $States = ['0' => ['Down', BarGauge::RED], '1' => ['Up', BarGauge::GREEN]];
    /** @var array<int|string, string> from => colour, when given */
    private array $Thresholds = [];
    private float $Left = 80;

    public function __construct(?string $Title = null, ?string $ID = null)
    {
      parent::__construct($Title, $ID);
      $this->TableLegend = false;
      $this->Time();
    }

    /**
     * What each value means: [value => [text, colour]]; by default [0 => ['Down', red], 1 => ['Up', green]]. Values
     * without a state show as themselves, in grey.
     *
     * @param array<int|string, array{0: string, 1: string}> $States
     */
    public function States(array $States): static
    {
      $this->States = [];
      foreach ($States as $Value => $State)
      {
        $this->States[self::Key((float)$Value)] = [(string)$State[0], (string)$State[1]];
      }
      return $this;
    }

    /**
     * Colour by ranges instead of states ([0 => green, 80 => orange, 90 => red], as BarGauge's), the value as the text
     *
     * @param array<int|string, string> $Thresholds
     */
    public function Thresholds(array $Thresholds): static
    {
      ksort($Thresholds, SORT_NUMERIC);
      $this->Thresholds = $Thresholds;
      return $this;
    }

    private static function Key(float $Value): string
    {
      return rtrim(rtrim(number_format($Value, 6, '.', ''), '0'), '.');
    }

    // A value's text and colour
    private function StateOf(float $Value): array
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
        return [$this->Value($Value), $Color];
      }
      return $this->States[self::Key($Value)] ?? [$this->Value($Value), '#adb5bd'];
    }

    protected function PlotBox(): ?array
    {
      return [$this->Left, self::TOP, $this->Left + $this->PlotWidth(), $this->Height - self::BOTTOM];
    }

    private function PlotWidth(): float
    {
      return $this->Width - $this->Left - 12;
    }

    private function X(int $Time): float
    {
      $First = min($this->Times);
      $Last = $this->End();
      return $this->Left + ($Last > $First ? $this->PlotWidth() * ($Time - $First) / ($Last - $First) : 0);
    }

    // Where the last stretch ends: one usual step after the last time
    private function End(): int
    {
      return max($this->Times) + TimeTicks::Step($this->Times);
    }

    private static function Duration(int $Seconds): string
    {
      $Parts = [];
      foreach (['d' => 86400, 'h' => 3600, 'm' => 60] as $Unit => $Size)
      {
        if ($Seconds >= $Size)
        {
          $Parts[] = intdiv($Seconds, $Size) . $Unit;
          $Seconds %= $Size;
        }
      }
      return $Parts ? implode(' ', array_slice($Parts, 0, 2)) : $Seconds . 's';
    }

    // The drawing is as tall as its rows: set before the drawing is made, which states its size first
    protected function Prepare(): void
    {
      $this->Height = (int)ceil(self::TOP + max(1, count($this->Series)) * (self::ROW + self::GAP) + self::BOTTOM);
    }

    protected function Draw(): string
    {
      $Count = count($this->Labels);
      $Widest = max(1, max(array_map(fn(Series $Series) => mb_strlen($Series->Name), $this->Series ?: [new Series('')])));
      $this->Left = min($this->Width * 0.35, ceil($Widest * self::CHARACTER) + 12);
      $Fits = (int)floor(($this->Left - 12) / self::CHARACTER);
      $Step = TimeTicks::Step($this->Times);
      $Svg = '';
      foreach ($this->Series as $Number => $Series)
      {
        $Y = self::TOP + $Number * (self::ROW + self::GAP);
        // The row's name, a link to the series' page with OpenSeries()
        $Name = $this->LinkAttributes(null, $Number, $Series->Name);
        $Name['class'] = trim('sc-row-name ' . ($Name['class'] ?? ''));
        $Svg .= '<text class="sc-y-label" x="' . self::Coordinate($this->Left - 8) . '" y="' . self::Coordinate($Y + self::ROW / 2 + 4) .
          '" text-anchor="end" fill="#444"' . self::Attributes($Name) . '>' . self::Escape(self::Fit($Series->Name, $Fits)) . '</text>';
        // Stretches: neighbours in the same state, until a gap (no value, or more than twice the usual step)
        $From = null;
        $State = null;
        for ($Index = 0; $Index <= $Count; $Index++)
        {
          $Value = $Index < $Count ? ($Series->Values[$Index] ?? null) : null;
          $Key = $Value === null ? null : self::Key($Value);
          $Broken = $From !== null && ($Key !== $State || $this->Times[$Index] - $this->Times[$Index - 1] > 2 * $Step);
          if ($Broken || ($Index === $Count && $From !== null))
          {
            $Until = $Index < $Count && $this->Times[$Index] - $this->Times[$Index - 1] <= 2 * $Step ? $this->Times[$Index] : $this->Times[$Index - 1] + $Step;
            $Svg .= $this->Stretch($Number, $From, $Until, (float)$Series->Values[$From], $Y);
            $From = null;
          }
          if ($Key !== null && $From === null)
          {
            $From = $Index;
            $State = $Key;
          }
        }
      }
      // The time axis
      $Svg .= '<g class="sc-axis" fill="#666">';
      foreach (TimeTicks::Between(min($this->Times), $this->End(), max(2, (int)floor($this->PlotWidth() / 70))) as [$Time, $Text])
      {
        $Svg .= '<text class="sc-x-label" x="' . self::Coordinate($this->X($Time)) . '" y="' . self::Coordinate($this->Height - 8) . '" text-anchor="middle">' . self::Escape($Text) . '</text>';
      }
      return $Svg . '</g>';
    }

    // One stretch in one state: its band, its text when it fits, and its hover (state, from, until, how long)
    private function Stretch(int $Number, int $From, int $Until, float $Value, float $Y): string
    {
      [$Text, $Color] = $this->StateOf($Value);
      $Left = $this->X($this->Times[$From]);
      $Width = max(1.0, $this->X($Until) - $Left);
      $Start = $this->Times[$From];
      $When = date('D j M H:i', $Start) . ' – ' . date(date('Ymd', $Start) === date('Ymd', $Until) ? 'H:i' : 'D j M H:i', $Until);
      $Tip = $this->Series[$Number]->Name . "\n" . $Text . ', ' . $When . ' (' . self::Duration($Until - $Start) . ')';
      $Svg = $this->Item('rect', ['class' => 'sc-state', 'x' => $Left, 'y' => $Y, 'width' => $Width, 'height' => self::ROW, 'rx' => 2,
        'fill' => $Color, 'data-head' => $this->Series[$Number]->Name,
        'data-rows' => json_encode([['n' => $Text, 'c' => $Color, 'v' => $When . ' (' . self::Duration($Until - $Start) . ')']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
        $Tip, $From, $Number);
      if ($Width > mb_strlen($Text) * self::CHARACTER + 10)
      {
        $Svg .= '<text x="' . self::Coordinate($Left + $Width / 2) . '" y="' . self::Coordinate($Y + self::ROW / 2 + 4) . '" text-anchor="middle" fill="#fff" ' .
          'pointer-events="none">' . self::Escape($Text) . '</text>';
      }
      return $Svg;
    }

    /**
     * The legend: the states and their colours (not the series: each is a row of its own)
     */
    protected function DrawLegend(): string
    {
      if ($this->Thresholds)
      {
        return '';
      }
      $Items = '';
      foreach ($this->States as [$Text, $Color])
      {
        $Items .= '<span class="sc-legend-item"><i style="' . self::SWATCH . self::Escape($Color) . '"></i>' . self::Escape($Text) . '</span>';
      }
      return '<div class="sc-chart-legend" style="' . self::LEGEND . '">' . $Items . '</div>';
    }
  }
