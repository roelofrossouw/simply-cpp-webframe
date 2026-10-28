<?php

  namespace HTML;

  /**
   * <aside> Defines content aside from the page content
   *
   * @see https://www.w3schools.com/tags/tag_aside.asp
   */
  class Aside extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('aside', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
