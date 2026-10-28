<?php

  namespace HTML;

  /**
   * <i> Defines a part of text in an alternate voice or mood
   *
   * @see https://www.w3schools.com/tags/tag_i.asp
   */
  class Italic extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('i', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
