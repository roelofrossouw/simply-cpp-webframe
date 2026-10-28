<?php

    namespace BS;

    /**
     * A vertical divider between items in a horizontal Stack or flex row
     *
     * @see https://getbootstrap.com/docs/5.3/helpers/vertical-rule/
     */
    class VerticalRule extends \HTML\ClassDiv
    {
        public function __construct()
        {
            parent::__construct('vr');
        }
    }
