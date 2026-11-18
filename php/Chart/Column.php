<?php

  namespace Chart;

  /**
   * A column of a Chart\Table: how its values show (written out with a unit, coloured by thresholds as text, background
   * or a small bar, or a sparkline of a list of values)
   */
  class Column
  {
    /** @var array<int|string, string> from => colour */
    private array $Thresholds = [];
    private string $Show = 'text';
    private bool $Sparkline = false;
    private string $Unit = '';
    private ?int $Decimals = null;
    /** @var callable|null */
    private $Formatter = null;
    private ?float $Max = null;

    public function __construct(public readonly string $Key, public readonly string $Title)
    {
    }

    /**
     * Colour by ranges, as BarGauge's: [0 => BarGauge::GREEN, 80 => BarGauge::ORANGE, 90 => BarGauge::RED]
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
     * How a coloured value shows: 'text' (its colour, the default), 'background' (a tint behind it), 'bar' (a small bar
     * up to $Max, the column's highest by default, before the value)
     */
    public function Show(string $How, ?float $Max = null): static
    {
      if (!in_array($How, ['text', 'background', 'bar'], true))
      {
        throw new \InvalidArgumentException("Show(): text, background or bar, not '$How'");
      }
      $this->Show = $How;
      $this->Max = $Max;
      return $this;
    }

    /**
     * The values are lists (a range query's points): a small line of each
     */
    public function Sparkline(): static
    {
      $this->Sparkline = true;
      return $this;
    }

    /**
     * After the value ('%', ' ms'), and how many decimals (as the value needs, by default)
     */
    public function Unit(string $Unit, ?int $Decimals = null): static
    {
      $this->Unit = $Unit;
      $this->Decimals = $Decimals;
      return $this;
    }

    /**
     * How the value is written instead: fn(mixed $Value): string
     */
    public function Format(callable $Format): static
    {
      $this->Formatter = $Format;
      return $this;
    }

    public function Align(): string
    {
      return $this->Sparkline || $this->Thresholds || $this->Unit !== '' || $this->Decimals !== null ? 'right' : 'left';
    }

    private function ColorOf(float $Value): ?string
    {
      if (!$this->Thresholds)
      {
        return null;
      }
      $Color = reset($this->Thresholds);
      foreach ($this->Thresholds as $From => $Threshold)
      {
        if ($Value >= (float)$From)
        {
          $Color = $Threshold;
        }
      }
      return $Color;
    }

    private function Text(mixed $Value): string
    {
      if ($this->Formatter !== null)
      {
        return (string)($this->Formatter)($Value);
      }
      if ($Value === null || $Value === '')
      {
        return '–';
      }
      if (!is_numeric($Value))
      {
        return (string)$Value;
      }
      $Number = (float)$Value;
      $Decimals = $this->Decimals ?? (floor($Number) == $Number ? 0 : (abs($Number) < 10 ? 2 : (abs($Number) < 1000 ? 1 : 0)));
      $Text = number_format($Number, $Decimals, '.', ' ');
      if ($this->Decimals === null && $Decimals > 0)
      {
        $Text = rtrim(rtrim($Text, '0'), '.');
      }
      return $Text . $this->Unit;
    }

    /**
     * A cell: [inline style for the <td>, its HTML]
     *
     * @return array{0: string, 1: string}
     */
    public function Cell(mixed $Value, ?float $Highest = null): array
    {
      $Escape = fn(string $Text) => htmlspecialchars($Text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
      if ($this->Sparkline)
      {
        return ['width:8rem', is_array($Value) ? self::Spark($Value) : ''];
      }
      $Text = $Escape($this->Text($Value));
      $Color = is_numeric($Value) ? $this->ColorOf((float)$Value) : null;
      if ($Color === null)
      {
        return ['', $Text];
      }
      $Color = $Escape($Color);
      return match ($this->Show)
      {
        'background' => ['background:' . (preg_match('/^#[0-9a-f]{6}$/i', $Color) ? $Color . '2e' : $Color) . ';font-weight:600', $Text],
        'bar' => ['', '<span style="display:inline-flex;align-items:center;gap:.5rem;width:100%;justify-content:flex-end">' .
          '<span style="flex:1 1 auto;max-width:8rem;height:.5rem;border-radius:.25rem;background:' . (preg_match('/^#[0-9a-f]{6}$/i', $Color) ? $Color . '2e' : '#eee') .
          '"><span style="display:block;height:100%;border-radius:.25rem;background:' . $Color . ';width:' .
          round(100 * max(0, min(1, (float)$Value / max(1e-9, $this->Max ?? $Highest ?? (float)$Value))), 1) . '%"></span></span>' .
          '<span style="color:' . $Color . ';font-weight:600">' . $Text . '</span></span>'],
        default => ['color:' . $Color . ';font-weight:600', $Text],
      };
    }

    /**
     * The column's highest value, for bars
     */
    public function Highest(array $Rows): ?float
    {
      $Values = array_filter(array_map(fn(array $Row) => $Row[$this->Key] ?? null, $Rows), 'is_numeric');
      return $Values ? (float)max($Values) : null;
    }

    // A small line of a list of values (nulls are gaps)
    private static function Spark(array $Values): string
    {
      $Numbers = array_values(array_filter($Values, fn($Value) => is_numeric($Value)));
      if (count($Numbers) < 2)
      {
        return '';
      }
      $Low = min($Numbers);
      $High = max($Numbers);
      $Range = $High > $Low ? $High - $Low : 1.0;
      $Values = array_values($Values);
      $Last = count($Values) - 1;
      $Points = [];
      foreach ($Values as $Index => $Value)
      {
        if (is_numeric($Value))
        {
          $Points[] = [(int)round(120 * $Index / max(1, $Last)), (int)round(2 + 20 * (1 - ((float)$Value - $Low) / $Range))];
        }
      }
      return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 24" width="120" height="24" preserveAspectRatio="none" style="display:block;width:100%;height:24px"' .
        ' aria-hidden="true"><path d="' . LineChart::PathOf($Points) . '" fill="none" stroke="#0d6efd" stroke-width="1.5" vector-effect="non-scaling-stroke"/></svg>';
    }
  }
