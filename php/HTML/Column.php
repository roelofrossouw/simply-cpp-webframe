<?php

  namespace HTML;

  /**
   * <col> Specifies column properties for each column within a <colgroup> element
   *
   * @property string|int $Span number
   * @see https://www.w3schools.com/tags/tag_col.asp
   */
  class Column extends Element
  {
    public function __construct(?string $ID = null)
    {
      parent::__construct('col', $ID);
    }
  }
