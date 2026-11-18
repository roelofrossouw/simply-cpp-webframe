<?php

  namespace Chart;

  /**
   * A chart on axes: the labels along the bottom, the values up the left, with grid lines (LineChart, BarChart)
   */
  abstract class AxisChart extends Chart
  {
    protected const float TOP = 10;
    protected const float RIGHT = 12;
    protected const float BOTTOM = 26;
    private const float CHARACTER = 6.2; // about a character's width at 11 px

    protected Scale $Scale;
    protected float $Left = 40;
    protected bool $Zero = false;

    /**
     * Start the value axis at 0, however far the values are from it (bars always do)
     */
    public function FromZero(): static
    {
      $this->Zero = true;
      return $this;
    }

    /**
     * The values' range, for the scale: [lowest, highest] of what's drawn
     *
     * @return float[]
     */
    protected function Range(): array
    {
      $Values = [];
      foreach ($this->Series as $Series)
      {
        foreach ($Series->Values as $Value)
        {
          if ($Value !== null)
          {
            $Values[] = $Value;
          }
        }
      }
      return empty($Values) ? [0.0, 1.0] : [min($Values), max($Values)];
    }

    /**
     * Where label $Index lies across, in pixels
     */
    abstract protected function X(int $Index): float;

    /**
     * The series, drawn over the axes
     */
    abstract protected function Plot(): string;

    protected function Y(float $Value): float
    {
      return $this->Height - self::BOTTOM - $this->Scale->Share($Value) * ($this->Height - self::BOTTOM - self::TOP);
    }

    protected function PlotWidth(): float
    {
      return $this->Width - $this->Left - self::RIGHT;
    }

    protected function Draw(): string
    {
      [$Min, $Max] = $this->Range();
      $this->Scale = Scale::Around($Min, $Max, max(3, intdiv($this->Height - 36, 40) + 1), $this->Zero);
      $Ticks = $this->Scale->Ticks();
      $Widest = max(array_map(fn(float $Tick) => mb_strlen($this->Tick($Tick)), $Ticks));
      $this->Left = ceil($Widest * self::CHARACTER) + 10;
      return $this->Axes($Ticks) . $this->Plot();
    }

    // The grid lines with their values, and the labels along the bottom (as many as fit)
    private function Axes(array $Ticks): string
    {
      $Svg = '<g class="sc-grid" stroke="#888" stroke-opacity="0.2">';
      foreach ($Ticks as $Tick)
      {
        $Y = self::Coordinate($this->Y($Tick));
        $Svg .= '<line vector-effect="non-scaling-stroke" x1="' . self::Coordinate($this->Left) . '" x2="' . self::Coordinate($this->Width - self::RIGHT) . '" y1="' . $Y . '" y2="' . $Y . '"/>';
      }
      $Svg .= '</g><g class="sc-axis" fill="#666">';
      foreach ($Ticks as $Tick)
      {
        $Svg .= '<text class="sc-y-label" x="' . self::Coordinate($this->Left - 6) . '" y="' . self::Coordinate($this->Y($Tick) + 4) . '" text-anchor="end">' .
          self::Escape($this->Tick($Tick)) . '</text>';
      }
      if ($this->Times !== null && $this->TimeAxis())
      {
        return $Svg . $this->TimeLabels() . '</g>';
      }
      // Every n-th label, so they don't overlap
      $Texts = array_map(fn(int $Index) => $this->AxisLabel($Index), array_keys($this->Labels));
      $Count = count($Texts);
      $Widest = max(1, max(array_map('mb_strlen', $Texts)));
      $Fit = max(1, (int)floor($this->PlotWidth() / ($Widest * self::CHARACTER + 8)));
      $Every = max(1, (int)ceil($Count / $Fit));
      for ($Index = 0; $Index < $Count; $Index += $Every)
      {
        $Svg .= '<text class="sc-x-label" x="' . self::Coordinate($this->X($Index)) . '" y="' . self::Coordinate($this->Height - 8) . '" text-anchor="middle">' .
          self::Escape($Texts[$Index]) . '</text>';
      }
      return $Svg . '</g>';
    }

    protected function PlotBox(): ?array
    {
      return [$this->Left, self::TOP, $this->Width - self::RIGHT, $this->Height - self::BOTTOM];
    }

    /**
     * A label's tooltip: the label, then each series' value there
     */
    protected function LabelTip(int $Index): string
    {
      $Lines = [$this->LabelText($Index)];
      foreach ($this->Series as $Series)
      {
        $Lines[] = $Series->Name . ': ' . $this->Value($Series->Values[$Index] ?? null);
      }
      return implode("\n", $Lines);
    }

    /**
     * Whether the labels lie by their time across the axis (LineChart with Time()); bars keep a slot per label
     */
    protected function TimeAxis(): bool
    {
      return false;
    }

    // A label on the axis: with Time() (bars) the time, as short as the spacing allows
    private function AxisLabel(int $Index): string
    {
      if ($this->Times === null)
      {
        return $this->Labels[$Index];
      }
      return date(TimeTicks::FormatFor(TimeTicks::Step($this->Times)), $this->Times[$Index]);
    }

    // Where a time lies across, in pixels (TimeAxis())
    protected function TimeX(int $Time): float
    {
      $First = min($this->Times);
      $Last = max($this->Times);
      return $this->Left + ($Last > $First ? $this->PlotWidth() * ($Time - $First) / ($Last - $First) : $this->PlotWidth() / 2);
    }

    // Round times along the bottom, as many as fit
    private function TimeLabels(): string
    {
      $First = min($this->Times);
      $Last = max($this->Times);
      $Svg = '';
      foreach (TimeTicks::Between($First, $Last, max(2, (int)floor($this->PlotWidth() / 70))) as [$Time, $Text])
      {
        $Svg .= '<text class="sc-x-label" x="' . self::Coordinate($this->TimeX($Time)) . '" y="' . self::Coordinate($this->Height - 8) . '" text-anchor="middle">' .
          self::Escape($Text) . '</text>';
      }
      return $Svg;
    }
  }
