<?php

  namespace HTML;

  /**
   * <blockquote> Defines a section that is quoted from another source
   *
   * @property string $Cite url
   * @see https://www.w3schools.com/tags/tag_blockquote.asp
   */
  class BlockQuote extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('blockquote', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
