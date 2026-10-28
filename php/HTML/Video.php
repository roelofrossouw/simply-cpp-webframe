<?php

  namespace HTML;

  /**
   * <video> Defines embedded video content
   *
   * @property bool $AutoPlay
   * @property bool $Controls
   * @property string|int $Height pixels
   * @property bool $Loop
   * @property bool $Muted
   * @property string $Poster url
   * @property string $Preload auto|metadata|none
   * @property string $Src url
   * @property string|int $Width pixels
   * @see https://www.w3schools.com/tags/tag_video.asp
   */
  class Video extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('video', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
