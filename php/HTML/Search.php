<?php

  namespace HTML;

  /**
   * <search> Defines a search section
   *
   * @see https://www.w3schools.com/tags/tag_search.asp
   */
  class Search extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('search', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
