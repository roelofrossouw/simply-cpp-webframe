<?php

  namespace HTML;

  /**
   * <kbd> Defines keyboard input
   *
   * @see https://www.w3schools.com/tags/tag_kbd.asp
   */
  class Keyboard extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('kbd', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
