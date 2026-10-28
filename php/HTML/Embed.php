<?php

  namespace HTML;

  /**
   * <embed> Defines a container for an external application
   *
   * @property string|int $Height pixels
   * @property string $Src url
   * @property string $Type media type
   * @property string|int $Width pixels
   * @see https://www.w3schools.com/tags/tag_embed.asp
   */
  class Embed extends Element
  {
    public function __construct(?string $ID = null)
    {
      parent::__construct('embed', $ID);
    }
  }
