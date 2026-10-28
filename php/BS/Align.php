<?php

    namespace BS;

    /**
     * Placement of flex items across the main axis
     *
     * @see https://getbootstrap.com/docs/5.3/utilities/flex/#align-items
     */
    enum Align: string
    {
        case Start = 'start';
        case End = 'end';
        case Center = 'center';
        case Baseline = 'baseline';
        case Stretch = 'stretch';
    }
