<?php

  namespace HTML;

  /**
   * <cite> Defines the title of a work
   *
   * @see https://www.w3schools.com/tags/tag_cite.asp
   */
  class Citation extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('cite', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
