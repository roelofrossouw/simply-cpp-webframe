<?php

  namespace HTML;

  /**
   * <dfn> Specifies a term that is going to be defined within the content
   *
   * @see https://www.w3schools.com/tags/tag_dfn.asp
   */
  class Definition extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('dfn', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
