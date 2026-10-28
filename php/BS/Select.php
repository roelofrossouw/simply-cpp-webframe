<?php

    namespace BS;

    /**
     * A drop-down list with a floating label
     *
     *   new Select('size', 'Size', ['s' => 'Small', 'l' => 'Large'], Placeholder: 'Choose a size...')
     *
     * @see https://getbootstrap.com/docs/5.3/forms/select/
     * @see https://getbootstrap.com/docs/5.3/forms/floating-labels/#selects
     */
    class Select extends \HTML\ClassDiv
    {
        use FieldHelp;

        private \HTML\Select $SelectElement;

        /**
         * @param array       $Options     Value => caption
         * @param string|null $Placeholder A first, empty choice, e.g. 'Choose...'. With Required() the user must pick
         *                                 another. (Not disabled: Safari would select the next option instead.)
         */
        public function __construct(string $Name, string $Label, array $Options, ?string $Value = null, ?string $Placeholder = null)
        {
            parent::__construct('form-floating');
            $this->AddChild($this->SelectElement = new \HTML\Select(ID: uniqid('select_')));
            $this->SelectElement->Name = $Name;
            $this->SelectElement->AddClass('form-select');
            if ($Placeholder !== null)
            {
                $this->SelectElement->AddChild($Option = new \HTML\Option($Placeholder));
                $Option->Value = '';
                $Option->Selected = $Value === null;
            }
            foreach ($Options as $OptionValue => $Caption)
            {
                $this->SelectElement->AddChild($Option = new \HTML\Option($Caption));
                $Option->Value = (string)$OptionValue;
                $Option->Selected = $Value !== null && (string)$OptionValue === $Value;
            }
            $this->AddChild(new \HTML\Label($Label, $this->SelectElement->Id));
        }

        public function SelectElement(): \HTML\Select
        {
            return $this->SelectElement;
        }

        protected function Field(): \HTML\Element
        {
            return $this->SelectElement;
        }

        protected function MessageParent(): \HTML\Element
        {
            return $this;
        }
    }
