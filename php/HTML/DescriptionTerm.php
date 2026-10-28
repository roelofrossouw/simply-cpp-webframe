<?php

  namespace HTML;

  /**
   * <dt> Defines a term/name in a description list
   *
   * @see https://www.w3schools.com/tags/tag_dt.asp
   */
  class DescriptionTerm extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('dt', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
