<?php

  namespace HTML;

  /**
   * <wbr> Defines a possible line-break
   *
   * @see https://www.w3schools.com/tags/tag_wbr.asp
   */
  class WordBreak extends Element
  {
    public function __construct(?string $ID = null)
    {
      parent::__construct('wbr', $ID);
    }
  }
