<?php

  namespace HTML;

  /**
   * <address> Defines contact information for the author/owner of a document
   *
   * @see https://www.w3schools.com/tags/tag_address.asp
   */
  class Address extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('address', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
