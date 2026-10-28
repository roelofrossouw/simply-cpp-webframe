<?php

  namespace HTML;

  /**
   * <time> Defines a specific time (or datetime)
   *
   * @property string $DateTime datetime
   * @see https://www.w3schools.com/tags/tag_time.asp
   */
  class Time extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('time', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
