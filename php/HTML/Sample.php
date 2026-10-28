<?php

  namespace HTML;

  /**
   * <samp> Defines sample output from a computer program
   *
   * @see https://www.w3schools.com/tags/tag_samp.asp
   */
  class Sample extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('samp', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
