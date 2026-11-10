<?php

    namespace BS;

    /**
     * What a BS\DatePicker chooses: one date, several, or a range (its first and last day)
     */
    enum DateSelection: string
    {
        case Single = 'single';
        case Multiple = 'multiple';
        case Range = 'multiple-ranged';
    }
