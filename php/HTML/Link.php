<?php

  namespace HTML;

  /**
   * <link> Defines the relationship between a document and an external resource (most used to link to style sheets)
   *
   * @property string $CrossOrigin anonymous|use-credentials
   * @property string $Href url
   * @property string $HrefLang language code
   * @property string $Media media query
   * @property string $ReferrerPolicy no-referrer|no-referrer-when-downgrade|origin|origin-when-cross-origin|unsafe-url
   * @property string $Rel alternate|author|dns-prefetch|help|icon|license|next|pingback|preconnect|prefetch|preload|prerender|prev|search|stylesheet|manifest|apple-touch-icon|canonical|modulepreload
   * @property string $Sizes any|(other)
   * @property string $Title text
   * @property string $Type media type
   * @see https://www.w3schools.com/tags/tag_link.asp
   */
  class Link extends Element
  {


    /**
     * @param string $string
     * @param string $string1
     */
    public function __construct(string $Rel, string $Href)
    {
      parent::__construct('link');
      $this->AddAttribute('rel', $Rel);
      $this->AddAttribute('href', $Href);
    }
  }
