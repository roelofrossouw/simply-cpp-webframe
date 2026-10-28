<?php

    namespace BS;

    /**
     * CSS position
     *
     * @see https://getbootstrap.com/docs/5.3/utilities/position
     */
    enum Position: string
    {
        case Static = 'static';
        case Relative = 'relative';
        case Absolute = 'absolute';
        case Fixed = 'fixed';
        case Sticky = 'sticky';
    }
