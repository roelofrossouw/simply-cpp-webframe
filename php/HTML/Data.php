<?php

  namespace HTML;

  /**
   * <data> Adds a machine-readable translation of a given content
   *
   * @property string $Value machine-readable format
   * @see https://www.w3schools.com/tags/tag_data.asp
   */
  class Data extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('data', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
