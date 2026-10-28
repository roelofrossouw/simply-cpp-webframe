<?php

  namespace HTML;

  /**
   * <details> Defines additional details that the user can view or hide
   *
   * @property string $Name text
   * @property bool $Open
   * @see https://www.w3schools.com/tags/tag_details.asp
   */
  class Details extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('details', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
