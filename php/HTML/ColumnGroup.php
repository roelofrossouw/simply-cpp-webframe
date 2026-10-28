<?php

  namespace HTML;

  /**
   * <colgroup> Specifies a group of one or more columns in a table for formatting
   *
   * @property string|int $Span number
   * @see https://www.w3schools.com/tags/tag_colgroup.asp
   */
  class ColumnGroup extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('colgroup', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
