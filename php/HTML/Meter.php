<?php

  namespace HTML;

  /**
   * <meter> Defines a scalar measurement within a known range (a gauge)
   *
   * @property string $Form id
   * @property string|int $High number
   * @property string|int $Low number
   * @property string|int $Max number
   * @property string|int $Min number
   * @property string|int $Optimum number
   * @property string|int $Value number
   * @see https://www.w3schools.com/tags/tag_meter.asp
   */
  class Meter extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('meter', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
