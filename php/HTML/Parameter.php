<?php

  namespace HTML;

  /**
   * <param> Defines a parameter for an object
   *
   * @property string $Name text
   * @property string $Value text
   * @see https://www.w3schools.com/tags/tag_param.asp
   */
  class Parameter extends Element
  {
    public function __construct(?string $ID = null)
    {
      parent::__construct('param', $ID);
    }
  }
