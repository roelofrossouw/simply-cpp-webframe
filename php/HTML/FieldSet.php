<?php

  namespace HTML;

  /**
   * <fieldset> Groups related elements in a form
   *
   * @property bool $Disabled
   * @property string $Form id
   * @property string $Name text
   * @see https://www.w3schools.com/tags/tag_fieldset.asp
   */
  class FieldSet extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('fieldset', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
