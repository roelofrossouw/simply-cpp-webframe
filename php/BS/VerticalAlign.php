<?php

    namespace BS;

    /**
     * Vertical alignment of inline and table cell elements
     *
     * @see https://getbootstrap.com/docs/5.3/utilities/vertical-align
     */
    enum VerticalAlign: string
    {
        case Baseline = 'baseline';
        case Top = 'top';
        case Middle = 'middle';
        case Bottom = 'bottom';
        case TextTop = 'text-top';
        case TextBottom = 'text-bottom';
    }
