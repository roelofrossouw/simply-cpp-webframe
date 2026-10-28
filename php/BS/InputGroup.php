<?php

    namespace BS;

    /**
     * Input group: text, buttons or floating inputs joined together on one line
     *
     * @see https://getbootstrap.com/docs/5.3/forms/input-group/
     * @see VerticalInputs for inputs stacked into one box
     */
    class InputGroup extends \HTML\ClassDiv
    {
        public function __construct()
        {
            parent::__construct('input-group');
        }
    }
