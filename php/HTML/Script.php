<?php

    namespace HTML;

    /**
     * <script> Defines a client-side script
     *
     * @property bool $Async
     * @property string $Charset text
     * @property string $CrossOrigin anonymous|use-credentials
     * @property bool $Defer
     * @property string $Integrity filehash
     * @property bool $NoModule
     * @property string $ReferrerPolicy no-referrer|no-referrer-when-downgrade|origin|origin-when-cross-origin|same-origin|strict-origin|strict-origin-when-cross-origin|unsafe-url
     * @property string $Src url
     * @property string $Type scripttype
     * @see https://www.w3schools.com/tags/tag_script.asp
     */
    class Script extends Element
    {
        public function __construct(?string $Script = null, ?string $Source = null)
        {
            parent::__construct('script');
            if (!empty($Source))
            {
                $this->AddAttribute('src', $Source);
            }
            if (!empty($Script))
            {
                $this->AddChild(new Raw($Script));
            }
        }
    }