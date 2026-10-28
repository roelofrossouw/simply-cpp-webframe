<?php

  namespace HTML;

  /**
   * <u> Defines some text that is unarticulated and styled differently from normal text
   *
   * @see https://www.w3schools.com/tags/tag_u.asp
   */
  class Underline extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('u', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
