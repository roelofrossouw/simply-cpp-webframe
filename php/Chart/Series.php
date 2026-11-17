<?php

  namespace Chart;

  /**
   * One line, one set of bars, or a pie's slices: a value for each of the chart's labels (null: none there, a gap)
   */
  class Series
  {
    /** @var array<int, float|null> by the label's position */
    public array $Values = [];

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
  }
