<?php

  namespace HTML;

  /**
   * <sub> Defines subscripted text
   *
   * @see https://www.w3schools.com/tags/tag_sub.asp
   */
  class Subscript extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('sub', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
