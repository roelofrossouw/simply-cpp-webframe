<?php

  namespace HTML;

  /**
   * <hgroup> Defines a header and related content
   *
   * @see https://www.w3schools.com/tags/tag_hgroup.asp
   */
  class HeadingGroup extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('hgroup', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
