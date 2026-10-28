<?php

  namespace HTML;

  /**
   * <svg> Defines a container for SVG graphics
   *
   * @see https://www.w3schools.com/tags/tag_svg.asp
   */
  class Svg extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('svg', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
