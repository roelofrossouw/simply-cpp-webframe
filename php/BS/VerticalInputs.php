<?php

    namespace BS;

    /**
     * Inputs stacked into one box, e.g. email and password on a login form (styled by .vertical-inputs in style.css)
     */
    class VerticalInputs extends \HTML\ClassDiv
    {
        public function __construct()
        {
            parent::__construct('vertical-inputs');
        }
    }
