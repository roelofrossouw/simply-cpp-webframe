<?php

  namespace HTML;

  /**
   * <mark> Defines marked/highlighted text
   *
   * @see https://www.w3schools.com/tags/tag_mark.asp
   */
  class Mark extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('mark', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
