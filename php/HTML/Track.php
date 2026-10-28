<?php

  namespace HTML;

  /**
   * <track> Defines text tracks for media elements (<video> and <audio>)
   *
   * @property bool $Default
   * @property string $Kind captions|chapters|descriptions|metadata|subtitles
   * @property string $Label text
   * @property string $Src url
   * @property string $SrcLang language code
   * @see https://www.w3schools.com/tags/tag_track.asp
   */
  class Track extends Element
  {
    public function __construct(?string $ID = null)
    {
      parent::__construct('track', $ID);
    }
  }
