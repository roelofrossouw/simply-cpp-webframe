<?php

    namespace BS;

    /**
     * Text capitalization
     *
     * @see https://getbootstrap.com/docs/5.3/utilities/text/#text-transform
     */
    enum TextTransform: string
    {
        case Lowercase = 'lowercase';
        case Uppercase = 'uppercase';
        case Capitalize = 'capitalize';
    }
