<?php

  namespace Chart;

  /**
   * A value axis: from Low to High in Step, rounded to 1, 2 or 5 times a power of ten, so its ticks read well
   */
  final class Scale
  {
    private function __construct(public readonly float $Low, public readonly float $High, public readonly float $Step)
    {
    }

    /**
     * About $Ticks ticks covering $Min to $Max; $Zero keeps 0 on the axis (bars start there)
     */
    public static function Around(float $Min, float $Max, int $Ticks = 5, bool $Zero = false): self
    {
      if ($Zero)
      {
        $Min = min($Min, 0.0);
        $Max = max($Max, 0.0);
      }
      if ($Max <= $Min)
      {
        // One value (or none): an axis around it
        $Spread = abs($Max) > 0 ? abs($Max) / 2 : 1.0;
        $Min = $Zero && $Min >= 0 ? 0.0 : $Min - $Spread;
        $Max += $Spread;
      }
      $Step = self::Round(($Max - $Min) / max(1, $Ticks - 1));
      return new self(floor($Min / $Step) * $Step, ceil($Max / $Step) * $Step, $Step);
    }

    // The nearest of 1, 2, 5 or 10 times a power of ten
    private static function Round(float $Value): float
    {
      $Power = 10 ** floor(log10($Value));
      $Fraction = $Value / $Power;
      return ($Fraction < 1.5 ? 1 : ($Fraction < 3 ? 2 : ($Fraction < 7 ? 5 : 10))) * $Power;
    }

    /**
     * The tick values, Low to High
     *
     * @return float[]
     */
    public function Ticks(): array
    {
      $Ticks = [];
      for ($Value = $this->Low; $Value <= $this->High + $this->Step / 2; $Value += $this->Step)
      {
        $Ticks[] = abs($Value) < $this->Step / 1e6 ? 0.0 : round($Value, 10);
      }
      return $Ticks;
    }

    /**
     * Where $Value lies, 0 (Low) to 1 (High)
     */
    public function Share(float $Value): float
    {
      return ($Value - $this->Low) / ($this->High - $this->Low);
    }
  }
