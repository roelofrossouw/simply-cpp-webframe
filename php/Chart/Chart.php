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

    /** @var string[] the labels, in order */
    protected array $Labels = [];
    /** @var array<int, array> each label's row (FromRows()), for Open()'s placeholders */
    protected array $Rows = [];
    /** @var Series[] */
    protected array $Series = [];
    protected int $Width = 640;
    protected int $Height = 240;
    protected bool $ShowLegend = true;
    private string $Title;
    private ?string $Template = null;
    private Open $In = Open::Page;
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
        $this->LabelIndex((string)$Label);
      }
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
        $Index = $IsList ? $Key : $this->LabelIndex((string)$Key);
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
        $Index = $this->LabelIndex((string)($Row[$Label] ?? ''));
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
     * {value}, and the label's row's columns ({id}, {month}, ... from FromRows())
     */
    public function Open(string $Template, Open $In = Open::Page): static
    {
      $this->Template = $Template;
      $this->In = $In;
      return $this;
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
      if ($this->Title !== '')
      {
        $Caption = (new \HTML\FigureCaption())->AddClass('sc-chart-title');
        $Caption->AddChild($this->Title);
        $this->AddChild($Caption);
      }
      $Label = $this->Title !== '' ? $this->Title : 'Chart';
      $this->AddChild(new \HTML\Raw('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $this->Width . ' ' . $this->Height .
        '" width="' . $this->Width . '" height="' . $this->Height . '" role="img" aria-label="' . self::Escape($Label) .
        '" font-family="system-ui, -apple-system, sans-serif" font-size="11">' . (empty($this->Labels) ? $this->Empty() : $this->Draw()) .
        '</svg>'));
      if ($this->ShowLegend && !empty($this->Labels))
      {
        $this->AddChild(new \HTML\Raw($this->Legend()));
      }
      return parent::OwnString();
    }

    // A chart without data says so
    private function Empty(): string
    {
      return '<text x="' . $this->Width / 2 . '" y="' . $this->Height / 2 . '" text-anchor="middle" fill="#888">No data</text>';
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
        $Items .= '<span class="sc-legend-item" data-series="' . $Index . '" role="button" tabindex="0" aria-pressed="true">' .
          '<i style="background:' . self::Escape($this->ColorOf($Index)) . '"></i>' . self::Escape($Series->Name) . '</span>';
      }
      return '<div class="sc-chart-legend">' . $Items . '</div>';
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
    protected function Item(string $Tag, array $Attributes, string $Tip, int $Label, ?int $Series = null): string
    {
      $Attributes['data-tip'] = $Tip;
      if ($Series !== null)
      {
        $Attributes['data-series'] = $Series;
      }
      $Link = $this->LinkAttributes($Label, $Series, str_replace("\n", ', ', $Tip));
      if (isset($Link['class']))
      {
        $Link['class'] = trim(($Attributes['class'] ?? '') . ' ' . $Link['class']);
      }
      $Element = '<' . $Tag . self::Attributes(array_merge($Attributes, $Link)) . '/>';
      $Href = \Config::AppPage() ? null : $this->Href($Label, $Series);
      return $Href !== null ? '<a href="' . self::Escape($Href) . '">' . $Element . '</a>' : $Element;
    }

    /**
     * What makes an element open a label's page in the app (Open()): data-href, which app.js follows, the class saying
     * where, and what makes it a link for the keyboard and screen readers. None outside the app, or without Open().
     *
     * @return array<string, string|int>
     */
    protected function LinkAttributes(int $Label, ?int $Series, string $Description): array
    {
      $Href = \Config::AppPage() ? $this->Href($Label, $Series) : null;
      if ($Href === null)
      {
        return [];
      }
      $Attributes = ['data-href' => $Href, 'tabindex' => 0, 'role' => 'link', 'aria-label' => $Description];
      if (($Class = $this->In->LinkClass()) !== null)
      {
        $Attributes['class'] = $Class;
      }
      return $Attributes;
    }

    // Open()'s page for a label (and series), its placeholders filled in
    private function Href(int $Label, ?int $Series): ?string
    {
      if ($this->Template === null)
      {
        return null;
      }
      $Values = $this->Rows[$Label] ?? [];
      $Values['label'] = $this->Labels[$Label] ?? '';
      if ($Series !== null)
      {
        $Values['series'] = $this->Series[$Series]->Name;
        $Values['value'] = $this->Series[$Series]->Values[$Label] ?? '';
      }
      return preg_replace_callback('/\{(\w+)}/', fn(array $Match) => array_key_exists($Match[1], $Values)
        ? rawurlencode((string)$Values[$Match[1]]) : $Match[0], $this->Template);
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

    private function LabelIndex(string $Label): int
    {
      $Index = array_search($Label, $this->Labels, true);
      if ($Index === false)
      {
        $Index = count($this->Labels);
        $this->Labels[] = $Label;
      }
      return $Index;
    }

    private static function Number(mixed $Value): ?float
    {
      return is_numeric($Value) ? (float)$Value : null;
    }
  }
