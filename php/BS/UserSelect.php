<?php

    namespace BS;

    /**
     * Whether text can be selected
     *
     * @see https://getbootstrap.com/docs/5.3/utilities/interactions/#text-selection
     */
    enum UserSelect: string
    {
        case All = 'all';
        case Auto = 'auto';
        case None = 'none';
    }
