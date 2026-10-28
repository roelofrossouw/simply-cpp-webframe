<?php

  namespace HTML;

  /**
   * <del> Defines text that has been deleted from a document
   *
   * @property string $Cite url
   * @property string $DateTime datetime
   * @see https://www.w3schools.com/tags/tag_del.asp
   */
  class Deleted extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('del', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
