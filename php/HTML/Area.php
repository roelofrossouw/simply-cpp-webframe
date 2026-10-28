<?php

  namespace HTML;

  /**
   * <area> Defines an area inside an image map
   *
   * @property string $Alt text
   * @property string $Coords coordinates
   * @property string $Download filename
   * @property string $Href url
   * @property string $HrefLang language code
   * @property string $Media media query
   * @property string $ReferrerPolicy no-referrer|no-referrer-when-downgrade|origin|origin-when-cross-origin|same-origin|strict-origin-when-cross-origin|unsafe-url
   * @property string $Rel alternate|author|bookmark|help|license|next|nofollow|noreferrer|prefetch|prev|search|tag
   * @property string $Shape default|rect|circle|poly
   * @property string $Target _blank|_parent|_self|_top|(other)
   * @property string $Type media type
   * @see https://www.w3schools.com/tags/tag_area.asp
   */
  class Area extends Element
  {
    public function __construct(?string $ID = null)
    {
      parent::__construct('area', $ID);
    }
  }
