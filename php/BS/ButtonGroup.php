<?php

    namespace BS;

    /**
     * Buttons joined together on one line (or stacked)
     *
     * @see https://getbootstrap.com/docs/5.3/components/button-group/
     */
    class ButtonGroup extends \HTML\Div
    {
        public function __construct(string $Label, ?Size $Size = null)
        {
            parent::__construct();
            $this->AddClass('btn-group', 'btn-group');
            $this->AddAttribute('role', 'group');
            $this->AddAttribute('aria-label', $Label);
            if ($Size !== null)
            {
                $this->AddClass('btn-group-' . $Size->value, 'btn-group-size');
            }
        }

        public function Vertical(): static
        {
            $this->AddClass('btn-group-vertical', 'btn-group');
            return $this;
        }
    }
