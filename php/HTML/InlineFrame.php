<?php

  namespace HTML;

  /**
   * <iframe> Defines an inline frame
   *
   * @property string $Allow text
   * @property string $AllowFullscreen true|false
   * @property string|int $Height pixels
   * @property string $Loading eager|lazy
   * @property string $Name text
   * @property string $ReferrerPolicy no-referrer|no-referrer-when-downgrade|origin|origin-when-cross-origin|same-origin|strict-origin-when-cross-origin|unsafe-url
   * @property string $Sandbox allow-forms|allow-pointer-lock|allow-popups|allow-same-origin|allow-scripts|allow-top-navigation
   * @property string $Src url
   * @property string $SrcDoc html code
   * @property string|int $Width pixels
   * @see https://www.w3schools.com/tags/tag_iframe.asp
   */
  class InlineFrame extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('iframe', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
