<?php

  namespace Chart;

  /**
   * Lines, one per series, across the labels (months, days, the hours of a time range). A missing value (null) leaves a
   * gap. The pointer on a label shows every series' value there; clicking opens that label's page (Open(), with {label}
   * and the row's columns: a line chart has no {series}).
   */
  class LineChart extends AxisChart
  {
    private bool $Area = false;
    private ?bool $Dots = null;

    /**
     * Fill under each line, lightly
     */
    public function Area(): static
    {
      $this->Area = true;
      return $this;
    }

    /**
     * A dot on each value: on or off (by default on when there are at most 40 labels)
     */
    public function Dots(bool $On = true): static
    {
      $this->Dots = $On;
      return $this;
    }

    protected function X(int $Index): float
    {
      $Count = count($this->Labels);
      return $this->Left + ($Count < 2 ? $this->PlotWidth() / 2 : $this->PlotWidth() * $Index / ($Count - 1));
    }

    protected function Plot(): string
    {
      $Count = count($this->Labels);
      $Dots = $this->Dots ?? $Count <= 40;
      $Bottom = self::Coordinate($this->Y(max($this->Scale->Low, min(0.0, $this->Scale->High))));
      $Svg = '';
      foreach ($this->Series as $Number => $Series)
      {
        $Color = self::Escape($this->ColorOf($Number));
        // A run of values without gaps is a line of its own
        $Runs = [];
        $Run = [];
        for ($Index = 0; $Index < $Count; $Index++)
        {
          $Value = $Series->Values[$Index] ?? null;
          if ($Value === null)
          {
            if ($Run)
            {
              $Runs[] = $Run;
            }
            $Run = [];
            continue;
          }
          $Run[] = [$this->X($Index), $this->Y($Value)];
        }
        if ($Run)
        {
          $Runs[] = $Run;
        }
        $Svg .= '<g data-series="' . $Number . '">';
        foreach ($Runs as $Points)
        {
          $Line = implode(' ', array_map(fn(array $Point) => self::Coordinate($Point[0]) . ',' . self::Coordinate($Point[1]), $Points));
          if ($this->Area && count($Points) > 1)
          {
            $Svg .= '<polygon points="' . self::Coordinate($Points[0][0]) . ',' . $Bottom . ' ' . $Line . ' ' .
              self::Coordinate(end($Points)[0]) . ',' . $Bottom . '" fill="' . $Color . '" fill-opacity="0.12" stroke="none"/>';
          }
          $Svg .= '<polyline points="' . $Line . '" fill="none" stroke="' . $Color . '" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>';
          if ($Dots || count($Points) == 1)
          {
            foreach ($Points as [$X, $Y])
            {
              $Svg .= '<circle cx="' . self::Coordinate($X) . '" cy="' . self::Coordinate($Y) . '" r="2.5" fill="' . $Color . '"/>';
            }
          }
        }
        $Svg .= '</g>';
      }
      return $Svg . $this->Bands();
    }

    // A see-through strip over each label, for its tooltip and link
    private function Bands(): string
    {
      $Count = count($this->Labels);
      $Step = $Count < 2 ? $this->PlotWidth() : $this->PlotWidth() / ($Count - 1);
      $Svg = '<g class="sc-bands">';
      for ($Index = 0; $Index < $Count; $Index++)
      {
        $Left = max($this->Left, $this->X($Index) - $Step / 2);
        $Right = min($this->Left + $this->PlotWidth(), $this->X($Index) + $Step / 2);
        $Svg .= $this->Item('rect', ['class' => 'sc-band', 'x' => $Left, 'y' => self::TOP, 'width' => max(1.0, $Right - $Left),
          'height' => $this->Height - self::TOP - self::BOTTOM, 'fill' => '#888', 'fill-opacity' => '0'], $this->LabelTip($Index), $Index);
      }
      return $Svg . '</g>';
    }
  }
