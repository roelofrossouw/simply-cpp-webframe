<?php

    namespace BS;

    /**
     * One choice of a radio group; give the choices of a group the same name
     *
     *   foreach (['daily' => 'Daily', 'weekly' => 'Weekly'] as $Value => $Label)
     *     $Grid->Col(new Radio('frequency', $Label, $Value, $Value == $Current));
     *
     * @see https://getbootstrap.com/docs/5.3/forms/checks-radios/#radios
     */
    class Radio extends Checkbox
    {
        public function __construct(string $Name, string $Label, string $Value, bool $Checked = false)
        {
            parent::__construct($Name, $Label, $Checked, $Value, \HTML\InputType::Radio);
        }
    }
