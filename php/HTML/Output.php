<?php

  namespace HTML;

  /**
   * <output> Defines the result of a calculation
   *
   * @property string $For id
   * @property string $Form id
   * @property string $Name text
   * @see https://www.w3schools.com/tags/tag_output.asp
   */
  class Output extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('output', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
