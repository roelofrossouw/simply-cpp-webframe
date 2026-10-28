<?php

  namespace HTML;

  /**
   * <abbr> Defines an abbreviation or an acronym
   *
   * @see https://www.w3schools.com/tags/tag_abbr.asp
   */
  class Abbreviation extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('abbr', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
