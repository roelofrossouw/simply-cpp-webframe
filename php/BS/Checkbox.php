<?php

    namespace BS;

    /**
     * A checkbox with its label
     *
     * @see https://getbootstrap.com/docs/5.3/forms/checks-radios/
     */
    class Checkbox extends \HTML\ClassDiv
    {
        use FieldHelp;

        protected \HTML\Input $Input;

        public function __construct(string $Name, string $Label, bool $Checked = false, string $Value = '1', \HTML\InputType $Type = \HTML\InputType::Checkbox)
        {
            parent::__construct('form-check');
            $this->AddChild($this->Input = new \HTML\Input($Name, $Type));
            $this->Input->AddClass('form-check-input');
            $this->Input->Value = $Value;
            if ($Checked)
            {
                $this->Input->AddAttribute('checked');
            }
            $this->AddChild((new \HTML\Label($Label, $this->Input->Id))->AddClass('form-check-label'));
        }

        /**
         * Side by side with the next ones, instead of stacked
         */
        public function Inline(): static
        {
            $this->AddClass('form-check-inline');
            return $this;
        }

        public function InputElement(): \HTML\Input
        {
            return $this->Input;
        }

        protected function Field(): \HTML\Element
        {
            return $this->Input;
        }

        protected function MessageParent(): \HTML\Element
        {
            return $this;
        }
    }
