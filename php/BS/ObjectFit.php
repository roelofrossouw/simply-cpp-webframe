<?php

    namespace BS;

    /**
     * How an image or video fills its box
     *
     * @see https://getbootstrap.com/docs/5.3/utilities/object-fit
     */
    enum ObjectFit: string
    {
        case Contain = 'contain';
        case Cover = 'cover';
        case Fill = 'fill';
        case ScaleDown = 'scale';
        case None = 'none';
    }
