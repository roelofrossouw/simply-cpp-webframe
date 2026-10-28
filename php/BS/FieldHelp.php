<?php

    namespace BS;

    /**
     * Help text and validation messages for a form field
     *
     * The messages show once the form is checked (app.js adds was-validated when it's sent).
     *
     * @see https://getbootstrap.com/docs/5.3/forms/form-control/#form-text
     * @see https://getbootstrap.com/docs/5.3/forms/validation/
     */
    trait FieldHelp
    {
        /**
         * The input, select or textarea itself
         */
        abstract protected function Field(): \HTML\Element;

        /**
         * Where the messages go: next to the field, so Bootstrap's :invalid ~ .invalid-feedback rule finds them
         */
        abstract protected function MessageParent(): \HTML\Element;

        /**
         * A hint under the field, also read out by screen readers when the field gets focus
         */
        public function Help(string $Text): static
        {
            $ID = uniqid('help_');
            $this->AddChild((new \HTML\Div($ID, $Text))->AddClass('form-text'));
            $this->Field()->AddAttribute('aria-describedby', $ID);
            return $this;
        }

        /**
         * @param string      $Invalid Shown when the value isn't valid, e.g. 'Please choose a reason.'
         * @param string|null $Valid   Shown when it is, e.g. 'Looks good!'
         */
        public function Feedback(string $Invalid, ?string $Valid = null): static
        {
            $this->MessageParent()->AddChild((new \HTML\Div(Content: $Invalid))->AddClass('invalid-feedback'));
            if ($Valid !== null)
            {
                $this->MessageParent()->AddChild((new \HTML\Div(Content: $Valid))->AddClass('valid-feedback'));
            }
            return $this;
        }

        public function Required(): static
        {
            $this->Field()->AddAttribute('required');
            return $this;
        }

        public function Disabled(): static
        {
            $this->Field()->AddAttribute('disabled');
            return $this;
        }
    }
