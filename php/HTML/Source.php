<?php

  namespace HTML;

  /**
   * <source> Defines multiple media resources for media elements (<video> and <audio>)
   *
   * @property string $Media media query
   * @property string $Sizes text
   * @property string $Src url
   * @property string $SrcSet url
   * @property string $Type mime-type
   * @see https://www.w3schools.com/tags/tag_source.asp
   */
  class Source extends Element
  {
    public function __construct(?string $ID = null)
    {
      parent::__construct('source', $ID);
    }
  }
