<?php

    namespace BS;

    /**
     * A number with − and + buttons beside it and its unit after it: shown as the page's language writes numbers
     * (1 234,5 or 1,234.5), posted plain (1234.5) in a hidden field of the given name. js/app.js does the formatting,
     * the buttons and typing (either decimal mark is taken); Min() and Max() bound it, Step() is what the buttons add,
     * Decimals() how many are shown and posted. A typed value outside the bounds makes the field invalid.
     *
     *   $Quantity = new BS\NumberInput('quantity', 'Quantity', 1);
     *   $Quantity->Min(1)->Max(99);
     *   $Mass = new BS\NumberInput('mass', 'Mass', 12.5, 'kg');
     *   $Mass->Decimals(1)->Step(0.5)->Min(0);
     */
    class NumberInput extends Input
    {
        private \HTML\Input $ValueInput;

        public function __construct(string $Name, string $Label, int|float|string|null $Value = null, ?string $Unit = null)
        {
            parent::__construct($Name, $Label, Suffix: $Unit);
            $this->AddClass('number-field');
            $Field = $this->InputElement();
            $Field->RemoveAttribute('name');
            $ID = $Field->GetAttribute('id');
            $Field->AddAttribute('inputmode', 'decimal');
            $Field->AddAttribute('autocomplete', 'off');
            $Field->AddAttribute('data-bs-toggle', 'number');
            $Field->AddAttribute('data-bs-value-input', '#' . $ID . '_value');
            $Field->AddSibling($this->ValueInput = new \HTML\Input($Name, \HTML\InputType::Hidden));
            $this->ValueInput->AddAttribute('id', $ID . '_value');

            $Group = $this->Group();
            $Group->PrependChild($this->Stepper('-', Icon::Dash, 'Less', $ID));
            $Group->AddChild($this->Stepper('+', Icon::Plus, 'More', $ID));
            $this->Value($Value);
        }

        private function Stepper(string $Direction, Icon $Icon, string $Caption, string $ID): \HTML\Button
        {
            $Button = new \HTML\Button($Icon->Element());
            $Button->AddAttribute('type', 'button');
            $Button->AddClasses(['btn', 'btn-outline-secondary', 'number-step']);
            $Button->AddAttribute('data-bs-step-direction', $Direction);
            $Button->AddAttribute('data-bs-target', '#' . $ID);
            $Button->AddAttribute('aria-label', $Caption);
            $Button->AddAttribute('tabindex', '-1');
            return $Button;
        }

        /**
         * The number, plain: 1234.5
         */
        public function Value(mixed $Value): void
        {
            $Text = $Value === null ? '' : trim((string)$Value);
            $this->ValueInput->Value($Text);
            parent::Value($Text); // written out by the script
        }

        public function ShowPostedValue(): void
        {
            $this->Value(\Post::Get($this->ValueInput->GetAttribute('name')));
        }

        public function Min(int|float $Min): static
        {
            $this->InputElement()->AddAttribute('data-bs-min', $Min);
            return $this;
        }

        public function Max(int|float $Max): static
        {
            $this->InputElement()->AddAttribute('data-bs-max', $Max);
            return $this;
        }

        /**
         * What − and + take off and add (1 by default)
         */
        public function Step(int|float $Step): static
        {
            $this->InputElement()->AddAttribute('data-bs-step', $Step);
            return $this;
        }

        /**
         * How many decimals are shown and posted (rounded); without it, as typed
         */
        public function Decimals(int $Count): static
        {
            $this->InputElement()->AddAttribute('data-bs-decimals', $Count);
            return $this;
        }

        public function ValueElement(): \HTML\Input
        {
            return $this->ValueInput;
        }
    }
