<?php

  namespace HTML;

  /**
   * <base> Specifies the base URL/target for all relative URLs in a document
   *
   * @property string $Href url
   * @property string $Target _blank|_parent|_self|_top|(other)
   * @see https://www.w3schools.com/tags/tag_base.asp
   */
  class Base extends Element
  {
    public function __construct(?string $ID = null)
    {
      parent::__construct('base', $ID);
    }
  }
