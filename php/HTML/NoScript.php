<?php

  namespace HTML;

  /**
   * <noscript> Defines an alternate content for users that do not support client-side scripts
   *
   * @see https://www.w3schools.com/tags/tag_noscript.asp
   */
  class NoScript extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('noscript', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
