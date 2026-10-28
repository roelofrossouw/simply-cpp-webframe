<?php

  namespace HTML;

  /**
   * <summary> Defines a visible heading for a <details> element
   *
   * @see https://www.w3schools.com/tags/tag_summary.asp
   */
  class Summary extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('summary', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
