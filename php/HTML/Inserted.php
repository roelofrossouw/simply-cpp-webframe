<?php

  namespace HTML;

  /**
   * <ins> Defines a text that has been inserted into a document
   *
   * @property string $Cite url
   * @property string $DateTime datetime
   * @see https://www.w3schools.com/tags/tag_ins.asp
   */
  class Inserted extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('ins', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
