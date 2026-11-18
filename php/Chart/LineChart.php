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
      // Dots only where they stay few: on a short line, and not on a hundred of them
      $Dots = $this->Dots ?? ($Count <= 40 && $Count * count($this->Series) <= 400);
      $Gap = $this->GapAfter();
      $Bottom = (int)round($this->Y(max($this->Scale->Low, min(0.0, $this->Scale->High))));
      $Xs = [];
      for ($Index = 0; $Index < $Count; $Index++)
      {
        $Xs[$Index] = (int)round($this->X($Index));
      }
      $Svg = '';
      $Hits = [];
      // With OpenSeries() each line is also a wide see-through stroke to click: its shape then goes in <defs> once and
      // both draw it (<use>), rather than the shape twice
      $Linked = !empty($this->LinkAttributes(null, 0, ''));
      $Prefix = 'sc' . bin2hex(random_bytes(4)) . '-';
      $Shapes = '';
      foreach ($this->Series as $Number => $Series)
      {
        $Color = self::Escape($this->ColorOf($Number));
        // A run of values without gaps is a line of its own: one path, whole pixels, each point a step from the last
        $Paths = [];
        $Path = '';
        $Last = null;
        $First = null;
        $Points = 0;
        $Circles = '';
        for ($Index = 0; $Index <= $Count; $Index++)
        {
          $Value = $Index < $Count ? ($Series->Values[$Index] ?? null) : null;
          $Break = $Value === null || ($Last !== null && $Gap !== null && $this->Times[$Index] - $this->Times[$Index - 1] > $Gap);
          if ($Break && $Path !== '')
          {
            $Paths[] = [$Path, $First, $Last, $Points];
            $Path = '';
            $Last = null;
            $Points = 0;
          }
          if ($Value === null)
          {
            continue;
          }
          $Point = [$Xs[$Index], (int)round($this->Y($Value))];
          if ($Last === null)
          {
            $Path = 'M' . $Point[0] . ' ' . $Point[1] . 'l';
            $First = $Point;
          }
          else
          {
            $Path .= ($Points > 1 ? ' ' : '') . ($Point[0] - $Last[0]) . ' ' . ($Point[1] - $Last[1]);
          }
          $Last = $Point;
          $Points++;
          if ($Dots)
          {
            $Circles .= '<circle cx="' . $Point[0] . '" cy="' . $Point[1] . '" r="2.5" fill="' . $Color . '"/>';
          }
        }
        $Svg .= '<g data-series="' . $Number . '">';
        $Lines = [];
        foreach ($Paths as [$Line, $Start, $End, $Many])
        {
          $Line = $Many > 1 ? $Line : rtrim($Line, 'l');
          if ($this->Area && $Many > 1)
          {
            $Svg .= '<path d="' . $Line . 'L' . $End[0] . ' ' . $Bottom . 'L' . $Start[0] . ' ' . $Bottom . 'Z" fill="' . $Color . '" fill-opacity="0.12" stroke="none"/>';
          }
          if ($Many > 1 && $Linked)
          {
            $Id = $Prefix . $Number . '-' . count($Lines);
            $Shapes .= '<path id="' . $Id . '" d="' . $Line . '"/>';
            $Svg .= '<use href="#' . $Id . '" fill="none" stroke="' . $Color . '" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>';
            $Lines[] = '#' . $Id;
          }
          elseif ($Many > 1)
          {
            $Svg .= '<path d="' . $Line . '" fill="none" stroke="' . $Color . '" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>';
          }
          elseif (!$Dots)
          {
            // A point on its own shows as a dot
            $Svg .= '<circle cx="' . $Start[0] . '" cy="' . $Start[1] . '" r="2.5" fill="' . $Color . '"/>';
          }
        }
        $Svg .= $Circles . '</g>';
        $Hits[$Number] = $Lines;
      }
      return ($Shapes !== '' ? '<defs>' . $Shapes . '</defs>' : '') . $Svg . $this->SeriesLinks($Hits) . $this->HoverArea($Xs);
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
    // label lies across, its text, each series' name, colour and value per label (written as Value() writes them, by
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
        $Data['series'][] = ['n' => $Series->Name, 'c' => $this->ColorOf($Number)];
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
        $Data['v'][] = $Values;
        if ($this->Formatted())
        {
          $Data['f'][] = $Texts;
        }
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
