<?php

  namespace Chart;

  /**
   * A table of values like Grafana's table panel: columns from a query's rows (or Prometheus->TableRows()), a number
   * column coloured by thresholds (its text, its background, or a small bar), a column of sparklines, a link per row.
   *
   *   $Table = new Chart\Table('OneAPI top clients');
   *   $Table->Column('client', 'Client');
   *   $Table->Column('value', 'Requests/min')->Thresholds([0 => BarGauge::GREEN, 100 => BarGauge::ORANGE, 500 => BarGauge::RED])->Show('bar');
   *   $Table->Column('trend', 'Last 6 hours')->Sparkline();
   *   $Table->Rows($Prometheus->TableRows('topk(10, 60 * sum by (client) (rate(oneapi_requests_total[5m])))',
   *     '60 * sum by (client) (rate(oneapi_requests_total[$__rate_interval]))', Chart\TimeRange::Last('6h')))->Sort('value');
   *   $Table->Open('/clients/view.php?name={client}', Chart\Open::SidePanel);
   *
   * Styled inline, so it looks the same in the app, in One Web and in an email.
   */
  class Table extends \HTML\Element
  {
    /** @var Column[] by key */
    private array $Columns = [];
    /** @var array<int, array<string, mixed>> */
    private array $Rows = [];
    private ?string $Href = null;
    private Open $In = Open::Page;

    public function __construct(private string $Title = '', ?string $ID = null)
    {
      parent::__construct('div', $ID);
      $this->AddClass('sc-table');
    }

    /**
     * A column: the rows' $Key, headed $Title. Without columns every key of the first row is one.
     */
    public function Column(string $Key, ?string $Title = null): Column
    {
      return $this->Columns[$Key] = new Column($Key, $Title ?? $Key);
    }

    /**
     * The rows: arrays (or objects) keyed by column
     */
    public function Rows(array $Rows): static
    {
      $this->Rows = array_map(fn($Row) => (array)$Row, $Rows);
      return $this;
    }

    /**
     * In order of a column: the highest first (or the lowest, $Descending false); text in natural order
     */
    public function Sort(string $Key, bool $Descending = true): static
    {
      usort($this->Rows, function (array $A, array $B) use ($Key, $Descending): int {
        $Order = is_numeric($A[$Key] ?? null) && is_numeric($B[$Key] ?? null) ? (float)$A[$Key] <=> (float)$B[$Key]
          : strnatcasecmp((string)($A[$Key] ?? ''), (string)($B[$Key] ?? ''));
        return $Descending ? -$Order : $Order;
      });
      return $this;
    }

    /**
     * A row opens this page when clicked; placeholders are the row's columns ({client}, {instance}, ...)
     */
    public function Open(string $Template, Open $In = Open::Page): static
    {
      $this->Href = $Template;
      $this->In = $In;
      return $this;
    }

    private static function Escape(string $Text): string
    {
      return htmlspecialchars($Text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    private function HrefOf(array $Row): ?string
    {
      if ($this->Href === null)
      {
        return null;
      }
      return preg_replace_callback('/\{(\w+)}/', fn(array $Match) => array_key_exists($Match[1], $Row) && !is_array($Row[$Match[1]])
        ? rawurlencode((string)$Row[$Match[1]]) : $Match[0], $this->Href);
    }

    protected function OwnString(): string
    {
      $this->Children = [];
      $Columns = $this->Columns ?: array_map(fn(string $Key) => new Column($Key, $Key), array_keys($this->Rows[0] ?? []));
      $InApp = Chart::InApp();
      $Html = $this->Title !== '' ? '<div class="sc-table-title" style="font-weight:600;margin-bottom:.5rem">' . self::Escape($this->Title) . '</div>' : '';
      $Html .= '<table class="table table-sm align-middle mb-0" style="width:100%;border-collapse:collapse;font-variant-numeric:tabular-nums"><thead><tr>';
      foreach ($Columns as $Column)
      {
        $Html .= '<th style="text-align:' . $Column->Align() . ';padding:.375rem .5rem;border-bottom:1px solid var(--bs-border-color,#dee2e6);' .
          'color:var(--bs-secondary-color,#6c757d);font-weight:600">' . self::Escape($Column->Title) . '</th>';
      }
      $Html .= '</tr></thead><tbody>';
      $Highest = [];
      foreach ($Columns as $Column)
      {
        $Highest[$Column->Key] = $Column->Highest($this->Rows);
      }
      if (!$this->Rows)
      {
        $Html .= '<tr><td colspan="' . count($Columns) . '" style="padding:.5rem;color:var(--bs-secondary-color,#6c757d)">No data</td></tr>';
      }
      foreach ($this->Rows as $Row)
      {
        $Href = $this->HrefOf($Row);
        $Attributes = '';
        if ($Href !== null && $InApp)
        {
          $Class = $this->In->LinkClass();
          $Attributes = ' data-href="' . self::Escape($Href) . '" role="link" tabindex="0"' . ($Class !== null ? ' class="' . $Class . '"' : '') .
            ' style="cursor:pointer"';
        }
        $Html .= '<tr' . $Attributes . '>';
        foreach (array_values($Columns) as $Position => $Column)
        {
          $Cell = $Column->Cell($Row[$Column->Key] ?? null, $Highest[$Column->Key]);
          // Outside the app the first cell is the row's link
          if ($Position === 0 && $Href !== null && !$InApp)
          {
            $Cell[1] = '<a href="' . self::Escape($Href) . '">' . $Cell[1] . '</a>';
          }
          $Html .= '<td style="text-align:' . $Column->Align() . ';padding:.375rem .5rem;border-bottom:1px solid var(--bs-border-color-translucent,#eee);' .
            $Cell[0] . '">' . $Cell[1] . '</td>';
        }
        $Html .= '</tr>';
      }
      $Html .= '</tbody></table>';
      $this->AddChild(new \HTML\Raw($Html));
      return parent::OwnString();
    }
  }
