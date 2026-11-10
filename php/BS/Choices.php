<?php

    namespace BS;

    /**
     * A group of choices under one label: checkboxes, posting name[] (Post::Get('name') is an array of the values
     * ticked), or with Single() radios, posting name. Stacked, side by side with Inline(), or as a row of toggle
     * buttons with Buttons() (Bootstrap's btn-check). Required(): at least one ticked (js/app.js checks the boxes;
     * radios the browser does).
     *
     *   $Days = new BS\Choices('days', 'Days', ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday'], ['mon']);
     *   $Days->Inline()->Required();
     *   $Size = new BS\Choices('size', 'Size', ['s' => 'Small', 'm' => 'Medium', 'l' => 'Large'], 'm', Single: true);
     *   $Size->Buttons();
     *
     * @see https://getbootstrap.com/docs/5.3/forms/checks-radios/
     */
    class Choices extends \HTML\FieldSet
    {
        use FieldHelp;

        private string $Name;
        private bool $Single;
        /** @var \HTML\Input[] */
        private array $Inputs = [];
        /** @var \HTML\Element[] each choice's wrapper (.form-check) or label (a button) */
        private array $Wrappers = [];
        private \HTML\Div $Options;

        /**
         * @param array             $Options Value => caption
         * @param array|string|null $Values  The values ticked at first (Single: the one)
         */
        public function __construct(string $Name, string $Label, array $Options, array|string|null $Values = [], bool $Single = false)
        {
            parent::__construct();
            $this->AddClass('choices');
            $this->Name = $Name;
            $this->Single = $Single;
            $this->AddAttribute('data-bs-toggle', 'choices');
            $this->AddChild((new \HTML\Legend($Label))->AddClasses(['form-label', 'fs-6']));
            $this->AddChild($this->Options = new \HTML\ClassDiv('choices-options'));
            foreach ($Options as $Value => $Caption)
            {
                $Input = new \HTML\Input($Single ? $Name : $Name . '[]', $Single ? \HTML\InputType::Radio : \HTML\InputType::Checkbox);
                $Input->Value = (string)$Value;
                $Input->AddClass('form-check-input');
                $Wrapper = $this->Options->AddChild(new \HTML\ClassDiv('form-check'));
                $Wrapper->AddChild($Input);
                $Wrapper->AddChild((new \HTML\Label((string)$Caption, $Input->GetAttribute('id')))->AddClass('form-check-label'));
                $this->Inputs[] = $Input;
                $this->Wrappers[] = $Wrapper;
            }
            $this->Value($Values);
        }

        /**
         * Side by side instead of stacked
         */
        public function Inline(): static
        {
            foreach ($this->Wrappers as $Wrapper)
            {
                $Wrapper->AddClass('form-check-inline');
            }
            return $this;
        }

        /**
         * A row of toggle buttons (Bootstrap's btn-check), in this colour's outline
         */
        public function Buttons(Color $Color = Color::Primary): static
        {
            $this->AddClass('choices-buttons');
            foreach ($this->Wrappers as $Index => $Wrapper)
            {
                $Wrapper->RemoveClass('form-check');
                $Wrapper->RemoveClass('form-check-inline');
                $Wrapper->AddClass('choices-button');
                $Input = $this->Inputs[$Index];
                $Input->RemoveClass('form-check-input');
                $Input->AddClass('btn-check');
                $Input->AddAttribute('autocomplete', 'off');
                foreach ($Wrapper->Children() as $Child)
                {
                    if ($Child instanceof \HTML\Label)
                    {
                        $Child->RemoveClass('form-check-label');
                        $Child->AddClasses(['btn', 'btn-outline-' . $Color->value]);
                    }
                }
            }
            return $this;
        }

        /**
         * At least one ticked: radios by the browser, checkboxes by js/app.js
         */
        public function Required(): static
        {
            $this->AddAttribute('data-bs-required', 'true');
            if ($this->Single && $this->Inputs)
            {
                $this->Inputs[0]->AddAttribute('required');
            }
            return $this;
        }

        public function Disabled(): static
        {
            $this->AddAttribute('disabled');
            return $this;
        }

        /**
         * The values ticked (Single: one value)
         */
        public function Value(array|string|null $Values): void
        {
            $Values = array_map('strval', is_array($Values) ? $Values : ($Values === null ? [] : [$Values]));
            foreach ($this->Inputs as $Input)
            {
                $Input->Checked = in_array($Input->GetAttribute('value'), $Values, true);
            }
        }

        public function ShowPostedValue(): void
        {
            $this->Value(\Post::Get($this->Name));
        }

        /** @return \HTML\Input[] */
        public function Inputs(): array
        {
            return $this->Inputs;
        }

        protected function Field(): \HTML\Element
        {
            return $this->Inputs[0] ?? $this;
        }

        protected function MessageParent(): \HTML\Element
        {
            return $this;
        }
    }
