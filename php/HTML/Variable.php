<?php

  namespace HTML;

  /**
   * <var> Defines a variable
   *
   * @see https://www.w3schools.com/tags/tag_var.asp
   */
  class Variable extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('var', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
