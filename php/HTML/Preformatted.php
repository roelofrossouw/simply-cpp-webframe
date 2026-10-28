<?php

  namespace HTML;

  /**
   * <pre> Defines preformatted text
   *
   * @see https://www.w3schools.com/tags/tag_pre.asp
   */
  class Preformatted extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('pre', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }

    /**
     * Text goes in as it is: escaped, but its newlines stay newlines (Text would add <br>, doubling the lines)
     */
    public function AddChild(Element|string $Child): Element
    {
      // Browsers drop a newline straight after <pre>, so the first text gets one in case it starts with its own
      return parent::AddChild(is_string($Child)
        ? new Raw((empty($this->Children()) ? "\n" : '') . htmlspecialchars($Child, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'))
        : $Child);
    }
  }
