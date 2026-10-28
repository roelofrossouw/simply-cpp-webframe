<?php

    namespace HTML;

    /**
     * <img> Defines an image
     *
     * @property string $Alt text
     * @property string $CrossOrigin anonymous|use-credentials
     * @property string|int $Height pixels
     * @property bool $IsMap
     * @property string $Loading eager|lazy
     * @property string $LongDesc url
     * @property string $ReferrerPolicy no-referrer|no-referrer-when-downgrade|origin|origin-when-cross-origin|unsafe-url
     * @property string $Sizes sizes
     * @property string $Src url
     * @property string $SrcSet url-list
     * @property string $UseMap #mapname
     * @property string|int $Width pixels
     * @see https://www.w3schools.com/tags/tag_img.asp
     */
    class Image extends Element
    {
        public function __construct(string $Src, ?string $Alt = null)
        {
            parent::__construct('img');
            $this->AddAttribute('src', $Src);
            $this->AddAttribute('alt', $Alt);
        }
    }