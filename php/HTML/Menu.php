<?php

  namespace HTML;

  /**
   * <menu> Defines an unordered list
   *
   * @property string $Type text
   * @see https://www.w3schools.com/tags/tag_menu.asp
   */
  class Menu extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('menu', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
