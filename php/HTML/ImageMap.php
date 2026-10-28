<?php

  namespace HTML;

  /**
   * <map> Defines an image map
   *
   * @property string $Name text
   * @see https://www.w3schools.com/tags/tag_map.asp
   */
  class ImageMap extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('map', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
