<?php

  namespace Chart;

  /**
   * Log lines like Grafana's logs panel: newest first, a row per line with its time, a colour for its level, and chosen
   * fields as columns (or the line itself); clicking a row shows all its fields and labels. Optionally a text filter, a
   * "showing 200 of 4 512" note, and a link per row. Styled inline and without a script of its own (the filter's is on
   * its box), so it works the same in the app, in One Web's pages loaded by AJAX, and in an email (without the filter).
   *
   *   $Range = Chart\TimeRange::Last('1h');
   *   $Query = '{job="oneweb",env="production"} | json | code >= 500';
   *   $Logs = new Chart\Logs('Errors');
   *   $Logs->Lines($Loki->Lines($Query, $Range, 200))->Total($Loki->LineCount($Query, $Range))
   *     ->Fields(['name' => 'User', 'page' => 'Page', 'code' => 'Code', 'ms' => 'ms'])
   *     ->Level(fn(array $Line) => ($Line['fields']['code'] ?? 0) >= 500 ? 'error' : 'info')
   *     ->Filter()->Open('/users/view.php?name={name}', Chart\Open::SidePanel);
   *
   * Lines are Chart\Loki::Lines()'s: ['time' => Unix seconds, 'labels' => [...], 'line' => text, 'fields' => array|null];
   * other sources can give the same shape.
   */
  class Logs extends \HTML\Element
  {
    // A level's colour, as Grafana's logs panel shows them
    public const array LEVELS = ['error' => '#e02f44', 'err' => '#e02f44', 'fatal' => '#e02f44', 'critical' => '#e02f44', 'crit' => '#e02f44',
      'alert' => '#e02f44', 'emerg' => '#e02f44', 'warning' => '#ff9830', 'warn' => '#ff9830', 'info' => '#56a64b', 'notice' => '#56a64b',
      'debug' => '#5794f2', 'trace' => '#a352cc'];
    private const string UNKNOWN = '#8e8e8e';

    /** @var array<int, array> */
    private array $Lines = [];
    /** @var array<string, string> field => heading */
    private array $Fields = [];
    /** @var string|callable|null */
    private $Level = null;
    private bool $Filter = false;
    private ?int $Total = null;
    private ?string $Href = null;
    private Open $In = Open::Page;

    public function __construct(private string $Title = '', ?string $ID = null)
    {
      parent::__construct('div', $ID);
      $this->AddClasses(['sc-logs', 'sc-chart']);
    }

    /**
     * The lines, newest first as Loki::Lines() gives them
     *
     * @param array<int, array> $Lines
     */
    public function Lines(array $Lines): static
    {
      $this->Lines = array_values($Lines);
      return $this;
    }

    /**
     * Columns from the lines' fields (their JSON) or labels: [name => heading]; without them a row shows the line's text
     *
     * @param array<string, string> $Fields
     */
    public function Fields(array $Fields): static
    {
      $this->Fields = array_is_list($Fields) ? array_combine($Fields, $Fields) : $Fields;
      return $this;
    }

    /**
     * Where a line's level comes from: a field or label's name ('level'), or fn(array $Line): ?string giving a level
     * ('error', 'warn', 'info', 'debug') or a colour ('#e02f44'). By default the field or label level, else Loki's
     * detected_level.
     */
    public function Level(string|callable $From): static
    {
      $this->Level = $From;
      return $this;
    }

    /**
     * A box above the lines that hides those without its text (in any field)
     */
    public function Filter(bool $On = true): static
    {
      $this->Filter = $On;
      return $this;
    }

    /**
     * How many lines there are in all (Loki::LineCount()), for "showing 200 of 4 512"
     */
    public function Total(?int $Lines): static
    {
      $this->Total = $Lines;
      return $this;
    }

    /**
     * A link on each row to this page; placeholders are the line's fields and labels ({name}, {host}, ...) and {time}
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

    // A field's or label's value as text
    private static function Text(array $Line, string $Name): string
    {
      $Value = $Line['fields'][$Name] ?? $Line['labels'][$Name] ?? null;
      return match (true)
      {
        $Value === null => '',
        is_bool($Value) => $Value ? 'true' : 'false',
        is_array($Value) => (string)json_encode($Value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        default => (string)$Value,
      };
    }

    // A line's level colour
    private function ColorOf(array $Line): string
    {
      $Level = match (true)
      {
        is_callable($this->Level) => ($this->Level)($Line),
        is_string($this->Level) => self::Text($Line, $this->Level),
        default => self::Text($Line, 'level') !== '' ? self::Text($Line, 'level') : self::Text($Line, 'detected_level'),
      };
      $Level = strtolower(trim((string)$Level));
      if (preg_match('/^#[0-9a-f]{3,8}$/', $Level))
      {
        return $Level;
      }
      return self::LEVELS[$Level] ?? self::UNKNOWN;
    }

    private function HrefOf(array $Line): ?string
    {
      if ($this->Href === null)
      {
        return null;
      }
      $Values = ($Line['labels'] ?? []) + ['time' => (string)($Line['time'] ?? '')];
      foreach ($Line['fields'] ?? [] as $Name => $Value)
      {
        $Values[$Name] = is_scalar($Value) ? $Value : null;
      }
      return preg_replace_callback('/\{(\w+)}/', fn(array $Match) => isset($Values[$Match[1]]) ? rawurlencode((string)$Values[$Match[1]]) : $Match[0], $this->Href);
    }

    protected function OwnString(): string
    {
      $this->Children = [];
      $Times = array_map(fn(array $Line) => (float)($Line['time'] ?? 0), $this->Lines);
      $Days = $Times && date('Ymd', (int)min($Times)) !== date('Ymd', (int)max($Times));
      $Muted = 'color:var(--bs-secondary-color,#6c757d)';
      $Cell = 'overflow:hidden;text-overflow:ellipsis;white-space:nowrap';

      $Html = '<div class="sc-logs-head" style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;margin-bottom:.5rem">';
      if ($this->Title !== '')
      {
        $Html .= '<div style="font-weight:600;margin-right:auto">' . self::Escape($this->Title) . '</div>';
      }
      $Shown = count($this->Lines);
      $Note = $this->Total !== null && $this->Total > $Shown ? 'showing ' . number_format($Shown, 0, '.', ' ') . ' of ' . number_format($this->Total, 0, '.', ' ')
        : number_format($Shown, 0, '.', ' ') . ' ' . ($Shown === 1 ? 'line' : 'lines');
      $Html .= '<div class="sc-logs-note small" style="' . $Muted . '">' . $Note . '</div>';
      if ($this->Filter)
      {
        $Html .= '<input type="search" class="form-control form-control-sm" placeholder="Filter" aria-label="Filter the lines" style="max-width:16rem" oninput="' .
          self::Escape("var q=this.value.toLowerCase();this.closest('.sc-logs').querySelectorAll('.sc-log').forEach(function(r){r.style.display=q&&r.textContent.toLowerCase().indexOf(q)<0?'none':''})") . '">';
      }
      $Html .= '</div>';

      // Headings for the columns
      $Html .= '<div class="sc-logs-list" style="font-size:.8125rem;font-variant-numeric:tabular-nums">';
      if ($this->Fields)
      {
        $Html .= '<div style="display:flex;gap:.75rem;padding:.25rem .5rem .25rem calc(.5rem + 3px);font-weight:600;' . $Muted . ';border-bottom:1px solid var(--bs-border-color,#dee2e6)">' .
          '<span style="flex:0 0 ' . ($Days ? '11.5rem' : '6.5rem') . '">Time</span>';
        foreach ($this->Fields as $Heading)
        {
          $Html .= '<span style="flex:1 1 0;min-width:0;' . $Cell . '">' . self::Escape($Heading) . '</span>';
        }
        $Html .= ($this->Href !== null ? '<span style="flex:0 0 1.25rem"></span>' : '') . '</div>';
      }
      if (!$this->Lines)
      {
        $Html .= '<div style="padding:.5rem;' . $Muted . '">No lines</div>';
      }
      $InApp = Chart::InApp();
      foreach ($this->Lines as $Line)
      {
        $Time = (float)($Line['time'] ?? 0);
        $When = date($Days ? 'Y-m-d H:i:s' : 'H:i:s', (int)$Time) . sprintf('.%03d', (int)(fmod($Time, 1) * 1000));
        $Html .= '<details class="sc-log" style="border-left:3px solid ' . $this->ColorOf($Line) . ';border-bottom:1px solid var(--bs-border-color-translucent,#eee)">' .
          '<summary style="display:flex;gap:.75rem;align-items:baseline;padding:.25rem .5rem;cursor:pointer;list-style:none">' .
          '<span style="flex:0 0 ' . ($Days ? '11.5rem' : '6.5rem') . ';font-family:var(--bs-font-monospace,monospace);' . $Muted . '">' . $When . '</span>';
        if ($this->Fields)
        {
          foreach (array_keys($this->Fields) as $Name)
          {
            $Value = self::Text($Line, $Name);
            $Html .= '<span style="flex:1 1 0;min-width:0;' . $Cell . '" title="' . self::Escape($Value) . '">' . self::Escape($Value) . '</span>';
          }
        }
        else
        {
          $Html .= '<span style="flex:1 1 0;min-width:0;font-family:var(--bs-font-monospace,monospace);' . $Cell . '">' . self::Escape((string)($Line['line'] ?? '')) . '</span>';
        }
        $Href = $this->HrefOf($Line);
        if ($Href !== null)
        {
          $Class = $this->In->LinkClass();
          $Html .= $InApp
            ? '<span data-href="' . self::Escape($Href) . '" role="link" tabindex="0" aria-label="Open"' . ($Class !== null ? ' class="' . $Class . '"' : '') .
              ' style="flex:0 0 1.25rem;text-align:center;cursor:pointer;color:var(--bs-link-color,#0d6efd)">&#8599;</span>'
            : '<a href="' . self::Escape($Href) . '" aria-label="Open" style="flex:0 0 1.25rem;text-align:center;text-decoration:none">&#8599;</a>';
        }
        $Details = $Line['fields'] ?? ['line' => $Line['line'] ?? ''];
        // The stream's own labels: a query's | json repeats every field as a label (and _extracted ones), shown once
        $Labels = array_filter($Line['labels'] ?? [], fn($Value, $Name) => !array_key_exists($Name, $Line['fields'] ?? [])
          && !str_ends_with((string)$Name, '_extracted'), ARRAY_FILTER_USE_BOTH);
        if ($Labels)
        {
          $Details = ['fields' => $Details, 'labels' => $Labels];
        }
        $Html .= '</summary><pre style="margin:0;padding:.375rem .75rem .5rem;font-size:.75rem;white-space:pre-wrap;word-break:break-word;' .
          'background:var(--bs-tertiary-bg,#f8f9fa)">' . self::Escape((string)json_encode($Details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) .
          '</pre></details>';
      }
      $Html .= '</div>';
      $this->AddChild(new \HTML\Raw($Html));
      return parent::OwnString();
    }
  }
