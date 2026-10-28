<?php

  namespace HTML;

  /**
   * <legend> Defines a caption for a <fieldset> element
   *
   * @see https://www.w3schools.com/tags/tag_legend.asp
   */
  class Legend extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('legend', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
