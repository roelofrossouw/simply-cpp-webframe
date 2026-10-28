<?php

  namespace HTML;

  /**
   * <progress> Represents the progress of a task
   *
   * @property string|int $Max number
   * @property string|int $Value number
   * @see https://www.w3schools.com/tags/tag_progress.asp
   */
  class Progress extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('progress', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
