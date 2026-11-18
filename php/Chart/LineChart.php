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

    protected function TimeAxis(): bool
    {
      return true;
    }

    protected function X(int $Index): float
    {
      if ($this->Times !== null)
      {
        return $this->TimeX($this->Times[$Index]);
      }
      $Count = count($this->Labels);
      return $this->Left + ($Count < 2 ? $this->PlotWidth() / 2 : $this->PlotWidth() * $Index / ($Count - 1));
    }

    protected function Plot(): string
    {
      $Count = count($this->Labels);
      $Dots = $this->Dots ?? $Count <= 40;
      $Gap = $this->GapAfter();
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
          // With Time(): no line across a stretch without samples
          if ($Run && $Gap !== null && $this->Times[$Index] - $this->Times[$Index - 1] > $Gap)
          {
            $Runs[] = $Run;
            $Run = [];
          }
          $Run[] = [$this->X($Index), $this->Y($Value)];
        }
        if ($Run)
        {
          $Runs[] = $Run;
        }
        $Svg .= '<g data-series="' . $Number . '">';
        $Lines = [];
        foreach ($Runs as $Points)
        {
          $Line = implode(' ', array_map(fn(array $Point) => self::Coordinate($Point[0]) . ',' . self::Coordinate($Point[1]), $Points));
          if ($this->Area && count($Points) > 1)
          {
            $Svg .= '<polygon points="' . self::Coordinate($Points[0][0]) . ',' . $Bottom . ' ' . $Line . ' ' .
              self::Coordinate(end($Points)[0]) . ',' . $Bottom . '" fill="' . $Color . '" fill-opacity="0.12" stroke="none"/>';
          }
          $Svg .= '<polyline points="' . $Line . '" fill="none" stroke="' . $Color . '" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>';
          $Lines[] = $Line;
          if ($Dots || count($Points) == 1)
          {
            foreach ($Points as [$X, $Y])
            {
              $Svg .= '<circle cx="' . self::Coordinate($X) . '" cy="' . self::Coordinate($Y) . '" r="2.5" fill="' . $Color . '"/>';
            }
          }
        }
        $Svg .= '</g>';
        $Hits[$Number] = $Lines;
      }
      return $Svg . $this->Bands() . $this->SeriesLinks($Hits ?? []);
    }

    // With Time(): how long between samples counts as a gap: more than twice the usual step (the median)
    private function GapAfter(): ?int
    {
      if ($this->Times === null || count($this->Times) < 3)
      {
        return null;
      }
      $Steps = [];
      for ($Index = 1; $Index < count($this->Times); $Index++)
      {
        $Steps[] = $this->Times[$Index] - $this->Times[$Index - 1];
      }
      sort($Steps);
      return max(1, 2 * $Steps[intdiv(count($Steps), 2)]);
    }

    // OpenSeries(): each line also as a wide see-through stroke on top, which opens the series' page
    private function SeriesLinks(array $Hits): string
    {
      if (empty($this->LinkAttributes(null, 0, '')) || empty($Hits))
      {
        return '';
      }
      $Svg = '<g class="sc-series-links">';
      foreach ($Hits as $Number => $Lines)
      {
        foreach ($Lines as $Line)
        {
          $Svg .= $this->Item('polyline', ['class' => 'sc-series-link', 'points' => $Line, 'fill' => 'none', 'stroke' => '#888',
            'stroke-opacity' => '0', 'stroke-width' => '10', 'pointer-events' => 'stroke'], $this->Series[$Number]->Name, null, $Number);
        }
      }
      return $Svg . '</g>';
    }

    // A see-through strip over each label, for its tooltip and link: from halfway to the label before to halfway to the
    // one after
    private function Bands(): string
    {
      $Count = count($this->Labels);
      $Svg = '<g class="sc-bands">';
      for ($Index = 0; $Index < $Count; $Index++)
      {
        $X = $this->X($Index);
        $Left = $Index > 0 ? ($this->X($Index - 1) + $X) / 2 : $this->Left;
        $Right = $Index < $Count - 1 ? ($X + $this->X($Index + 1)) / 2 : $this->Left + $this->PlotWidth();
        // For the hover: where the label lies across (the crosshair), and each series' value and point (the box, the dots)
        $Values = [];
        $Ys = [];
        foreach ($this->Series as $Number => $Series)
        {
          $Values[$Number] = $Series->Values[$Index] ?? null;
          if ($Values[$Number] !== null)
          {
            $Ys[$Number] = $this->Y($Values[$Number]);
          }
        }
        $Svg .= $this->Item('rect', ['class' => 'sc-band', 'x' => $Left, 'y' => self::TOP, 'width' => max(1.0, $Right - $Left),
          'height' => $this->Height - self::TOP - self::BOTTOM, 'fill' => '#888', 'fill-opacity' => '0', 'data-x' => round($X, 1),
          'data-sort' => 'desc'] + $this->HoverAttributes($Index, $Values, $Ys), $this->LabelTip($Index), $Index);
      }
      return $Svg . '</g>';
    }
  }
