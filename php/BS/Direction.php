<?php

    namespace BS;

    /**
     * Bootstrap Direction (Subset of side - To apply margin or padding)
     *
     * @see https://getbootstrap.com/docs/5.3/utilities/spacing/
     */
    enum Direction
    {
        function Infix(): string
        {
            return match ($this)
            {
                self::All => '',
                self::Vertical => 'y',
                self::Horizontal => 'x',
            };
        }

        case All;
        case Vertical;
        case Horizontal;
    }