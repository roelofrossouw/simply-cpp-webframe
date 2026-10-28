<?php

  namespace HTML;

  /**
   * <hr> Defines a thematic change in the content
   *
   * @see https://www.w3schools.com/tags/tag_hr.asp
   */
  class HorizontalRule extends Element
  {
    public function __construct(?string $ID = null)
    {
      parent::__construct('hr', $ID);
    }
  }
