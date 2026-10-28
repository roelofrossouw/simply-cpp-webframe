<?php

  namespace HTML;

  /**
   * <rp> Defines what to show in browsers that do not support ruby annotations
   *
   * @see https://www.w3schools.com/tags/tag_rp.asp
   */
  class RubyParenthesis extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('rp', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
