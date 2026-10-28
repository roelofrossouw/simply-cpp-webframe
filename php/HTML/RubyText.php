<?php

  namespace HTML;

  /**
   * <rt> Defines an explanation/pronunciation of characters (for East Asian typography)
   *
   * @see https://www.w3schools.com/tags/tag_rt.asp
   */
  class RubyText extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('rt', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
