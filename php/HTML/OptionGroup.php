<?php

  namespace HTML;

  /**
   * <optgroup> Defines a group of related options in a drop-down list
   *
   * @property bool $Disabled
   * @property string $Label text
   * @see https://www.w3schools.com/tags/tag_optgroup.asp
   */
  class OptionGroup extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('optgroup', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
