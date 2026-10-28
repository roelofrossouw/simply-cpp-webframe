<?php

  namespace HTML;

  /**
   * <audio> Defines embedded sound content
   *
   * @property bool $AutoPlay
   * @property bool $Controls
   * @property bool $Loop
   * @property bool $Muted
   * @property string $Preload auto|metadata|none
   * @property string $Src url
   * @see https://www.w3schools.com/tags/tag_audio.asp
   */
  class Audio extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('audio', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
