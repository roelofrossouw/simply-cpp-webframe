<?php

  namespace HTML;

  /**
   * <dialog> Defines a dialog box or window
   *
   * @property bool $Open
   * @see https://www.w3schools.com/tags/tag_dialog.asp
   */
  class Dialog extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('dialog', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
