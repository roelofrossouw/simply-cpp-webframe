<?php

  namespace HTML;

  /**
   * <figcaption> Defines a caption for a <figure> element
   *
   * @see https://www.w3schools.com/tags/tag_figcaption.asp
   */
  class FigureCaption extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('figcaption', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
