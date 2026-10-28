<?php

  namespace HTML;

  /**
   * <section> Defines a section in a document
   *
   * @see https://www.w3schools.com/tags/tag_section.asp
   */
  class Section extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('section', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
