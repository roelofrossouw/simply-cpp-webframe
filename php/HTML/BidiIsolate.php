<?php

  namespace HTML;

  /**
   * <bdi> Isolates a part of text that might be formatted in a different direction from other text outside it
   *
   * @see https://www.w3schools.com/tags/tag_bdi.asp
   */
  class BidiIsolate extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('bdi', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
