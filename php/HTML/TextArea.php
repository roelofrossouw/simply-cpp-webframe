<?php

  namespace HTML;

  /**
   * <textarea> Defines a multiline input control (text area)
   *
   * @property bool $AutoFocus
   * @property string|int $Cols number
   * @property string $DirName textareaname.dir
   * @property bool $Disabled
   * @property string $Form id
   * @property string|int $MaxLength number
   * @property string $Name text
   * @property string $Placeholder text
   * @property bool $ReadOnly
   * @property bool $Required
   * @property string|int $Rows number
   * @property string $Wrap hard|soft
   * @see https://www.w3schools.com/tags/tag_textarea.asp
   */
  class TextArea extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('textarea', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }

    /**
     * Text goes in as it is: escaped, but its newlines stay newlines (Text would add <br>, which a textarea shows)
     */
    public function AddChild(Element|string $Child): Element
    {
      // Browsers drop a newline straight after <textarea>, so the first text gets one in case it starts with its own
      return parent::AddChild(is_string($Child)
        ? new Raw((empty($this->Children()) ? "\n" : '') . htmlspecialchars($Child, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'))
        : $Child);
    }
  }
