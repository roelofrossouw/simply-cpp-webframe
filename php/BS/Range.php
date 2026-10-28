<?php

    namespace BS;

    /**
     * A slider, with its label above it
     *
     * @see https://getbootstrap.com/docs/5.3/forms/range/
     */
    class Range extends \HTML\Div
    {
        use FieldHelp;

        private \HTML\Input $Input;

        public function __construct(string $Name, string $Label, int|float $Min = 0, int|float $Max = 100, int|float $Step = 1, int|float|null $Value = null)
        {
            parent::__construct();
            $this->Input = new \HTML\Input($Name, \HTML\InputType::Range);
            $this->AddChild((new \HTML\Label($Label, $this->Input->Id))->AddClass('form-label'));
            $this->AddChild($this->Input);
            $this->Input->AddClass('form-range');
            $this->Input->Min = $Min;
            $this->Input->Max = $Max;
            $this->Input->Step = $Step;
            if ($Value !== null)
            {
                $this->Input->Value = $Value;
            }
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
