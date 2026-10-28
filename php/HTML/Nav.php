<?php

  namespace HTML;

  /**
   * <nav> Defines navigation links
   *
   * @see https://www.w3schools.com/tags/tag_nav.asp
   */
  class Nav extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('nav', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
