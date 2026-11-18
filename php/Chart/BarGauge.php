<?php

  namespace Chart;

  /**
   * A value as a row of cells, like Grafana's bar gauge: the cells up to the value lit, the rest faint, each in the
   * colour of the threshold its part of the range falls in (green, then orange from 80, red from 90 by default), and the
   * value beside them in its threshold's colour. A title, an info icon with its explanation, and a link like a Stat's.
   *
   *   $Gauge = (new Chart\BarGauge('db3 CPU', $Prometheus->Value($Query)))->Unit('%')
   *     ->Info('CPU busy, the last 5 minutes')->Open('/servers/view.php?name=db3', Chart\Open::SidePanel);
   *
   * Styled inline, so it looks the same in the app, in another app (One Web) and in an email.
   */
  class BarGauge extends \HTML\Element
  {
    // Grafana's green, orange and red
    public const string GREEN = '#56a64b';
    public const string ORANGE = '#ff9830';
    public const string RED = '#f2495c';

    private float $Min = 0;
    private float $Max = 100;
    private int $Cells = 20;
    private int $Decimals = 1;
    private string $Unit = '';
    private string $Info = '';
    /** @var array<int|string, string> from => colour, in ascending order */
    private array $Thresholds = [0 => self::GREEN, 80 => self::ORANGE, 90 => self::RED];
    /** @var callable|null */
    private $Formatter = null;
    private ?string $Href = null;
    private Open $In = Open::Page;

    /**
     * $Value null: nothing to show (no cells lit, a dash)
     */
    public function __construct(private string $Title, private ?float $Value, ?string $ID = null)
    {
      parent::__construct('div', $ID);
      $this->AddClass('sc-bargauge');
    }

    /**
     * The range the cells cover (0 to 100 by default)
     */
    public function Range(float $Min, float $Max): static
    {
      $this->Min = $Min;
      $this->Max = $Max > $Min ? $Max : $Min + 1;
      return $this;
    }

    /**
     * The colours: [from => colour], each from that value up to the next (e.g. [0 => GREEN, 70 => ORANGE, 85 => RED])
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
     * How many cells (20 by default)
     */
    public function Cells(int $Cells): static
    {
      $this->Cells = max(2, min(100, $Cells));
      return $this;
    }

    /**
     * After the value: '%', ' ms', ' GB'
     */
    public function Unit(string $Unit, int $Decimals = 1): static
    {
      $this->Unit = $Unit;
      $this->Decimals = $Decimals;
      return $this;
    }

    /**
     * How the value is written instead: fn(float $Value): string
     */
    public function Format(callable $Format): static
    {
      $this->Formatter = $Format;
      return $this;
    }

    /**
     * The info icon's explanation, shown when the pointer is on it
     */
    public function Info(string $Text): static
    {
      $this->Info = $Text;
      return $this;
    }

    public function Open(string $Href, Open $In = Open::Page): static
    {
      $this->Href = $Href;
      $this->In = $In;
      return $this;
    }

    // The threshold colour a value falls in
    private function ColorAt(float $Value): string
    {
      $Color = reset($this->Thresholds) ?: self::GREEN;
      foreach ($this->Thresholds as $From => $Threshold)
      {
        if ($Value >= (float)$From)
        {
          $Color = $Threshold;
        }
      }
      return $Color;
    }

    // How many cells are lit
    private function Lit(): int
    {
      if ($this->Value === null)
      {
        return 0;
      }
      $Share = ($this->Value - $this->Min) / ($this->Max - $this->Min);
      return (int)max(0, min($this->Cells, round($Share * $this->Cells)));
    }

    private function Text(): string
    {
      if ($this->Value === null)
      {
        return '–';
      }
      if ($this->Formatter !== null)
      {
        return (string)($this->Formatter)($this->Value);
      }
      return number_format($this->Value, $this->Decimals, '.', ' ') . $this->Unit;
    }

    private static function Escape(string $Text): string
    {
      return htmlspecialchars($Text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    protected function OwnString(): string
    {
      $this->Children = [];
      $this->AddAttribute('style', 'padding:.75rem 1rem;border:1px solid var(--bs-border-color,#dee2e6);border-radius:12px;background:var(--bs-body-bg,#fff)');
      $Lit = $this->Lit();
      $Cells = '';
      for ($Cell = 0; $Cell < $this->Cells; $Cell++)
      {
        // A cell's colour is its part of the range's: the middle of the cell
        $Color = $this->ColorAt($this->Min + ($Cell + 0.5) * ($this->Max - $this->Min) / $this->Cells);
        $Cells .= '<span class="sc-bargauge-cell' . ($Cell < $Lit ? ' sc-lit' : '') . '" style="flex:1 1 0;min-width:3px;height:1.5rem;border-radius:3px;background:' .
          self::Escape($Color) . ';' . ($Cell < $Lit ? 'box-shadow:inset 0 0 6px rgba(0,0,0,.18)' : 'opacity:.18') . '"></span>';
      }
      $ValueColor = $this->Value === null ? 'var(--bs-secondary-color,#6c757d)' : $this->ColorAt($this->Value);
      $Icon = $this->Info === '' ? '' : '<span class="sc-bargauge-info" title="' . self::Escape($this->Info) . '" aria-label="' . self::Escape($this->Info) .
        '" style="display:inline-flex;color:var(--bs-secondary-color,#6c757d);cursor:help"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" ' .
        'fill="currentColor" aria-hidden="true"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>' .
        '<path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416' .
        'c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg></span>';
      $Html = '<div class="sc-bargauge-title" style="display:flex;align-items:center;gap:.5rem;font-weight:600;font-size:1.0625rem">' . self::Escape($this->Title) . $Icon . '</div>' .
        '<div style="display:flex;align-items:center;gap:1rem;margin-top:.5rem"><div class="sc-bargauge-cells" role="meter" aria-label="' . self::Escape($this->Title) .
        '" aria-valuemin="' . $this->Min . '" aria-valuemax="' . $this->Max . '"' . ($this->Value === null ? '' : ' aria-valuenow="' . $this->Value . '"') .
        ' aria-valuetext="' . self::Escape($this->Text()) . '" style="display:flex;gap:3px;flex:1 1 auto">' . $Cells . '</div>' .
        '<div class="sc-bargauge-value" style="min-width:4.5em;text-align:right;font-size:1.5rem;line-height:1;color:' . self::Escape($ValueColor) .
        ';font-variant-numeric:tabular-nums">' . self::Escape($this->Text()) . '</div></div>';
      $this->AddChild(new \HTML\Raw($Html));
      if ($this->Href !== null)
      {
        if (Chart::InApp())
        {
          $this->AddAttribute('data-href', $this->Href);
          $this->AddAttribute('role', 'link');
          $this->AddAttribute('tabindex', 0);
          if (($Class = $this->In->LinkClass()) !== null)
          {
            $this->AddClass($Class);
          }
        }
        else
        {
          return '<a href="' . self::Escape($this->Href) . '" style="color:inherit;text-decoration:none">' . parent::OwnString() . '</a>';
        }
      }
      return parent::OwnString();
    }
  }
