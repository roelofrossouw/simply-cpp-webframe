<?php

  namespace HTML;

  /**
   * <picture> Defines a container for multiple image resources
   *
   * @see https://www.w3schools.com/tags/tag_picture.asp
   */
  class Picture extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('picture', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
