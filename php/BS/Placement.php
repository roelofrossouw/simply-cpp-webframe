<?php

    namespace BS;

    /**
     * Where a tooltip or popover appears
     *
     * @see https://getbootstrap.com/docs/5.3/components/tooltips/#directions
     */
    enum Placement: string
    {
        case Top = 'top';
        case Bottom = 'bottom';
        case Start = 'left';
        case End = 'right';
    }
