<?php

  namespace HTML;

  /**
   * <datalist> Specifies a list of pre-defined options for input controls
   *
   * @see https://www.w3schools.com/tags/tag_datalist.asp
   */
  class DataList extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('datalist', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
