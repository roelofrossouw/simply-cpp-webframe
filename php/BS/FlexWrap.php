<?php

    namespace BS;

    /**
     * Whether flex items wrap onto more lines
     *
     * @see https://getbootstrap.com/docs/5.3/utilities/flex/#wrap
     */
    enum FlexWrap: string
    {
        case Wrap = 'wrap';
        case NoWrap = 'nowrap';
        case WrapReverse = 'wrap-reverse';
    }
