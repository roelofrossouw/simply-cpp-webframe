<?php

  namespace HTML;

  /**
   * <option> Defines an option in a drop-down list
   *
   * @property bool $Disabled
   * @property string $Label text
   * @property bool $Selected
   * @property string $Value text
   * @see https://www.w3schools.com/tags/tag_option.asp
   */
  class Option extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('option', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
