<?php

  namespace Chart;

  /**
   * Bars, a group per label with a bar per series (or stacked: Stacked()). Each bar shows its value when the pointer is
   * on it, and opens its page when clicked (Open(), with {label}, {series}, {value} and the row's columns).
   */
  class BarChart extends AxisChart
  {
    private bool $Stacked = false;

    public function __construct(?string $Title = null, ?string $ID = null)
    {
      parent::__construct($Title, $ID);
      $this->Zero = true;
    }

    /**
     * The series on top of each other, a column per label
     */
    public function Stacked(): static
    {
      $this->Stacked = true;
      return $this;
    }

    protected function X(int $Index): float
    {
      return $this->Left + $this->PlotWidth() * ($Index + 0.5) / max(1, count($this->Labels));
    }

    protected function Range(): array
    {
      if (!$this->Stacked)
      {
        return parent::Range();
      }
      // Stacked: the totals, positive and negative apart
      $Low = 0.0;
      $High = 0.0;
      foreach (array_keys($this->Labels) as $Index)
      {
        $Up = $Down = 0.0;
        foreach ($this->Series as $Series)
        {
          $Value = $Series->Values[$Index] ?? 0.0;
          $Value >= 0 ? $Up += $Value : $Down += $Value;
        }
        $High = max($High, $Up);
        $Low = min($Low, $Down);
      }
      return [$Low, $High];
    }

    protected function Plot(): string
    {
      $Slot = $this->PlotWidth() / max(1, count($this->Labels));
      $Group = $Slot * 0.8;
      $Bars = $this->Stacked ? 1 : max(1, count($this->Series));
      $Width = max(1.0, $Group / $Bars - ($Bars > 1 ? 1 : 0));
      $Zero = $this->Y(0.0);
      $Svg = '';
      // Stacked: from the bottom up (StackOrder(), or the table legend's order: its first row on top); standalone.js
      // stacks them again in the document's order, so in the same one
      $Sequence = $this->Stacked ? $this->StackSequence() : array_keys($this->Series);
      foreach (array_keys($this->Labels) as $Index)
      {
        $Start = $this->X($Index) - $Group / 2;
        $Up = $Down = 0.0;
        foreach ($Sequence as $Number)
        {
          $Series = $this->Series[$Number];
          $Value = $Series->Values[$Index] ?? null;
          if ($Value === null)
          {
            continue;
          }
          if ($this->Stacked)
          {
            $From = $Value >= 0 ? $Up : $Down;
            $Value >= 0 ? $Up += $Value : $Down += $Value;
            [$Top, $Bottom] = [$this->Y(max($From, $From + $Value)), $this->Y(min($From, $From + $Value))];
            $X = $Start;
          }
          else
          {
            [$Top, $Bottom] = [min($Zero, $this->Y($Value)), max($Zero, $this->Y($Value))];
            $X = $Start + $Number * ($Width + 1);
          }
          $Tip = $this->LabelText($Index) . "\n" . $Series->Name . ': ' . $this->Value($Value);
          $Svg .= $this->Item('rect', ['class' => 'sc-bar', 'x' => $X, 'y' => $Top, 'width' => $Width,
            'height' => max(0.5, $Bottom - $Top), 'rx' => min(2.0, $Width / 4), 'fill' => $this->ColorOf($Number)] +
            $this->HoverAttributes($Index, [$Number => $Value]) + ($this->Stacked ? ['data-i' => $Index, 'data-v' => $Value] : []), $Tip, $Index, $Number);
        }
      }
      if ($this->Stacked && $Svg !== '')
      {
        // The scale, for standalone.js to stack the bars again without the series hidden in the legend
        $Svg = '<g class="sc-stack" data-y="' . $this->Scale->Low . ' ' . $this->Scale->High . ' ' . self::TOP . ' ' . ($this->Height - self::BOTTOM) . '"></g>' . $Svg;
      }
      return $Svg;
    }
  }
