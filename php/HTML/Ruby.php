<?php

  namespace HTML;

  /**
   * <ruby> Defines a ruby annotation (for East Asian typography)
   *
   * @see https://www.w3schools.com/tags/tag_ruby.asp
   */
  class Ruby extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('ruby', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
