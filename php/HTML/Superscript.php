<?php

  namespace HTML;

  /**
   * <sup> Defines superscripted text
   *
   * @see https://www.w3schools.com/tags/tag_sup.asp
   */
  class Superscript extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('sup', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
