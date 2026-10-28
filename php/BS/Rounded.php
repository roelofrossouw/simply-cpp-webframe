<?php

    namespace BS;

    /**
     * Corner rounding
     *
     * @see https://getbootstrap.com/docs/5.3/utilities/borders/#radius
     */
    enum Rounded: string
    {
        case None = '0';
        case Small = '1';
        case Regular = '';
        case Medium = '2';
        case Large = '3';
        case ExtraLarge = '4';
        case ExtraExtraLarge = '5';
        case Circle = 'circle';
        case Pill = 'pill';
    }
