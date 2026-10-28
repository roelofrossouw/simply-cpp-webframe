<?php

  namespace HTML;

  /**
   * <ol> Defines an ordered list
   *
   * @property bool $Reversed
   * @property string|int $Start number
   * @property string $Type 1|A|a|I|i
   * @see https://www.w3schools.com/tags/tag_ol.asp
   */
  class OrderedList extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('ol', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
