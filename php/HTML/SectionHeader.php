<?php

  namespace HTML;

  /**
   * <header> Defines a header for a document or section
   *
   * @see https://www.w3schools.com/tags/tag_header.asp
   */
  class SectionHeader extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('header', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
