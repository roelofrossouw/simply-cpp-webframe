<?php

  namespace Chart;

  /**
   * A chart drawn as SVG on the server (LineChart, BarChart, PieChart), so it shows the same in a page, an email or a
   * PDF. In the app it's interactive: each point, bar or slice shows its values when the pointer is on it, and opens a
   * page when clicked (Open()), in the page, a popup or the side panel; clicking a series in the legend hides it.
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
    // Bootstrap's colours, in an order that keeps neighbours apart
    protected const array PALETTE = ['#0d6efd', '#20c997', '#fd7e14', '#6f42c1', '#d63384', '#ffc107', '#198754', '#0dcaf0',
      '#6c757d', '#dc3545'];

    // A legend item's colour swatch, styled inline so it shows without sc-webframe's style.css (another app, an email)
    protected const string LEGEND = 'display:flex;flex-wrap:wrap;justify-content:center;gap:.25rem 1rem;margin-top:.5rem;font-size:.875rem';
    protected const string SWATCH = 'display:inline-block;width:.75em;height:.75em;margin-right:.35em;border-radius:2px;background:';

    /** @var string[] the labels, in order */
    protected array $Labels = [];
    /** @var array<int, array> each label's row (FromRows()), for Open()'s placeholders */
    protected array $Rows = [];
    /** @var Series[] */
    protected array $Series = [];
    protected int $Width = 640;
    protected int $Height = 240;
    protected bool $ShowLegend = true;
    /** @var array<int, int>|null each label's time (Unix seconds) when the labels are times (Time()) */
    protected ?array $Times = null;
    private string $Title;
    private ?string $Template = null;
    private Open $In = Open::Page;
    private ?string $SeriesTemplate = null;
    private Open $SeriesIn = Open::Page;
    private ?string $Message = null;
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

    public function NoLegend(): static
    {
      $this->ShowLegend = false;
      return $this;
    }

    /**
     * The SVG's contents, inside a viewBox of Width x Height
     */
    abstract protected function Draw(): string;

    protected function OwnString(): string
    {
      $this->Children = [];
      $this->InTimeOrder();
      if ($this->Title !== '')
      {
        $Caption = (new \HTML\FigureCaption())->AddClass('sc-chart-title');
        $Caption->AddChild($this->Title);
        $this->AddChild($Caption);
      }
      $Label = $this->Title !== '' ? $this->Title : 'Chart';
      $this->AddChild(new \HTML\Raw('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $this->Width . ' ' . $this->Height .
        '" width="' . $this->Width . '" height="' . $this->Height . '" style="max-width:100%;height:auto" role="img" aria-label="' . self::Escape($Label) .
        '" font-family="system-ui, -apple-system, sans-serif" font-size="11">' . ($this->Message !== null || empty($this->Labels) ? $this->Empty() : $this->Draw()) .
        '</svg>'));
      if ($this->ShowLegend && !empty($this->Labels) && $this->Message === null)
      {
        $this->AddChild(new \HTML\Raw($this->Legend()));
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
     * The legend under the drawing: a series per item, which hides or shows it when clicked
     */
    protected function Legend(): string
    {
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
        $Items .= '<span class="sc-legend-item" data-series="' . $Index . '" role="button" tabindex="0" aria-pressed="true">' . $Swatch . '</span>';
      }
      return '<div class="sc-chart-legend" style="' . self::LEGEND . '">' . $Items . '</div>';
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
    private static function ParseTime(mixed $Value): ?int
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
