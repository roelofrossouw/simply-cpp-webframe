<?php

  namespace Chart;

  /**
   * A number on a tile: what it counts, the value, how it changed, and a small line of its history. Clicked, it opens
   * a page (Open()), like a chart's items.
   *
   *   $Row->AddChild((new Chart\Stat('Mails sent today', $Sent))->Change($Sent - $Yesterday)->Trend($LastDays)
   *     ->Open('/mail/outgoing.php', Chart\Open::SidePanel));
   */
  class Stat extends \HTML\Element
  {
    private string $Text;
    private ?float $Change = null;
    private bool $UpIsGood = true;
    private string $ChangeText = '';
    /** @var float[] */
    private array $Trend = [];
    private ?string $Href = null;
    private Open $In = Open::Page;

    /**
     * $Value as shown: a number gets its thousands grouped, text stays as it is ('R 1.2m', '98%')
     */
    public function __construct(private string $Caption, float|int|string $Value, ?string $ID = null)
    {
      parent::__construct('div', $ID);
      $this->AddClass('sc-stat');
      $this->Text = is_string($Value) ? $Value : number_format((float)$Value, floor((float)$Value) == $Value ? 0 : 1, '.', ' ');
    }

    /**
     * How much it changed (shown with an arrow, green or red), and as what ('+12 since yesterday': $Text 'since yesterday').
     * $UpIsGood false when less is better (failures, waiting time).
     */
    public function Change(float $Change, string $Text = '', bool $UpIsGood = true): static
    {
      $this->Change = $Change;
      $this->ChangeText = $Text;
      $this->UpIsGood = $UpIsGood;
      return $this;
    }

    /**
     * Its recent history, oldest first: drawn as a small line under the value
     *
     * @param array<float|int|null> $Values
     */
    public function Trend(array $Values): static
    {
      $this->Trend = array_values(array_map(fn($Value) => is_numeric($Value) ? (float)$Value : 0.0, $Values));
      return $this;
    }

    public function Open(string $Href, Open $In = Open::Page): static
    {
      $this->Href = $Href;
      $this->In = $In;
      return $this;
    }

    protected function OwnString(): string
    {
      $this->Children = [];
      $Escape = fn(string $Text) => htmlspecialchars($Text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
      $Html = '<div class="sc-stat-caption">' . $Escape($this->Caption) . '</div><div class="sc-stat-value">' . $Escape($this->Text) . '</div>';
      if ($this->Change !== null)
      {
        $Good = $this->Change == 0 ? null : ($this->Change > 0) === $this->UpIsGood;
        $Class = $Good === null ? 'text-body-secondary' : ($Good ? 'text-success' : 'text-danger');
        $Arrow = $this->Change > 0 ? '▲' : ($this->Change < 0 ? '▼' : '');
        $Number = rtrim(rtrim(number_format(abs($this->Change), 1, '.', ' '), '0'), '.');
        $Html .= '<div class="sc-stat-change ' . $Class . '">' . trim($Arrow . ' ' . ($this->Change > 0 ? '+' : ($this->Change < 0 ? '−' : '')) . $Number) .
          ($this->ChangeText !== '' ? ' <span class="text-body-secondary">' . $Escape($this->ChangeText) . '</span>' : '') . '</div>';
      }
      if (count($this->Trend) > 1)
      {
        $Html .= $this->Sparkline();
      }
      $this->AddChild(new \HTML\Raw($Html));
      if ($this->Href !== null)
      {
        if (\Config::AppPage())
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
          // Outside the app (an email): a plain link around it
          return '<a href="' . htmlspecialchars($this->Href, ENT_QUOTES) . '" class="text-reset text-decoration-none">' . parent::OwnString() . '</a>';
        }
      }
      return parent::OwnString();
    }

    private function Sparkline(): string
    {
      $Width = 120;
      $Height = 28;
      $Low = min($this->Trend);
      $High = max($this->Trend);
      $Range = $High > $Low ? $High - $Low : 1.0;
      $Last = count($this->Trend) - 1;
      $Points = [];
      foreach ($this->Trend as $Index => $Value)
      {
        $Points[] = round($Width * $Index / $Last, 1) . ',' . round(2 + ($Height - 4) * (1 - ($Value - $Low) / $Range), 1);
      }
      return '<svg class="sc-stat-trend" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $Width . ' ' . $Height . '" width="' . $Width .
        '" height="' . $Height . '" preserveAspectRatio="none" aria-hidden="true"><polyline points="' . implode(' ', $Points) .
        '" fill="none" stroke="#0d6efd" stroke-width="1.5" vector-effect="non-scaling-stroke"/></svg>';
    }
  }
