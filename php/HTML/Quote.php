<?php

  namespace HTML;

  /**
   * <q> Defines a short quotation
   *
   * @property string $Cite url
   * @see https://www.w3schools.com/tags/tag_q.asp
   */
  class Quote extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('q', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
