<?php

    namespace BS;

    /**
     * Placement of flex items along the main axis
     *
     * @see https://getbootstrap.com/docs/5.3/utilities/flex/#justify-content
     */
    enum Justify: string
    {
        case Start = 'start';
        case End = 'end';
        case Center = 'center';
        case Between = 'between';
        case Around = 'around';
        case Evenly = 'evenly';
    }
