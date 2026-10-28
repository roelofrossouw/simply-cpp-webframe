<?php

  namespace HTML;

  /**
   * <template> Defines a container for content that should be hidden when the page loads
   *
   * @see https://www.w3schools.com/tags/tag_template.asp
   */
  class Template extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('template', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
