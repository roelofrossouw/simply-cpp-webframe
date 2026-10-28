<?php

  namespace HTML;

  /**
   * <footer> Defines a footer for a document or section
   *
   * @see https://www.w3schools.com/tags/tag_footer.asp
   */
  class SectionFooter extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('footer', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
