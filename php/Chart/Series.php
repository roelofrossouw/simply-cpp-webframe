<?php

  namespace Chart;

  /**
   * One line, one set of bars, or a pie's slices: a value for each of the chart's labels (null: none there, a gap)
   */
  class Series
  {
    /** @var array<int, float|null> by the label's position */
    public array $Values = [];
    /** @var array<string, string> what it measures, e.g. Prometheus' labels (instance, job, ...), for OpenSeries()'s placeholders */
    public array $Labels = [];
    // Drawn below zero (Grafana's negative-Y: network sent under received); the hover shows its own value
    public bool $Negative = false;
    // A dashed line (a limit, a target, last week)
    public bool $Dashed = false;
    // The line's width in screen pixels, however far the chart is stretched (1 by default, as Grafana draws)
    public float $Width = 1;

    public function __construct(public string $Name, public ?string $Color = null)
    {
    }

    /**
     * Its colour: a CSS colour ('#0d6efd', 'rgb(...)', 'var(--bs-danger)'); the chart's palette when not set
     */
    public function Color(string $Color): static
    {
      $this->Color = $Color;
      return $this;
    }

    /**
     * Drawn below zero, as Grafana's negative-Y transform: e.g. network sent below received. The hover shows its value
     * as it is.
     */
    public function Negative(bool $On = true): static
    {
      $this->Negative = $On;
      return $this;
    }

    /**
     * A dashed line
     */
    public function Dashed(bool $On = true): static
    {
      $this->Dashed = $On;
      return $this;
    }

    /**
     * The line's width in screen pixels, however far the chart is stretched (1 by default)
     */
    public function Width(float $Width): static
    {
      $this->Width = max(0.5, $Width);
      return $this;
    }
  }
