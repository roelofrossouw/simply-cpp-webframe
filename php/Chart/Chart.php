<?php

  namespace Chart;

  /**
   * A chart drawn as SVG on the server (LineChart, BarChart, PieChart), so it shows the same in a page, an email or a
   * PDF. In the app it's interactive: each point, bar or slice shows its values when the pointer is on it, and opens a
   * page when clicked (Open()), in the page, a popup or the side panel; clicking a series in the legend hides it (and in a
   * stacked chart the others are stacked again without it, by Chart/standalone.js).
   *
   *   $Chart = new Chart\BarChart('Claims per month');
   *   $Chart->FromRows(DB::Rows($Sql), 'month', ['opened' => 'Opened', 'closed' => 'Closed']);
   *   $Chart->Open('/claims/month.php?month={month}&status={series}', Chart\Open::Popup);
   *   $Card->AddChild($Chart);
   *
   * The data are labels (the x axis, or the slices) and series, each with a value per label.
   */
  abstract class Chart extends \HTML\Element
  {
    // 24 colours, by series position (not rank), in an order that keeps neighbours apart; checked for distance (OKLab
    // ΔE) and colour-blind views, no grey (it reads as "no data"), repeating only past 24 series (Roelof, 2026-11-19)
    protected const array PALETTE = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#6250d6', '#e34948',
      '#0b4f9b', '#b0461a', '#008056', '#b77900', '#b0587a', '#005700', '#3f2b98', '#a7292b', '#ffacca', '#59a354', '#847fe4',
      '#f9857d', '#689fe7', '#ff9d78', '#77d0a6', '#ffcd7a'];
    // "Others" (Top()'s rest, a pie's Others slice): a neutral grey, not a palette colour
    protected const string OTHERS = '#9a9aa0';

    // A legend item's colour swatch, styled inline so it shows without sc-webframe's style.css (another app, an email)
    protected const string LEGEND = 'display:flex;flex-wrap:wrap;justify-content:center;gap:.25rem 1rem;margin-top:.5rem;font-size:.875rem';
    protected const string SWATCH = 'display:inline-block;width:.75em;height:.75em;margin-right:.35em;border-radius:2px;background:';
    // Legend('table'): the calculations it can show, with their headers, and its styles (inline, as the list's)
    protected const array CALCULATIONS = ['last' => 'Last *', 'min' => 'Min', 'mean' => 'Mean', 'max' => 'Max', 'sum' => 'Total'];
    private const string LINE_SWATCH = 'display:inline-block;width:14px;height:3px;border-radius:1.5px;margin-right:.5em;vertical-align:middle;background:';
    private const string TABLE_HEAD = 'position:sticky;top:0;z-index:1;padding:.25rem .5rem;background:var(--bs-body-bg,#fff);' .
      'color:var(--bs-secondary-color,#6c757d);font-weight:500;white-space:nowrap;cursor:pointer;user-select:none;' .
      'border-bottom:1px solid var(--bs-border-color,#dee2e6)';
    private const string TABLE_CELL = 'padding:.2rem .5rem;white-space:nowrap;border-bottom:1px solid var(--bs-border-color-translucent,rgba(0,0,0,.08))';
    // Legend(Placement: 'right'): the drawing's share of the chart's width, the legend beside it taking the rest
    private const float DRAWING_SHARE = 0.58;

    /** @var string[] the labels, in order */
    protected array $Labels = [];
    /** @var array<int, array> each label's row (FromRows()), for Open()'s placeholders */
    protected array $Rows = [];
    /** @var Series[] */
    protected array $Series = [];
    protected int $Width = 640;
    protected int $Height = 240;
    // Legend(): 'list', 'table' or 'none'; a table's calculations, where it goes and how it's sorted first
    protected string $LegendMode = 'list';
    // Whether Legend('table') suits the chart (not a pie, a heatmap or a state timeline)
    protected bool $TableLegend = true;
    /** @var string[] */
    private array $LegendValues = [];
    private string $LegendPlacement = 'bottom';
    private ?string $LegendSort = null;
    private bool $LegendDesc = true;
    /** @var array<int, int>|null each label's time (Unix seconds) when the labels are times (Time()) */
    protected ?array $Times = null;
    private string $Title;
    private ?string $Template = null;
    private Open $In = Open::Page;
    private ?string $SeriesTemplate = null;
    private Open $SeriesIn = Open::Page;
    private ?string $Message = null;
    // Outside the app: whether the hover script (standalone.js) goes with the first chart of the request, and has
    private static bool $HoverScript = true;
    /** @var array<int, array{match: string, color: ?string, negative: ?bool, dashed: ?bool, width: ?float}> Override() */
    private array $Overrides = [];
    // Top(): how many series to keep, by what, and the name of the series the rest are folded into
    private int $TopCount = 0;
    private string $TopBy = 'max';
    private string $OthersName = 'Others';
    // Top(): the folded series' number, once folded
    private ?int $OthersAt = null;
    private static bool $HoverScriptSent = false;
    /** @var array<string, string> ItemAttributes() */
    private array $ItemAttributes = [];
    /** @var callable|null */
    private $Formatter = null;

    public function __construct(?string $Title = null, ?string $ID = null)
    {
      parent::__construct('figure', $ID);
      $this->AddClass('sc-chart');
      $this->Title = $Title ?? '';
    }

    /**
     * The labels, in order (also added as AddSeries() meets them)
     */
    public function Labels(array $Labels): static
    {
      foreach ($Labels as $Label)
      {
        $this->LabelIndex($Label);
      }
      return $this;
    }

    /**
     * The labels are times: Unix seconds (or milliseconds), or text strtotime() reads ('2026-11-17 14:00'). The values
     * then lie by their time, in time order: a LineChart spaces them so and marks round times on its axis, and series
     * keyed by the same times meet. Labels that aren't times are left out.
     */
    public function Time(): static
    {
      if ($this->Times === null)
      {
        $this->Times = [];
        foreach ($this->Labels as $Index => $Label)
        {
          $this->Times[$Index] = self::ParseTime($Label) ?? 0;
        }
      }
      return $this;
    }

    /**
     * Shown in place of the drawing, e.g. why there's no data (Prometheus did not answer)
     */
    public function Message(string $Message): static
    {
      $this->Message = $Message;
      return $this;
    }

    /**
     * A series: its values as a list in the labels' order, or keyed by label (new labels are added at the end)
     */
    public function AddSeries(string $Name, array $Values, ?string $Color = null): Series
    {
      $Series = new Series($Name, $Color);
      $IsList = array_is_list($Values);
      foreach ($Values as $Key => $Value)
      {
        $Index = $IsList && $this->Times === null ? $Key : $this->LabelIndex($IsList ? $Key : (string)$Key);
        if ($Index < 0)
        {
          continue;
        }
        if ($IsList && !isset($this->Labels[$Index]))
        {
          $this->Labels[$Index] = (string)($Index + 1);
        }
        $Series->Values[$Index] = self::Number($Value);
      }
      $this->Series[] = $Series;
      return $Series;
    }

    /**
     * From a query's rows: $Label names the column with the labels, $Columns the columns with the values, as
     * ['column' => 'Series name'] or a list of columns (their names as the series' names). A label's row gives Open()
     * its placeholders.
     */
    public function FromRows(array $Rows, string $Label, array|string $Columns): static
    {
      $Columns = is_string($Columns) ? [$Columns => $Columns] : (array_is_list($Columns) ? array_combine($Columns, $Columns) : $Columns);
      $Values = array_fill_keys(array_keys($Columns), []);
      foreach ($Rows as $Row)
      {
        $Row = (array)$Row;
        $Index = $this->LabelIndex($Row[$Label] ?? '');
        if ($Index < 0)
        {
          continue;
        }
        $this->Rows[$Index] = $Row;
        foreach ($Columns as $Column => $Name)
        {
          $Values[$Column][$Index] = self::Number($Row[$Column] ?? null);
        }
      }
      foreach ($Columns as $Column => $Name)
      {
        $Series = new Series((string)$Name);
        $Series->Values = $Values[$Column];
        $this->Series[] = $Series;
      }
      return $this;
    }

    /**
     * Clicking a point, bar or slice opens this page. Placeholders, URL-encoded: {label}, {series} (its name),
     * {value}, the label's row's columns ({id}, {month}, ... from FromRows()), the series' labels (Prometheus':
     * {instance}, {job}, ...), and with Time() {time} (Unix seconds) and {iso} (ISO 8601)
     */
    public function Open(string $Template, Open $In = Open::Page): static
    {
      $this->Template = $Template;
      $this->In = $In;
      return $this;
    }

    /**
     * Clicking a series' line (LineChart) or its legend item opens this page instead of hiding the series. Placeholders:
     * {series} (its name) and the series' labels ({instance}, {job}, ... from Prometheus)
     */
    public function OpenSeries(string $Template, Open $In = Open::Page): static
    {
      $this->SeriesTemplate = $Template;
      $this->SeriesIn = $In;
      return $this;
    }

    /**
     * Attributes for every point, bar or slice, with the placeholders Open() takes and {number} (the series' number,
     * from 1); one whose placeholder has no value on an item is left off it. For an app's own click handling, e.g. One Web's drill-down reads value and data from the element clicked:
     * ItemAttributes(['value' => '{category1}', 'data' => 'bar{number}'])
     *
     * @param array<string, string> $Attributes
     */
    public function ItemAttributes(array $Attributes): static
    {
      $this->ItemAttributes = $Attributes;
      return $this;
    }

    /**
     * From rows in One Web's graph form (as sysutil.chart() took them): category1 the label, _graph_value,
     * _graph_value2 ... _graph_value5 the series (those the rows have), named by $Names in that order. $LabelOf turns a
     * category into its text (fn($Category, array $Row): string); else it's shown as it is. A label's row gives Open()
     * and ItemAttributes() their placeholders ({category1}, ...).
     *
     * @param string[] $Names
     */
    public function FromGraphRows(array $Rows, array $Names = [], ?callable $LabelOf = null): static
    {
      $Columns = [];
      foreach (['_graph_value', '_graph_value2', '_graph_value3', '_graph_value4', '_graph_value5'] as $Number => $Column)
      {
        foreach ($Rows as $Row)
        {
          if (array_key_exists($Column, (array)$Row))
          {
            $Columns[$Column] = (string)($Names[$Number] ?? ('Value ' . ($Number + 1)));
            break;
          }
        }
      }
      $Labelled = [];
      foreach ($Rows as $Row)
      {
        $Row = (array)$Row;
        $Row['_label'] = $LabelOf !== null ? (string)$LabelOf($Row['category1'] ?? null, $Row) : (string)($Row['category1'] ?? '');
        $Labelled[] = $Row;
      }
      return $this->FromRows($Labelled, '_label', $Columns);
    }

    /**
     * A chart for One Web's graph types (sysutil.group_info.graph_type): 1 a pie, 2 bars, 3 lines; null for others
     */
    public static function ForGraphType(int $Type, ?string $Title = null): ?self
    {
      return match ($Type)
      {
        1 => new PieChart($Title),
        2 => new BarChart($Title),
        3 => new LineChart($Title),
        default => null,
      };
    }

    /**
     * Whether the chart is drawn for sc-webframe's app (its app.js follows links and shows tooltips) or elsewhere: an
     * email, a PDF, or another app (One Web), where links are SVG links and tooltips the browser's own (<title>)
     */
    public static function InApp(): bool
    {
      return class_exists('Config', false) && method_exists('Config', 'AppPage') && \Config::AppPage();
    }

    /**
     * Settings for the series named $Match, or matching it when it's a regular expression ('/^5../'), for series named
     * only when they come (Prometheus'): a fixed colour (2xx green, 5xx red), below zero (Negative: network sent under
     * received), dashed, a line's width. Later overrides win; null leaves a setting as it is.
     *
     *   $Chart->Override('/^2/', Color: BarGauge::GREEN)->Override('/^5/', Color: BarGauge::RED)->Override('sent', Negative: true);
     */
    public function Override(string $Match, ?string $Color = null, ?bool $Negative = null, ?bool $Dashed = null, ?float $Width = null): static
    {
      $this->Overrides[] = ['match' => $Match, 'color' => $Color, 'negative' => $Negative, 'dashed' => $Dashed, 'width' => $Width];
      return $this;
    }

    // Override()'s settings on the series they match
    private function ApplyOverrides(): void
    {
      foreach ($this->Overrides as $Override)
      {
        $Pattern = strlen($Override['match']) > 2 && $Override['match'][0] === '/' && @preg_match($Override['match'], '') !== false;
        foreach ($this->Series as $Series)
        {
          if ($Pattern ? preg_match($Override['match'], $Series->Name) !== 1 : $Series->Name !== $Override['match'])
          {
            continue;
          }
          if ($Override['color'] !== null)
          {
            $Series->Color = $Override['color'];
          }
          if ($Override['negative'] !== null)
          {
            $Series->Negative = $Override['negative'];
          }
          if ($Override['dashed'] !== null)
          {
            $Series->Dashed = $Override['dashed'];
          }
          if ($Override['width'] !== null)
          {
            $Series->Width = max(0.5, $Override['width']);
          }
        }
      }
    }

    /**
     * Only the $Count series that rank highest $By 'max' (their highest value), 'mean' or 'last' (their last value),
     * the rest added up into one more series, "Others (n)": for many series (100 job names), so the legend stays usable
     */
    public function Top(int $Count, string $By = 'max', string $Others = 'Others'): static
    {
      if (!in_array($By, ['max', 'mean', 'last'], true))
      {
        throw new \InvalidArgumentException("Top(): rank by max, mean or last, not '$By'");
      }
      $this->TopCount = max(1, $Count);
      $this->TopBy = $By;
      $this->OthersName = $Others;
      return $this;
    }

    // Top(): the series beyond the first TopCount, by their rank, added up into "Others (n)"
    private function FoldSeries(): void
    {
      $this->OthersAt = null;
      if ($this->TopCount < 1 || count($this->Series) <= $this->TopCount + 1)
      {
        return;
      }
      $Rank = [];
      foreach ($this->Series as $Number => $Series)
      {
        $Values = array_filter($Series->Values, fn($Value) => $Value !== null);
        $Rank[$Number] = empty($Values) ? -INF : match ($this->TopBy)
        {
          'max' => max($Values),
          'mean' => array_sum($Values) / count($Values),
          'last' => $Series->Values[array_key_last($Values)],
        };
      }
      arsort($Rank);
      $Keep = array_slice(array_keys($Rank), 0, $this->TopCount);
      sort($Keep);
      $Others = new Series($this->OthersName . ' (' . (count($this->Series) - count($Keep)) . ')', self::OTHERS);
      foreach ($this->Series as $Number => $Series)
      {
        if (in_array($Number, $Keep, true))
        {
          continue;
        }
        foreach ($Series->Values as $Index => $Value)
        {
          if ($Value !== null)
          {
            $Others->Values[$Index] = ($Others->Values[$Index] ?? 0.0) + $Value;
          }
        }
      }
      $Kept = array_map(fn(int $Number) => $this->Series[$Number], $Keep);
      // The kept series keep their colours
      foreach ($Keep as $Position => $Number)
      {
        $Kept[$Position]->Color ??= self::PALETTE[$Number % count(self::PALETTE)];
      }
      $this->Series = [...$Kept, $Others];
      $this->OthersAt = count($this->Series) - 1;
    }

    /**
     * The hover script (Chart/standalone.js) as JavaScript source, for an app that runs it its own way: one that loads
     * pages by AJAX and puts them in with innerHTML, where a chart's inline <script> never runs (One Web). Call
     * HoverScript(false) so charts don't bring it too, and run this once per page; it does nothing a second time.
     *
     *   Chart\Chart::HoverScript(false);
     *   EndScript('(0, eval)(' . json_encode(Chart\Chart::HoverScriptSource()) . ')');
     */
    public static function HoverScriptSource(): string
    {
      return (string)file_get_contents(__DIR__ . '/standalone.js');
    }

    /**
     * The first chart of a request brings a small script (Chart/standalone.js: the hover box, the crosshair and the
     * dots; in the app and outside it, e.g. One Web). Off for what no browser runs, e.g. an email or a PDF.
     */
    public static function HoverScript(bool $On): void
    {
      self::$HoverScript = $On;
    }

    /**
     * How many points the drawing can show apart: one per two pixels of the plot's width (Prometheus->AddTo() asks for
     * no more than this)
     */
    public function Resolution(): int
    {
      return max(20, intdiv($this->DrawingWidth() - 60, 2));
    }

    // The drawing's width: Size()'s, less the legend's share when a table legend goes on its right
    private function DrawingWidth(): int
    {
      return $this->LegendMode === 'table' && $this->LegendPlacement === 'right' ? (int)round($this->Width * self::DRAWING_SHARE) : $this->Width;
    }

    /**
     * The drawing's size in pixels; it scales to the width it's given, keeping this shape
     */
    public function Size(int $Width, int $Height): static
    {
      $this->Width = max(80, $Width);
      $this->Height = max(40, $Height);
      return $this;
    }

    /**
     * How values are written, in tooltips and on the axis: fn(float $Value): string (e.g. currency)
     */
    public function Format(callable $Format): static
    {
      $this->Formatter = $Format;
      return $this;
    }

    /**
     * Which legend, as Grafana's: 'list' (the series' names under the drawing, the default), 'none', or 'table': a row
     * per series with its $Values over the chart's points, in the order given: 'last' (the last value, "Last *"),
     * 'min', 'mean', 'max' and 'sum' ("Total"), written as Format() writes them. A table goes under the drawing
     * ('bottom', at most 40% of the chart's height) or beside it ('right', 42% of Size()'s width, as tall as the
     * drawing); either scrolls inside. Its rows are sorted by $SortBy ('name' or one of $Values; highest first with
     * $Desc), else in the series' order, and clicking a header sorts again. Pointing at a row brings its series forward
     * and dims the rest; clicking it hides or shows the series (or opens its page with OpenSeries()). Top()'s "Others"
     * row stays last, in grey. Colours follow the series' order, however the rows are sorted.
     *
     *   $Chart->Legend('table', ['last', 'max'], Placement: 'right', SortBy: 'last');
     */
    public function Legend(string $Mode, array $Values = [], string $Placement = 'bottom', ?string $SortBy = null, bool $Desc = true): static
    {
      if (!in_array($Mode, ['list', 'table', 'none'], true))
      {
        throw new \InvalidArgumentException("Legend(): 'list', 'table' or 'none', not '$Mode'");
      }
      if ($Mode === 'table' && !$this->TableLegend)
      {
        throw new \InvalidArgumentException('Legend(): no table legend for a ' . (new \ReflectionClass($this))->getShortName());
      }
      foreach ($Values as $Value)
      {
        if (!isset(self::CALCULATIONS[$Value]))
        {
          throw new \InvalidArgumentException("Legend(): a table shows " . implode(', ', array_keys(self::CALCULATIONS)) . ", not '$Value'");
        }
      }
      if ($Mode !== 'table' && ($Values || $Placement !== 'bottom' || $SortBy !== null))
      {
        throw new \InvalidArgumentException('Legend(): values, a placement and a sort are for a table');
      }
      if (!in_array($Placement, ['bottom', 'right'], true))
      {
        throw new \InvalidArgumentException("Legend(): placed 'bottom' or 'right', not '$Placement'");
      }
      if ($SortBy !== null && $SortBy !== 'name' && !in_array($SortBy, $Values, true))
      {
        throw new \InvalidArgumentException("Legend(): sorted by 'name' or one of its values, not '$SortBy'");
      }
      $this->LegendMode = $Mode;
      $this->LegendValues = array_values(array_unique($Values));
      $this->LegendPlacement = $Placement;
      $this->LegendSort = $SortBy;
      $this->LegendDesc = $Desc;
      return $this;
    }

    /**
     * No legend: Legend('none')
     */
    public function NoLegend(): static
    {
      return $this->Legend('none');
    }

    /**
     * Called once the series are settled (time order, Top(), Override()), before the drawing states its size: e.g. a
     * chart as tall as its rows sets its height here
     */
    protected function Prepare(): void
    {
    }

    /**
     * A name cut to $Characters, ending in … when it's longer (its row's hover gives it whole)
     */
    protected static function Fit(string $Name, int $Characters): string
    {
      return mb_strlen($Name) > $Characters ? rtrim(mb_substr($Name, 0, max(1, $Characters - 1))) . '…' : $Name;
    }

    /**
     * The plot's edges inside the drawing, [left, top, right, bottom], for a chart with axis labels around its plot;
     * null for one without (a pie)
     *
     * @return float[]|null
     */
    protected function PlotBox(): ?array
    {
      return null;
    }

    /**
     * The SVG's contents, inside a viewBox of Width x Height
     */
    abstract protected function Draw(): string;

    protected function OwnString(): string
    {
      $this->Children = [];
      $this->InTimeOrder();
      $this->FoldSeries();
      $this->ApplyOverrides();
      $this->Prepare();
      if ($this->Title !== '')
      {
        $Caption = (new \HTML\FigureCaption())->AddClass('sc-chart-title');
        $Caption->AddChild($this->Title);
        $this->AddChild($Caption);
      }
      $Label = $this->Title !== '' ? $this->Title : 'Chart';
      $HasLegend = $this->LegendMode !== 'none' && !empty($this->Labels) && $this->Message === null;
      // A table on the right: the drawing in its share of the width, so the chart keeps its height
      $Width = $this->Width;
      if ($HasLegend)
      {
        $this->Width = $this->DrawingWidth();
      }
      try
      {
        $Body = $this->Message !== null || empty($this->Labels) ? $this->Empty() : $this->Draw();
        // Where the plot is (drawn now: Draw() sets the margins), for the hover script's layout of the labels around it
        $Plot = $Body !== '' && $this->Message === null && !empty($this->Labels) ? $this->PlotBox() : null;
      }
      finally
      {
        $Drawn = $this->Width;
        $this->Width = $Width;
      }
      $PlotAttribute = $Plot !== null ? ' data-plot="' . implode(' ', array_map(fn($Edge) => self::Coordinate((float)$Edge), $Plot)) . '"' : '';
      $Svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $Drawn . ' ' . $this->Height .
        '" width="' . $Drawn . '" height="' . $this->Height . '" style="max-width:100%;height:auto" role="img" aria-label="' . self::Escape($Label) .
        '"' . $PlotAttribute . ' font-family="system-ui, -apple-system, sans-serif" font-size="11">' . $Body . '</svg>';
      $Legend = $HasLegend ? $this->DrawLegend() : '';
      if ($Legend !== '' && $this->LegendMode === 'table' && $this->LegendPlacement === 'right')
      {
        // Side by side, the legend as tall as the drawing (its own content doesn't stretch the row: it scrolls)
        $Svg = '<div class="sc-chart-body sc-legend-right" style="display:flex;align-items:stretch;gap:.75rem">' .
          '<div style="flex:1 1 0;min-width:0">' . $Svg . '</div>' . $Legend . '</div>';
        $Legend = '';
      }
      $this->AddChild(new \HTML\Raw($Svg));
      if ($Legend !== '')
      {
        $this->AddChild(new \HTML\Raw($Legend));
      }
      if (self::$HoverScript && !self::$HoverScriptSent)
      {
        self::$HoverScriptSent = true;
        $this->AddChild(new \HTML\Raw('<script>' . self::HoverScriptSource() . '</script>'));
      }
      return parent::OwnString();
    }

    // A chart without data says so (or says Message())
    private function Empty(): string
    {
      return '<text x="' . $this->Width / 2 . '" y="' . $this->Height / 2 . '" text-anchor="middle" fill="#888">' .
        self::Escape($this->Message ?? 'No data') . '</text>';
    }

    // With Time(): the labels, rows and values put in time order
    private function InTimeOrder(): void
    {
      if ($this->Times === null || empty($this->Times))
      {
        return;
      }
      $Order = array_keys($this->Times);
      usort($Order, fn(int $A, int $B) => $this->Times[$A] <=> $this->Times[$B]);
      if ($Order === array_keys($this->Labels))
      {
        return;
      }
      $Move = fn(array $From) => array_values(array_map(fn(int $Old) => $From[$Old] ?? null, $Order));
      $this->Labels = $Move($this->Labels);
      $this->Times = $Move($this->Times);
      $Rows = $Move($this->Rows);
      $this->Rows = array_filter($Rows, fn($Row) => $Row !== null);
      foreach ($this->Series as $Series)
      {
        $Series->Values = $Move($Series->Values);
      }
    }

    /**
     * A label as tooltips show it: with Time() the time written out (to the minute, or the second when the points are
     * closer than a minute; the date alone when every time is midnight)
     */
    protected function LabelText(int $Index): string
    {
      if ($this->Times === null)
      {
        return $this->Labels[$Index] ?? '';
      }
      $Time = $this->Times[$Index];
      $Midnights = array_reduce($this->Times, fn(bool $All, int $T) => $All && date('H:i:s', $T) === '00:00:00', true);
      $Seconds = count($this->Times) > 1 && min(array_map(fn($A, $B) => abs($B - $A) ?: PHP_INT_MAX,
          array_slice($this->Times, 0, -1), array_slice($this->Times, 1))) < 60;
      return date($Midnights ? 'D j M Y' : ($Seconds ? 'D j M H:i:s' : 'D j M H:i'), $Time);
    }

    /**
     * The legend (Legend()): a list under the drawing, a series per item, which hides or shows it when clicked; or a
     * table
     */
    protected function DrawLegend(): string
    {
      if ($this->LegendMode === 'table')
      {
        return empty($this->Series) ? '' : $this->LegendTable();
      }
      if (count($this->Series) < 2)
      {
        return '';
      }
      $Items = '';
      foreach ($this->Series as $Index => $Series)
      {
        $Swatch = '<i style="' . self::SWATCH . self::Escape($this->ColorOf($Index)) . '"></i>' . self::Escape($Series->Name);
        $Link = $this->LinkAttributes(null, $Index, $Series->Name);
        if ($Link)
        {
          // OpenSeries(): the legend item opens the series' page
          $Classes = trim('sc-legend-item ' . ($Link['class'] ?? ''));
          unset($Link['class']);
          $Items .= '<span class="' . $Classes . '"' . self::Attributes($Link) . '>' . $Swatch . '</span>';
          continue;
        }
        // In the app, app.js hides and shows the series; elsewhere (One Web) the hover script does (Chart/standalone.js)
        $Toggle = self::InApp() ? '' : ' style="cursor:pointer"';
        $Items .= '<span class="sc-legend-item" data-series="' . $Index . '" role="button" tabindex="0" aria-pressed="true"' . $Toggle . '>' . $Swatch . '</span>';
      }
      // Many series: the legend scrolls rather than pushing the page down
      $Scroll = count($this->Series) > 12 ? ';max-height:7.5rem;overflow-y:auto' : '';
      return '<div class="sc-chart-legend" style="' . self::LEGEND . $Scroll . '">' . $Items . '</div>';
    }

    /**
     * A calculation over a series' values (null where it has none): 'last', 'min', 'mean', 'max' or 'sum'
     */
    protected static function Calculate(Series $Series, string $Calculation): ?float
    {
      $Values = $Series->Values;
      ksort($Values);
      $Values = array_values(array_filter($Values, fn($Value) => $Value !== null));
      if (!$Values)
      {
        return null;
      }
      return (float)match ($Calculation)
      {
        'last' => $Values[count($Values) - 1],
        'min' => min($Values),
        'max' => max($Values),
        'sum' => array_sum($Values),
        'mean' => array_sum($Values) / count($Values),
      };
    }

    // Legend('table'): a row per series, its calculations right-aligned; the sort and the highlight are standalone.js's
    private function LegendTable(): string
    {
      $Others = $this->OthersAt;
      $Rows = [];
      foreach ($this->Series as $Number => $Series)
      {
        $Calculated = [];
        foreach ($this->LegendValues as $Calculation)
        {
          $Calculated[$Calculation] = self::Calculate($Series, $Calculation);
        }
        $Rows[$Number] = $Calculated;
      }
      $Order = array_keys($Rows);
      if ($this->LegendSort !== null)
      {
        $Key = $this->LegendSort;
        usort($Order, function (int $A, int $B) use ($Rows, $Key)
        {
          if ($Key === 'name')
          {
            $By = strnatcasecmp($this->Series[$A]->Name, $this->Series[$B]->Name);
            return ($this->LegendDesc ? -$By : $By) ?: $A <=> $B;
          }
          // Without a value: last either way
          [$X, $Y] = [$Rows[$A][$Key], $Rows[$B][$Key]];
          if ($X === null || $Y === null)
          {
            return ($X === null) <=> ($Y === null) ?: $A <=> $B;
          }
          return ($this->LegendDesc ? $Y <=> $X : $X <=> $Y) ?: $A <=> $B;
        });
      }
      if ($Others !== null)
      {
        // Others last, whatever the sort
        $Order = [...array_values(array_diff($Order, [$Others])), $Others];
      }
      // Headers in the order given: Name first, then the calculations as Legend() lists them
      $Head = '';
      foreach (['name', ...$this->LegendValues] as $Key)
      {
        $Text = $Key === 'name' ? 'Name' : self::CALCULATIONS[$Key];
        $Sorted = $this->LegendSort === $Key;
        $Head .= '<th scope="col" data-sort="' . $Key . '" tabindex="0" aria-sort="' . ($Sorted ? ($this->LegendDesc ? 'descending' : 'ascending') : 'none') .
          '" style="' . self::TABLE_HEAD . ';text-align:' . ($Key === 'name' ? 'left' : 'right') . '">' . self::Escape($Text) .
          '<span class="sc-sort-arrow">' . ($Sorted ? ($this->LegendDesc ? ' ↓' : ' ↑') : '') . '</span></th>';
      }
      $Body = '';
      foreach ($Order as $Number)
      {
        $Series = $this->Series[$Number];
        $Grey = $Number === $Others;
        $Cells = '<td style="' . self::TABLE_CELL . ';width:100%;max-width:0;overflow:hidden;text-overflow:ellipsis" title="' . self::Escape($Series->Name) . '">' .
          '<i style="' . self::LINE_SWATCH . self::Escape($Grey ? self::OTHERS : $this->ColorOf($Number)) . '"></i>' . self::Escape($Series->Name) . '</td>';
        foreach ($this->LegendValues as $Calculation)
        {
          $Value = $Rows[$Number][$Calculation];
          $Cells .= '<td style="' . self::TABLE_CELL . ';text-align:right" data-value="' . ($Value === null ? '' : self::Escape((string)$Value)) . '">' .
            self::Escape($this->Value($Value)) . '</td>';
        }
        $Attributes = ['data-row' => $Number];
        if ($Grey)
        {
          $Attributes['data-others'] = '';
        }
        $Style = $Grey ? 'color:var(--bs-secondary-color,#6c757d)' : '';
        $Link = $this->LinkAttributes(null, $Number, $Series->Name);
        if ($Link)
        {
          // OpenSeries(): the row opens the series' page
          $Attributes['class'] = trim('sc-legend-item ' . ($Link['class'] ?? ''));
          unset($Link['class']);
          $Attributes += $Link;
        }
        else
        {
          $Attributes += ['class' => 'sc-legend-item', 'data-series' => $Number, 'role' => 'button', 'tabindex' => 0, 'aria-pressed' => 'true'];
          // In the app, app.js hides and shows the series; elsewhere (One Web) the hover script does (Chart/standalone.js)
          $Style .= ($Style !== '' ? ';' : '') . (self::InApp() ? '' : 'cursor:pointer');
        }
        if (trim($Style, ';') !== '')
        {
          $Attributes['style'] = trim($Style, ';');
        }
        $Body .= '<tr' . self::Attributes($Attributes) . '>' . $Cells . '</tr>';
      }
      $Table = '<table style="width:100%;border-collapse:separate;border-spacing:0;font-variant-numeric:tabular-nums">' .
        '<thead><tr>' . $Head . '</tr></thead><tbody>' . $Body . '</tbody></table>';
      if ($this->LegendPlacement === 'right')
      {
        return '<div class="sc-chart-legend sc-legend-table" style="display:block;flex:0 0 ' . round(100 * (1 - self::DRAWING_SHARE) - 1) .
          '%;position:relative;min-width:0;margin:0;font-size:.8125rem"><div class="sc-legend-scroll" style="position:absolute;inset:0;overflow:auto">' .
          $Table . '</div></div>';
      }
      return '<div class="sc-chart-legend sc-legend-table" style="display:block;margin-top:.5rem;font-size:.8125rem;overflow:auto;max-height:' .
        max(96, (int)round(0.4 * $this->Height)) . 'px">' . $Table . '</div>';
    }

    protected function ColorOf(int $Series): string
    {
      return $this->Series[$Series]->Color ?? self::PALETTE[$Series % count(self::PALETTE)];
    }

    /**
     * An item of the drawing (<rect .../>, <path .../>): its tooltip, and its link when Open() was given, as a
     * data-href app.js follows (in the app) or an SVG link (elsewhere, e.g. in an email)
     *
     * @param array<string, string|int|float> $Attributes
     */
    protected function Item(string $Tag, array $Attributes, string $Tip, ?int $Label, ?int $Series = null): string
    {
      $Attributes['data-tip'] = $Tip;
      if ($Series !== null)
      {
        $Attributes['data-series'] = $Series;
      }
      foreach ($this->ItemAttributes as $Name => $Template)
      {
        // Left off where a placeholder has no value here (a line chart's strip has a category, not a series)
        $Value = $this->Fill($Template, $Label, $Series, false);
        if (!preg_match('/\{\w+}/', $Value))
        {
          $Attributes[$Name] = $Value;
        }
      }
      $Link = $this->LinkAttributes($Label, $Series, str_replace("\n", ', ', $Tip));
      if (isset($Link['class']))
      {
        $Link['class'] = trim(($Attributes['class'] ?? '') . ' ' . $Link['class']);
      }
      $InApp = self::InApp();
      // Outside the app the browser's own tooltip, from a <title>
      $Element = '<' . $Tag . self::Attributes(array_merge($Attributes, $Link)) . ($InApp ? '/>' : '><title>' . self::Escape($Tip) . '</title></' . $Tag . '>');
      $Href = $InApp ? null : $this->Href($Label, $Series);
      return $Href !== null ? '<a href="' . self::Escape($Href) . '">' . $Element . '</a>' : $Element;
    }

    /**
     * What makes an element open a label's page in the app (Open()): data-href, which app.js follows, the class saying
     * where, and what makes it a link for the keyboard and screen readers. None outside the app, or without Open().
     *
     * @return array<string, string|int>
     */
    protected function LinkAttributes(?int $Label, ?int $Series, string $Description): array
    {
      $Href = self::InApp() ? $this->Href($Label, $Series) : null;
      if ($Href === null)
      {
        return [];
      }
      $Attributes = ['data-href' => $Href, 'tabindex' => 0, 'role' => 'link', 'aria-label' => $Description];
      if (($Class = ($Label === null ? $this->SeriesIn : $this->In)->LinkClass()) !== null)
      {
        $Attributes['class'] = $Class;
      }
      return $Attributes;
    }

    // Open()'s page for a label (and series), or OpenSeries()'s for a series alone ($Label null), placeholders filled in
    private function Href(?int $Label, ?int $Series): ?string
    {
      $Template = $Label === null ? $this->SeriesTemplate : $this->Template;
      return $Template === null ? null : $this->Fill($Template, $Label, $Series, true);
    }

    // A template's placeholders filled in for a label and series, URL-encoded or as they are
    private function Fill(string $Template, ?int $Label, ?int $Series, bool $Encode): string
    {
      $Values = $Series !== null ? $this->Series[$Series]->Labels : [];
      if ($Label !== null)
      {
        $Values = array_merge($Values, $this->Rows[$Label] ?? []);
        $Values['label'] = $this->Labels[$Label] ?? '';
        if ($this->Times !== null && isset($this->Times[$Label]))
        {
          $Values['time'] = $this->Times[$Label];
          $Values['iso'] = date('c', $this->Times[$Label]);
        }
      }
      if ($Series !== null)
      {
        $Values['series'] = $this->Series[$Series]->Name;
        $Values['number'] = $Series + 1;
        if ($Label !== null)
        {
          $Values['value'] = $this->Series[$Series]->Values[$Label] ?? '';
        }
      }
      return preg_replace_callback('/\{(\w+)}/', fn(array $Match) => array_key_exists($Match[1], $Values)
        ? ($Encode ? rawurlencode((string)$Values[$Match[1]]) : (string)$Values[$Match[1]]) : $Match[0], $Template);
    }

    /**
     * The hover box's contents for app.js, as attributes: data-head (the label, written out) and data-rows (JSON, a row
     * per value: n name, c colour, v the value written out, r the value, s the series' number, y where a line's point
     * is). app.js draws them as a table with colour swatches, without the series hidden in the legend.
     *
     * @param array<int, float|null> $Values by series
     * @param array<int, float> $Ys by series: where each line's point lies (LineChart), for the dots on hover
     * @return array<string, string>
     */
    protected function HoverAttributes(int $Label, array $Values, array $Ys = []): array
    {
      $Rows = [];
      foreach ($Values as $Series => $Value)
      {
        $Row = ['n' => $this->Series[$Series]->Name ?? (string)$Series, 'c' => $this->ColorOf($Series), 'v' => $this->Value($Value), 'r' => $Value, 's' => $Series];
        if (isset($Ys[$Series]))
        {
          $Row['y'] = round($Ys[$Series], 1);
        }
        $Rows[] = $Row;
      }
      return ['data-head' => $this->LabelText($Label), 'data-rows' => json_encode($Rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
    }

    /**
     * A label's page (Open()), its placeholders filled in; null without Open()
     */
    protected function LabelHref(int $Label): ?string
    {
      return $this->Href($Label, null);
    }

    /**
     * ItemAttributes() for a label alone (no series), those whose placeholders all have values
     *
     * @return array<string, string>
     */
    protected function LabelItemAttributes(int $Label): array
    {
      $Attributes = [];
      foreach ($this->ItemAttributes as $Name => $Template)
      {
        $Value = $this->Fill($Template, $Label, null, false);
        if (!preg_match('/\{\w+}/', $Value))
        {
          $Attributes[$Name] = $Value;
        }
      }
      return $Attributes;
    }

    // The class that says where Open()'s pages open, for app.js (modal-link, oc-link), or null for the page
    protected function OpenClass(): ?string
    {
      return $this->In->LinkClass();
    }

    // Whether Format() writes the values (then a chart sends them written out; else app.js writes them as Value() does)
    protected function Formatted(): bool
    {
      return $this->Formatter !== null;
    }

    /**
     * A value as tooltips show it: Format()'s, else grouped thousands and up to two decimals
     */
    protected function Value(?float $Value): string
    {
      if ($Value === null)
      {
        return '–';
      }
      if ($this->Formatter !== null)
      {
        return (string)($this->Formatter)($Value);
      }
      $Decimals = floor($Value) == $Value ? 0 : (abs($Value) < 10 ? 2 : (abs($Value) < 1000 ? 1 : 0));
      $Text = number_format($Value, $Decimals, '.', ' ');
      return $Decimals > 0 ? rtrim(rtrim($Text, '0'), '.') : $Text;
    }

    /**
     * A value on an axis: short (1.5k, 2M) unless Format() says otherwise
     */
    protected function Tick(float $Value): string
    {
      if ($this->Formatter !== null)
      {
        return (string)($this->Formatter)($Value);
      }
      foreach ([1e9 => 'G', 1e6 => 'M', 1e3 => 'k'] as $Size => $Suffix)
      {
        if (abs($Value) >= $Size)
        {
          return rtrim(rtrim(number_format($Value / $Size, 1, '.', ''), '0'), '.') . $Suffix;
        }
      }
      return rtrim(rtrim(number_format($Value, 2, '.', ''), '0'), '.') ?: '0';
    }

    protected static function Escape(string $Text): string
    {
      return htmlspecialchars($Text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, 'UTF-8');
    }

    /** @param array<string, string|int|float> $Attributes */
    protected static function Attributes(array $Attributes): string
    {
      $Text = '';
      foreach ($Attributes as $Name => $Value)
      {
        $Text .= ' ' . $Name . '="' . self::Escape(is_float($Value) ? self::Coordinate($Value) : (string)$Value) . '"';
      }
      return $Text;
    }

    // A coordinate, to a tenth of a pixel
    protected static function Coordinate(float $Value): string
    {
      $Text = number_format($Value, 1, '.', '');
      return str_ends_with($Text, '.0') ? substr($Text, 0, -2) : $Text;
    }

    // A label's position, added when new; with Time() keyed by its time, -1 for one that isn't a time
    private function LabelIndex(mixed $Label): int
    {
      if ($this->Times !== null)
      {
        $Time = self::ParseTime($Label);
        if ($Time === null)
        {
          return -1;
        }
        $Index = array_search($Time, $this->Times, true);
        if ($Index === false)
        {
          $Index = count($this->Labels);
          $this->Labels[] = (string)$Label;
          $this->Times[] = $Time;
        }
        return $Index;
      }
      $Label = (string)$Label;
      $Index = array_search($Label, $this->Labels, true);
      if ($Index === false)
      {
        $Index = count($this->Labels);
        $this->Labels[] = $Label;
      }
      return $Index;
    }

    // Unix seconds from seconds, milliseconds (Prometheus' and JavaScript's), a DateTimeInterface or text; null if none
    protected static function ParseTime(mixed $Value): ?int
    {
      if ($Value instanceof \DateTimeInterface)
      {
        return $Value->getTimestamp();
      }
      if (is_numeric($Value))
      {
        $Number = (float)$Value;
        return (int)round(abs($Number) > 1e11 ? $Number / 1000 : $Number);
      }
      $Time = is_string($Value) && trim($Value) !== '' ? strtotime($Value) : false;
      return $Time === false ? null : $Time;
    }

    private static function Number(mixed $Value): ?float
    {
      return is_numeric($Value) ? (float)$Value : null;
    }
  }
