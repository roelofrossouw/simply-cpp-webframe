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
    // Stacked(): '' (not), 'normal' or 'percent'
    private string $Stack = '';
    /** @var array<int|string, string> Thresholds(): from => colour */
    private array $Thresholds = [];
    private string $ThresholdStyle = 'line';
    /** @var array<int, array<int, float|null>> where each series' points are drawn (stacked, below zero), by series and label */
    private array $Tops = [];
    /** @var array<int, array<int, float>> what each series stands on (stacked: the ones below it), for its area */
    private array $Unders = [];

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

    /**
     * Each series on top of the ones before it, as Grafana's stacking: 'normal' (the values add up) or 'percent' (each
     * time's total is 100%). Values above and below zero stack apart. With Area() each series fills down to the one
     * below; the hover still shows each series' own value.
     */
    public function Stacked(string $Mode = 'normal'): static
    {
      if (!in_array($Mode, ['normal', 'percent'], true))
      {
        throw new \InvalidArgumentException("Stacked(): normal or percent, not '$Mode'");
      }
      $this->Stack = $Mode;
      return $this;
    }

    /**
     * Thresholds on the chart, the same [from => colour] as BarGauge's: 'line' a dashed line at each (but the lowest),
     * 'area' a faint band from each to the next
     *
     * @param array<int|string, string> $Thresholds
     */
    public function Thresholds(array $Thresholds, string $Style = 'line'): static
    {
      if (!in_array($Style, ['line', 'area'], true))
      {
        throw new \InvalidArgumentException("Thresholds(): line or area, not '$Style'");
      }
      ksort($Thresholds, SORT_NUMERIC);
      $this->Thresholds = $Thresholds;
      $this->ThresholdStyle = $Style;
      return $this;
    }

    // Where each series' points are drawn and what its area stands on: its values, below zero when Negative, stacked
    private function Position(): void
    {
      $Count = count($this->Labels);
      $this->Tops = [];
      $this->Unders = [];
      $Up = array_fill(0, $Count, 0.0);
      $Down = array_fill(0, $Count, 0.0);
      $Totals = array_fill(0, $Count, 0.0);
      if ($this->Stack === 'percent')
      {
        foreach ($this->Series as $Series)
        {
          for ($Index = 0; $Index < $Count; $Index++)
          {
            $Totals[$Index] += abs($Series->Values[$Index] ?? 0.0);
          }
        }
      }
      foreach ($this->Series as $Number => $Series)
      {
        for ($Index = 0; $Index < $Count; $Index++)
        {
          $Value = $Series->Values[$Index] ?? null;
          $Shown = $Value === null ? 0.0 : ($Series->Negative ? -$Value : $Value);
          if ($this->Stack === 'percent')
          {
            $Shown = $Totals[$Index] > 0 ? 100 * abs($Shown) / $Totals[$Index] : 0.0;
          }
          if ($this->Stack === '')
          {
            $this->Unders[$Number][$Index] = 0.0;
            $this->Tops[$Number][$Index] = $Value === null ? null : $Shown;
            continue;
          }
          $Under = $Shown >= 0 ? $Up[$Index] : $Down[$Index];
          $Shown >= 0 ? $Up[$Index] += $Shown : $Down[$Index] += $Shown;
          $this->Unders[$Number][$Index] = $Under;
          $this->Tops[$Number][$Index] = $Value === null ? null : $Under + $Shown;
        }
      }
    }

    protected function Range(): array
    {
      $this->Position();
      if ($this->Stack === 'percent')
      {
        return [0.0, 100.0];
      }
      $Values = [];
      foreach ($this->Tops as $Number => $Tops)
      {
        foreach ($Tops as $Index => $Top)
        {
          if ($Top !== null)
          {
            $Values[] = $Top;
            if ($this->Stack !== '')
            {
              $Values[] = $this->Unders[$Number][$Index];
            }
          }
        }
      }
      return empty($Values) ? [0.0, 1.0] : [min($Values), max($Values)];
    }

    protected function Tick(float $Value): string
    {
      return parent::Tick($Value) . ($this->Stack === 'percent' && !$this->Formatted() ? '%' : '');
    }

    // Thresholds(): faint bands under the lines, or dashed lines over them
    private function ThresholdMarks(): string
    {
      if (!$this->Thresholds)
      {
        return '';
      }
      $Svg = '';
      $Froms = array_keys($this->Thresholds);
      $Left = (int)floor($this->Left);
      $Right = (int)ceil($this->Left + $this->PlotWidth());
      $Top = self::TOP;
      $Bottom = $this->Height - self::BOTTOM;
      $Clip = fn(float $Y) => (int)round(max($Top, min($Bottom, $Y)));
      foreach ($Froms as $At => $From)
      {
        $Color = self::Escape($this->Thresholds[$From]);
        if ($this->ThresholdStyle === 'area')
        {
          $Low = $At === 0 ? $this->Scale->Low : max($this->Scale->Low, (float)$From);
          $High = isset($Froms[$At + 1]) ? min($this->Scale->High, (float)$Froms[$At + 1]) : $this->Scale->High;
          if ($High > $Low)
          {
            $Y1 = $Clip($this->Y($High));
            $Svg .= '<rect class="sc-threshold" x="' . $Left . '" y="' . $Y1 . '" width="' . ($Right - $Left) . '" height="' . max(0, $Clip($this->Y($Low)) - $Y1) .
              '" fill="' . $Color . '" fill-opacity="0.1"/>';
          }
        }
        elseif ($At > 0 && (float)$From >= $this->Scale->Low && (float)$From <= $this->Scale->High)
        {
          $Y = (int)round($this->Y((float)$From));
          $Svg .= '<line class="sc-threshold" x1="' . $Left . '" x2="' . $Right . '" y1="' . $Y . '" y2="' . $Y . '" stroke="' . $Color .
            '" stroke-width="1.5" stroke-dasharray="6 4"/>';
        }
      }
      return $Svg;
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
      // Dots only where they stay few: on a short line, and not on a hundred of them
      $Dots = $this->Dots ?? ($Count <= 40 && $Count * count($this->Series) <= 400);
      $Gap = $this->GapAfter();
      $Bottom = (int)round($this->Y(max($this->Scale->Low, min(0.0, $this->Scale->High))));
      $Xs = [];
      for ($Index = 0; $Index < $Count; $Index++)
      {
        $Xs[$Index] = (int)round($this->X($Index));
      }
      $Svg = $this->ThresholdStyle === 'area' ? $this->ThresholdMarks() : '';
      $Hits = [];
      // With OpenSeries() each line is also a wide see-through stroke to click: its shape then goes in <defs> once and
      // both draw it (<use>), rather than the shape twice
      $Linked = !empty($this->LinkAttributes(null, 0, ''));
      $Prefix = 'sc' . bin2hex(random_bytes(4)) . '-';
      $Shapes = '';
      foreach ($this->Series as $Number => $Series)
      {
        $Color = self::Escape($this->ColorOf($Number));
        // Runs of values without gaps, each a line of its own, as points in whole pixels
        $Runs = [];
        $Run = [];
        $RunIndexes = [];
        $Circles = '';
        $Stroke = '" stroke-width="' . self::Coordinate($Series->Width) . '"' . ($Series->Dashed ? ' stroke-dasharray="6 4"' : '');
        for ($Index = 0; $Index <= $Count; $Index++)
        {
          $Value = $Index < $Count ? ($this->Tops[$Number][$Index] ?? null) : null;
          $Break = $Value === null || ($Run && $Gap !== null && $this->Times[$Index] - $this->Times[$Index - 1] > $Gap);
          if ($Break && $Run)
          {
            $Runs[] = $Run;
            $Run = [];
          }
          if ($Value === null)
          {
            continue;
          }
          $Point = [$Xs[$Index], (int)round($this->Y($Value))];
          $Run[] = $Point;
          $RunIndexes[count($Runs)][] = $Index;
          if ($Dots)
          {
            $Circles .= '<circle cx="' . $Point[0] . '" cy="' . $Point[1] . '" r="2.5" fill="' . $Color . '"/>';
          }
        }
        $Svg .= '<g data-series="' . $Number . '">';
        $Lines = [];
        foreach ($Runs as $RunNumber => $Points)
        {
          $Start = $Points[0];
          $End = $Points[count($Points) - 1];
          if (count($Points) < 2)
          {
            // A point on its own shows as a dot
            if (!$Dots)
            {
              $Svg .= '<circle cx="' . $Start[0] . '" cy="' . $Start[1] . '" r="2.5" fill="' . $Color . '"/>';
            }
            continue;
          }
          $Line = self::PathOf($Points);
          if ($this->Area && $this->Stack !== '')
          {
            // Down to the series below: its top, back from right to left
            $Lower = array_reverse(array_map(fn(int $Index) => [$Xs[$Index], (int)round($this->Y($this->Unders[$Number][$Index]))], $RunIndexes[$RunNumber]));
            $Svg .= '<path d="' . $Line . 'L' . substr(self::PathOf($Lower), 1) . 'Z" fill="' . $Color . '" fill-opacity="0.35" stroke="none"/>';
          }
          elseif ($this->Area)
          {
            $Svg .= '<path d="' . $Line . 'L' . $End[0] . ' ' . $Bottom . 'L' . $Start[0] . ' ' . $Bottom . 'Z" fill="' . $Color . '" fill-opacity="0.12" stroke="none"/>';
          }
          if ($Linked)
          {
            $Id = $Prefix . $Number . '-' . count($Lines);
            $Shapes .= '<path id="' . $Id . '" d="' . $Line . '"/>';
            $Svg .= '<use href="#' . $Id . '" fill="none" stroke="' . $Color . $Stroke . ' stroke-linejoin="round" stroke-linecap="round"/>';
            $Lines[] = '#' . $Id;
          }
          else
          {
            $Svg .= '<path d="' . $Line . '" fill="none" stroke="' . $Color . $Stroke . ' stroke-linejoin="round" stroke-linecap="round"/>';
          }
        }
        $Svg .= $Circles . '</g>';
        $Hits[$Number] = $Lines;
      }
      $Svg .= $this->ThresholdStyle === 'line' ? $this->ThresholdMarks() : '';
      return ($Shapes !== '' ? '<defs>' . $Shapes . '</defs>' : '') . $Svg . $this->SeriesLinks($Hits) . $this->HoverArea($Xs);
    }

    /**
     * A list as runs of equal neighbours, flat: [value, count, value, count, ...] (Chart/standalone.js expands them)
     *
     * @return array<int, mixed>
     */
    public static function Runs(array $Values): array
    {
      $Runs = [];
      foreach ($Values as $Value)
      {
        $Last = count($Runs) - 2;
        if ($Last >= 0 && $Runs[$Last] === $Value)
        {
          $Runs[$Last + 1]++;
          continue;
        }
        $Runs[] = $Value;
        $Runs[] = 1;
      }
      return $Runs;
    }

    /**
     * A run of points (whole pixels, left to right) as a path, with only the points that show: in a pixel column only
     * its first, lowest, highest and last point (more points than columns: a spike still shows, as averaging would hide
     * it), and a step that carries on in the same direction as the one before merged with it (a flat hour is one step)
     *
     * @param array<int, array{int, int}> $Points
     */
    public static function PathOf(array $Points): string
    {
      // Per pixel column: first, lowest, highest, last, in the order they came
      $Kept = [];
      $Column = [];
      $Flush = function () use (&$Kept, &$Column): void {
        if (!$Column)
        {
          return;
        }
        $Low = $High = 0;
        foreach ($Column as $At => $Point)
        {
          $Low = $Point[1] < $Column[$Low][1] ? $At : $Low;
          $High = $Point[1] > $Column[$High][1] ? $At : $High;
        }
        foreach (array_unique([0, min($Low, $High), max($Low, $High), count($Column) - 1]) as $At)
        {
          $Kept[] = $Column[$At];
        }
        $Column = [];
      };
      foreach ($Points as $Point)
      {
        if ($Column && $Column[0][0] !== $Point[0])
        {
          $Flush();
        }
        $Column[] = $Point;
      }
      $Flush();
      // Steps from point to point, merged while they keep the same direction
      $Steps = [];
      for ($At = 1; $At < count($Kept); $At++)
      {
        $Step = [$Kept[$At][0] - $Kept[$At - 1][0], $Kept[$At][1] - $Kept[$At - 1][1]];
        if ($Step[0] === 0 && $Step[1] === 0)
        {
          continue;
        }
        $Last = count($Steps) - 1;
        if ($Last >= 0 && $Steps[$Last][0] * $Step[1] === $Steps[$Last][1] * $Step[0] &&
          $Steps[$Last][0] * $Step[0] + $Steps[$Last][1] * $Step[1] > 0)
        {
          $Steps[$Last] = [$Steps[$Last][0] + $Step[0], $Steps[$Last][1] + $Step[1]];
          continue;
        }
        $Steps[] = $Step;
      }
      $Path = 'M' . $Kept[0][0] . ' ' . $Kept[0][1];
      if (!$Steps)
      {
        // Every point in one pixel: a step of nothing, so the round cap still draws a dot
        return $Path . 'l0 0';
      }
      return $Path . 'l' . implode(' ', array_map(fn(array $Step) => $Step[0] . ' ' . $Step[1], $Steps));
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
          $Svg .= $this->Item('use', ['class' => 'sc-series-link', 'href' => $Line, 'fill' => 'none', 'stroke' => '#888',
            'stroke-opacity' => '0', 'stroke-width' => '10', 'pointer-events' => 'stroke'], $this->Series[$Number]->Name, null, $Number);
        }
      }
      return $Svg . '</g>';
    }

    // One see-through area over the plot for the hover (Chart/standalone.js), and one block of data for it: where each
    // label lies across, its text, each series' name, colour and values per label as runs (Runs()) (written as Value() writes them, by
    // the script unless Format() is set), the scale (for the dots), and per label its page (Open()) and the attributes
    // an app's click reads (ItemAttributes()), which the script puts on the area as the pointer moves
    private function HoverArea(array $Xs): string
    {
      $Count = count($this->Labels);
      $Data = ['x' => array_values($Xs), 'head' => [], 'series' => [], 'v' => [],
        'y' => ['low' => $this->Scale->Low, 'high' => $this->Scale->High, 'top' => self::TOP, 'bottom' => $this->Height - self::BOTTOM]];
      for ($Index = 0; $Index < $Count; $Index++)
      {
        $Data['head'][] = $this->LabelText($Index);
      }
      foreach ($this->Series as $Number => $Series)
      {
        $Data['series'][] = ['n' => $Series->Name, 'c' => $this->ColorOf($Number)] + ($Series->Negative ? ['neg' => true] : []);
        $Values = [];
        $Texts = [];
        for ($Index = 0; $Index < $Count; $Index++)
        {
          $Value = $Series->Values[$Index] ?? null;
          // Rounded as the box shows them: two decimals under 10, one under 1000, none above
          $Values[] = $Value === null ? null : round($Value, abs($Value) < 10 ? 2 : (abs($Value) < 1000 ? 1 : 0));
          if ($this->Formatted())
          {
            $Texts[] = $this->Value($Value);
          }
        }
        // As runs: value, how many times (a job's hours of 0 are two numbers)
        $Data['v'][] = self::Runs($Values);
        if ($this->Formatted())
        {
          $Data['f'][] = self::Runs($Texts);
        }
      }
      if ($this->Stack !== '')
      {
        $Data['stack'] = $this->Stack;
      }
      $Hrefs = [];
      $Attributes = [];
      for ($Index = 0; $Index < $Count; $Index++)
      {
        if (($Href = $this->LabelHref($Index)) !== null)
        {
          $Hrefs[$Index] = $Href;
        }
        foreach ($this->LabelItemAttributes($Index) as $Name => $Value)
        {
          $Attributes[$Name][$Index] = $Value;
        }
      }
      if ($Hrefs)
      {
        $Data['href'] = array_replace(array_fill(0, $Count, null), $Hrefs);
      }
      foreach ($Attributes as $Name => $Values)
      {
        $Data['attrs'][$Name] = array_replace(array_fill(0, $Count, null), $Values);
      }
      $Class = 'sc-hover-area' . ($Hrefs && self::InApp() && ($In = $this->OpenClass()) !== null ? ' ' . $In : '');
      // <, > and & as \u escapes: inside an SVG in HTML a script element's text is read as markup
      $Json = json_encode($Data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
      return '<rect class="' . $Class . '" x="' . (int)floor($this->Left) . '" y="' . self::TOP . '" width="' . (int)ceil($this->PlotWidth()) . '" height="' .
        ($this->Height - self::TOP - self::BOTTOM) . '" fill="#888" fill-opacity="0" tabindex="0" role="img" aria-label="' .
        self::Escape('Values at each time: point at the chart, or use the arrow keys') . '"/>' .
        // In the SVG, as a script element of its own type: no browser runs it, and it travels with the drawing
        '<script type="application/json" class="sc-chart-data">' . $Json . '</script>';
    }
  }
