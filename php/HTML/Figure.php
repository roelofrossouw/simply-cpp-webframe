<?php

  namespace HTML;

  /**
   * <figure> Specifies self-contained content
   *
   * @see https://www.w3schools.com/tags/tag_figure.asp
   */
  class Figure extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('figure', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
