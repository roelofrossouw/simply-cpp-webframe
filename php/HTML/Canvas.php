<?php

  namespace HTML;

  /**
   * <canvas> Used to draw graphics, on the fly, via scripting (usually JavaScript)
   *
   * @property string|int $Height pixels
   * @property string|int $Width pixels
   * @see https://www.w3schools.com/tags/tag_canvas.asp
   */
  class Canvas extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('canvas', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
