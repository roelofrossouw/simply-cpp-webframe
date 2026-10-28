<?php

    namespace BS;

    /**
     * Text alignment (start/end follow the reading direction)
     *
     * @see https://getbootstrap.com/docs/5.3/utilities/text/#text-alignment
     */
    enum TextAlign: string
    {
        case Start = 'start';
        case Center = 'center';
        case End = 'end';
    }
