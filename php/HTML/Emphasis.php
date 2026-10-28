<?php

  namespace HTML;

  /**
   * <em> Defines emphasized text
   *
   * @see https://www.w3schools.com/tags/tag_em.asp
   */
  class Emphasis extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('em', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
