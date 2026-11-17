<?php

  namespace Chart;

  /**
   * A dashboard: panels (charts, tiles, tables, anything) in rows, under a bar with its variables (drop-downs), the
   * time range and how often it refreshes. Those are kept in the page's address, as Grafana does (?range=6h,
   * &var-instance=api1, &refresh=60), so a dashboard can be bookmarked or linked to as it is shown. It refreshes in
   * place, without the loading screen, and series hidden in a legend stay hidden.
   *
   *   $Dashboard = new Chart\Dashboard('Requests', Range: '6h', Refresh: 60);
   *   $Instances = $Prometheus->LabelValues('instance', 'sc_rest_requests_total');
   *   $Dashboard->Variable('instance', 'Server', $Instances, All: true);
   *   $Matcher = $Dashboard->Matcher('instance');           // instance=~"api1|api2"
   *
   *   $Dashboard->Row('Traffic');
   *   $Dashboard->Add(new Chart\Stat('Up', $Prometheus->Value("count(up{{$Matcher}} == 1)")), 3);
   *   $Chart = new Chart\LineChart('Requests per minute');
   *   $Prometheus->AddTo($Chart, "60 * sum by (instance) (rate(sc_rest_requests_total{{$Matcher}}[\$__rate_interval]))",
   *     $Dashboard->Range(), '{{instance}}');
   *   $Dashboard->Add($Chart, 9);
   *   Page::AddContent($Dashboard);
   */
  class Dashboard extends \HTML\Element
  {
    // The time ranges offered, and the refresh intervals (seconds; 0: off)
    public const array RANGES = ['15m', '1h', '6h', '24h', '7d', '30d'];
    public const array REFRESHES = [0 => 'Off', 30 => '30s', 60 => '1m', 300 => '5m', 900 => '15m'];

    private string $Title;
    private string $RangeName;
    private int $RefreshSeconds;
    /** @var array<string, array{label: string, options: array<string, string>, all: bool, value: string}> */
    private array $Variables = [];
    /** @var array<int, array{title: string, panels: array<int, array{element: \HTML\Element|string, width: int, title: string}>}> */
    private array $Rows = [];

    /**
     * $Range and $Refresh are the defaults; the address (?range=, ?refresh=) wins when it names one offered
     */
    public function __construct(string $Title, string $Range = '6h', int $Refresh = 0, ?string $ID = null)
    {
      parent::__construct('div', $ID);
      $this->AddClass('sc-dashboard');
      $this->Title = $Title;
      $Asked = (string)(\QueryString::Get('range') ?? '');
      $this->RangeName = in_array($Asked, self::RANGES, true) ? $Asked : $Range;
      $AskedRefresh = \QueryString::Get('refresh');
      $this->RefreshSeconds = is_numeric($AskedRefresh) && array_key_exists((int)$AskedRefresh, self::REFRESHES) ? (int)$AskedRefresh : $Refresh;
    }

    /**
     * The time range chosen, in steps for about $Points points: what Prometheus range queries and time charts take
     */
    public function Range(int $Points = 240): TimeRange
    {
      return TimeRange::Last($this->RangeName, $Points);
    }

    /**
     * The time range's name ('6h'), e.g. for a title or an SQL interval
     */
    public function RangeName(): string
    {
      return $this->RangeName;
    }

    /**
     * A drop-down in the bar, and its value: the address' var-<name>, else $Default, else the first option. $Options
     * are values, or [value => caption]. $All offers "All" (the value '*'; Values() and Matcher() then give every option).
     */
    public function Variable(string $Name, string $Label, array $Options, ?string $Default = null, bool $All = false): string
    {
      $Options = array_is_list($Options) ? array_combine(array_map('strval', $Options), array_map('strval', $Options)) : array_map('strval', $Options);
      $Asked = \QueryString::Get('var-' . $Name);
      $Allowed = array_keys($Options);
      if ($All)
      {
        $Allowed[] = '*';
      }
      $Value = is_string($Asked) && in_array($Asked, array_map('strval', $Allowed), true) ? $Asked
        : ($Default ?? ($All ? '*' : (string)($Allowed[0] ?? '')));
      $this->Variables[$Name] = ['label' => $Label, 'options' => $Options, 'all' => $All, 'value' => $Value];
      return $Value;
    }

    /**
     * A variable's values: the one chosen, or every option for All
     *
     * @return string[]
     */
    public function Values(string $Name): array
    {
      $Variable = $this->Variables[$Name] ?? throw new \InvalidArgumentException("no dashboard variable '$Name'");
      if ($Variable['value'] === '*')
      {
        return array_map('strval', array_keys($Variable['options']));
      }
      return $Variable['value'] === '' ? [] : [$Variable['value']];
    }

    /**
     * A PromQL matcher for a variable, for inside a selector's braces: label=~"api1|api2" (the label is the variable's
     * name unless given). All: label=~".*" (every series, also those not offered). Nothing to choose from: a matcher no
     * series has.
     */
    public function Matcher(string $Name, ?string $Label = null): string
    {
      $Label ??= $Name;
      if (($this->Variables[$Name]['value'] ?? '') === '*')
      {
        return $Label . '=~".*"';
      }
      $Values = $this->Values($Name);
      if (empty($Values))
      {
        return $Label . '="__none__"';
      }
      $Quoted = array_map(fn(string $Value) => addcslashes(preg_quote($Value), '"\\'), $Values);
      return $Label . '=~"' . implode('|', $Quoted) . '"';
    }

    /**
     * Start a row of panels, with a heading ('' for none)
     */
    public function Row(string $Title = ''): static
    {
      $this->Rows[] = ['title' => $Title, 'panels' => []];
      return $this;
    }

    /**
     * A panel in the current row, $Width twelfths of the page wide on wide screens (half or all of it on smaller ones),
     * on a card; $Title above it, for what has no title of its own. A Stat tile stands on its own, without a card.
     */
    public function Add(\HTML\Element|string $Panel, int $Width = 6, string $Title = ''): static
    {
      if (empty($this->Rows))
      {
        $this->Row();
      }
      $this->Rows[count($this->Rows) - 1]['panels'][] = ['element' => $Panel, 'width' => max(1, min(12, $Width)), 'title' => $Title];
      return $this;
    }

    protected function OwnString(): string
    {
      $this->Children = [];
      $Query = $_GET;
      unset($Query['_']);
      $this->AddAttribute('data-url', \Server::Page($Query));
      $this->AddAttribute('data-refresh', $this->RefreshSeconds);
      $this->AddChild(new \HTML\Raw($this->Bar() . $this->Panels() .
        '<div class="sc-dashboard-updated small text-body-secondary text-end mt-2">Updated ' . date('H:i:s') . '</div>'));
      return parent::OwnString();
    }

    private static function Escape(string $Text): string
    {
      return htmlspecialchars($Text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    // The title, then the variables, the time range and the refresh: changing one reloads the dashboard with it
    private function Bar(): string
    {
      $Html = '<div class="sc-dashboard-bar d-flex flex-wrap align-items-center gap-2 mb-3"><h2 class="h4 mb-0 me-auto">' .
        self::Escape($this->Title) . '</h2>';
      foreach ($this->Variables as $Name => $Variable)
      {
        $Id = 'sc-var-' . preg_replace('/\W/', '', $Name) . '-' . substr(md5($this->Title), 0, 6);
        $Html .= '<div class="input-group input-group-sm w-auto"><label class="input-group-text" for="' . $Id . '">' . self::Escape($Variable['label']) .
          '</label><select class="form-select form-select-sm" id="' . $Id . '" data-dashboard-param="var-' . self::Escape($Name) . '">';
        if ($Variable['all'])
        {
          $Html .= '<option value="*"' . ($Variable['value'] === '*' ? ' selected' : '') . '>All</option>';
        }
        foreach ($Variable['options'] as $Value => $Caption)
        {
          $Html .= '<option value="' . self::Escape((string)$Value) . '"' . ((string)$Value === $Variable['value'] ? ' selected' : '') . '>' .
            self::Escape($Caption) . '</option>';
        }
        $Html .= '</select></div>';
      }
      $Html .= '<div class="btn-group btn-group-sm" role="group" aria-label="Time range">';
      foreach (self::RANGES as $Range)
      {
        $Active = $Range === $this->RangeName;
        $Html .= '<button type="button" class="btn btn-outline-secondary' . ($Active ? ' active' : '') . '" data-dashboard-param="range" data-value="' .
          $Range . '" aria-pressed="' . ($Active ? 'true' : 'false') . '">' . $Range . '</button>';
      }
      $Html .= '</div><div class="input-group input-group-sm w-auto"><label class="input-group-text" title="Refresh every"><i class="bi bi-arrow-repeat"></i></label>' .
        '<select class="form-select form-select-sm" data-dashboard-param="refresh" aria-label="Refresh every">';
      foreach (self::REFRESHES as $Seconds => $Caption)
      {
        $Html .= '<option value="' . $Seconds . '"' . ($Seconds === $this->RefreshSeconds ? ' selected' : '') . '>' . $Caption . '</option>';
      }
      return $Html . '</select><button type="button" class="btn btn-outline-secondary" data-dashboard-reload title="Refresh now" aria-label="Refresh now">' .
        '<i class="bi bi-arrow-clockwise"></i></button></div></div>';
    }

    private function Panels(): string
    {
      $Html = '';
      foreach ($this->Rows as $Row)
      {
        if ($Row['title'] !== '')
        {
          $Html .= '<h3 class="sc-dashboard-row h6 text-body-secondary text-uppercase mt-3 mb-2">' . self::Escape($Row['title']) . '</h3>';
        }
        $Html .= '<div class="row g-3">';
        foreach ($Row['panels'] as $Panel)
        {
          $Width = $Panel['width'];
          $Element = $Panel['element'];
          $Classes = $Element instanceof Stat ? 'col-6 col-md-' . max(3, $Width) . ' col-xl-' . $Width
            : 'col-12 col-md-' . max(6, $Width) . ' col-xl-' . $Width;
          $Inside = $Panel['title'] !== '' ? '<h4 class="h6 mb-2">' . self::Escape($Panel['title']) . '</h4>' : '';
          $Inside .= (string)$Element;
          $Html .= '<div class="' . $Classes . '">' . ($Element instanceof Stat ? $Inside :
              '<div class="card h-100 sc-panel"><div class="card-body">' . $Inside . '</div></div>') . '</div>';
        }
        $Html .= '</div>';
      }
      return $Html;
    }
  }
