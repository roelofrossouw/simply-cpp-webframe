<?php

  namespace HTML;

  /**
   * <bdo> Overrides the current text direction
   *
   * @property string $Dir ltr|rtl
   * @see https://www.w3schools.com/tags/tag_bdo.asp
   */
  class BidiOverride extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('bdo', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
