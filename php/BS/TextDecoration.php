<?php

    namespace BS;

    /**
     * Text decoration
     *
     * @see https://getbootstrap.com/docs/5.3/utilities/text/#text-decoration
     */
    enum TextDecoration: string
    {
        case Underline = 'underline';
        case LineThrough = 'line-through';
        case None = 'none';
    }
