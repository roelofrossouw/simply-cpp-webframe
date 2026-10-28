<?php

  namespace HTML;

  /**
   * <select> Defines a drop-down list
   *
   * @property bool $AutoFocus
   * @property bool $Disabled
   * @property string $Form id
   * @property bool $Multiple
   * @property string $Name text
   * @property bool $Required
   * @property string|int $Size number
   * @see https://www.w3schools.com/tags/tag_select.asp
   */
  class Select extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('select', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
