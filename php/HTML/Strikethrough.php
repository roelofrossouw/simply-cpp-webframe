<?php

  namespace HTML;

  /**
   * <s> Defines text that is no longer correct
   *
   * @see https://www.w3schools.com/tags/tag_s.asp
   */
  class Strikethrough extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('s', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
