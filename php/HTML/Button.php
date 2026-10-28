<?php

  namespace HTML;

  /**
   * <button> Defines a clickable button
   *
   * @property bool $AutoFocus
   * @property string $Command show-modal|close|request-close|show-popover|hide-popover|toggle-popover|(other)
   * @property string $CommandFor text
   * @property bool $Disabled
   * @property string $Form id
   * @property string $FormAction url
   * @property string $FormEnctype application/x-www-form-urlencoded|multipart/form-data|text/plain
   * @property string $FormMethod get|post
   * @property bool $FormNoValidate
   * @property string $FormTarget _blank|_self|_parent|_top|(other)
   * @property string $Name text
   * @property string $PopoverTarget id
   * @property string $PopoverTargetAction hide|show|toggle
   * @property string $Type button|reset|submit
   * @property string $Value text
   * @see https://www.w3schools.com/tags/tag_button.asp
   */
  class Button extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('button', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
