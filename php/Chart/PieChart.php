<?php

  namespace Chart;

  /**
   * A doughnut (or pie: Hole(0)): a slice per label, sized by the first series' values. Each slice shows its value and
   * share when the pointer is on it, and opens its page when clicked (Open(), with {label}, {value} and the row's
   * columns). The legend lists the slices with their shares, and opens their pages too.
   *
   *   (new Chart\PieChart('Policies by product'))->FromRows($Rows, 'product', 'policies')->Open('/products/view.php?id={id}');
   */
  class PieChart extends Chart
  {
    private float $Hole = 0.6;

    public function __construct(?string $Title = null, ?string $ID = null)
    {
      parent::__construct($Title, $ID);
      $this->AddClass('sc-pie');
      $this->Width = 240;
      $this->Height = 240;
    }

    /**
     * The hole's size, a share of the radius: 0 for a pie, 0.6 by default
     */
    public function Hole(float $Share): static
    {
      $this->Hole = max(0.0, min(0.9, $Share));
      return $this;
    }

    protected function ColorOf(int $Series): string
    {
      return self::PALETTE[$Series % count(self::PALETTE)];
    }

    // The slices' values: the first series', only those above 0
    private function Slices(): array
    {
      $Slices = [];
      foreach (array_keys($this->Labels) as $Index)
      {
        $Value = $this->Series[0]->Values[$Index] ?? null;
        if ($Value !== null && $Value > 0)
        {
          $Slices[$Index] = $Value;
        }
      }
      return $Slices;
    }

    private function Share(float $Value, float $Total): string
    {
      return rtrim(rtrim(number_format(100 * $Value / $Total, 1, '.', ''), '0'), '.') . '%';
    }

    protected function Draw(): string
    {
      $Slices = empty($this->Series) ? [] : $this->Slices();
      $Total = array_sum($Slices);
      if ($Total <= 0)
      {
        return '<text x="' . $this->Width / 2 . '" y="' . $this->Height / 2 . '" text-anchor="middle" fill="#888">No data</text>';
      }
      $CX = $this->Width / 2;
      $CY = $this->Height / 2;
      $Outer = min($CX, $CY) - 4;
      $Inner = $Outer * $this->Hole;
      $Svg = '';
      $Angle = -M_PI / 2;
      foreach ($Slices as $Index => $Value)
      {
        $Sweep = 2 * M_PI * $Value / $Total;
        $Tip = $this->LabelText($Index) . "\n" . $this->Value($Value) . ' (' . $this->Share($Value, $Total) . ')';
        $Svg .= $this->Item('path', ['class' => 'sc-slice', 'd' => $this->Slice($CX, $CY, $Outer, $Inner, $Angle, $Sweep),
          'fill' => $this->ColorOf($Index), 'stroke' => '#fff', 'stroke-width' => '1'], $Tip, $Index, 0);
        $Angle += $Sweep;
      }
      if ($Inner > 20)
      {
        $Svg .= '<text class="sc-pie-total" x="' . self::Coordinate($CX) . '" y="' . self::Coordinate($CY + 6) . '" text-anchor="middle" font-size="18" fill="#444">' .
          self::Escape($this->Value($Total)) . '</text>';
      }
      return $Svg;
    }

    // A slice from $From, $Sweep radians round (a whole circle drawn as two halves: an arc can't end where it starts)
    private function Slice(float $CX, float $CY, float $Outer, float $Inner, float $From, float $Sweep): string
    {
      if ($Sweep >= 2 * M_PI - 1e-6)
      {
        return $this->Slice($CX, $CY, $Outer, $Inner, $From, M_PI) . ' ' . $this->Slice($CX, $CY, $Outer, $Inner, $From + M_PI, M_PI);
      }
      $To = $From + $Sweep;
      $Large = $Sweep > M_PI ? 1 : 0;
      $Point = fn(float $Radius, float $Angle) => self::Coordinate($CX + $Radius * cos($Angle)) . ' ' . self::Coordinate($CY + $Radius * sin($Angle));
      $Path = 'M' . $Point($Outer, $From) . ' A' . self::Coordinate($Outer) . ' ' . self::Coordinate($Outer) . " 0 $Large 1 " . $Point($Outer, $To);
      if ($Inner > 0)
      {
        return $Path . ' L' . $Point($Inner, $To) . ' A' . self::Coordinate($Inner) . ' ' . self::Coordinate($Inner) . " 0 $Large 0 " .
          $Point($Inner, $From) . ' Z';
      }
      return $Path . ' L' . self::Coordinate($CX) . ' ' . self::Coordinate($CY) . ' Z';
    }

    /**
     * The slices with their shares; a slice's item opens its page (Open()) like the slice
     */
    protected function Legend(): string
    {
      $Slices = empty($this->Series) ? [] : $this->Slices();
      $Total = array_sum($Slices);
      if ($Total <= 0)
      {
        return '';
      }
      $Items = '';
      foreach ($Slices as $Index => $Value)
      {
        $Swatch = '<i style="' . self::SWATCH . self::Escape($this->ColorOf($Index)) . '"></i>';
        $Text = self::Escape($this->Labels[$Index]) . ' <span class="text-body-secondary">' . $this->Share($Value, $Total) . '</span>';
        // The slice's link on its legend item too
        $Link = $this->LinkAttributes($Index, 0, $this->Labels[$Index]);
        $Classes = trim('sc-legend-item ' . ($Link['class'] ?? ''));
        unset($Link['class']);
        $Attributes = self::Attributes($Link);
        $Items .= '<span class="' . $Classes . '"' . $Attributes . '>' . $Swatch . $Text . '</span>';
      }
      return '<div class="sc-chart-legend sc-pie-legend" style="' . self::LEGEND . '">' . $Items . '</div>';
    }
  }
