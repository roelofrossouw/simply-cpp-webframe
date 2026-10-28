<?php

  namespace HTML;

  /**
   * <code> Defines a piece of computer code
   *
   * @see https://www.w3schools.com/tags/tag_code.asp
   */
  class Code extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('code', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
