<?php

    namespace BS;

    /**
     * Aspect ratio of embedded content
     *
     * @see https://getbootstrap.com/docs/5.3/helpers/ratio
     */
    enum AspectRatio: string
    {
        case Square = '1x1';
        case FourByThree = '4x3';
        case SixteenByNine = '16x9';
        case TwentyOneByNine = '21x9';
    }
