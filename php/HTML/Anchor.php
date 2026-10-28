<?php

    namespace HTML;

    /**
     * <a> Defines a hyperlink
     *
     * @property string $Download filename
     * @property string $Href url
     * @property string $HrefLang language code
     * @property string $Media media query
     * @property string $Ping list of urls
     * @property string $ReferrerPolicy no-referrer|no-referrer-when-downgrade|origin|origin-when-cross-origin|same-origin|strict-origin-when-cross-origin|unsafe-url
     * @property string $Rel alternate|author|bookmark|external|help|license|next|nofollow|noreferrer|noopener|prev|search|tag
     * @property string $Target _blank|_parent|_self|_top|(other)
     * @property string $Type media type
     * @see https://www.w3schools.com/tags/tag_a.asp
     */
    class Anchor extends Element
    {
        /**
         * @param Element|string|null $Content
         * @param string|null         $Href
         * @param string|null         $ID
         * @param bool                $IsLink Set this to true for external links (see \BS\Button::Link())
         */
        public function __construct(Element|string|null $Content = null, ?string $Href = null, ?string $ID = null, bool $IsLink = false)
        {
            parent::__construct('a', $ID);
            if (!empty($Content))
            {
                $this->AddChild($Content);
            }
            if (!empty($Href))
            {
                if ($IsLink)
                {
                    $this->AddAttribute('href', $Href);
                }
                else
                {
                    $this->AddAttribute('href', '#');
                    $this->Action($Href);
                }
            }
        }
    }