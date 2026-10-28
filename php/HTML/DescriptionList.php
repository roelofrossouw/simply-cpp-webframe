<?php

  namespace HTML;

  /**
   * <dl> Defines a description list
   *
   * @see https://www.w3schools.com/tags/tag_dl.asp
   */
  class DescriptionList extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('dl', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
