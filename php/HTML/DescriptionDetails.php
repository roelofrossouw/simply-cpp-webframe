<?php

  namespace HTML;

  /**
   * <dd> Defines a description/value of a term in a description list
   *
   * @see https://www.w3schools.com/tags/tag_dd.asp
   */
  class DescriptionDetails extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('dd', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
