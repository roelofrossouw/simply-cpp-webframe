<?php

    namespace BS;

    /**
     * @see https://getbootstrap.com/docs/5.3/utilities/overflow/
     */
    enum Overflow: string
    {
        case Hidden = 'hidden';
        case Visible = 'visible';
        case Scroll = 'scroll';
        case Automatic = 'auto';
    }
