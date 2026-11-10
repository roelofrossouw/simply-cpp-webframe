<?php

    namespace BS;

    /**
     * A one-time code: Bootstrap 6's OTP input (one real input, drawn as a box for each character), done by js/app.js
     * on Bootstrap 5.3 with v6's markup (.otp[data-bs-otp] > .otp-input) and attributes. It posts the code as typed or
     * pasted. Phones offer a code from an SMS or email (autocomplete one-time-code), and numeric codes get the digit
     * keyboard. Fires input.bs.otpInput and complete.bs.otpInput (event.value) on the .otp.
     *
     *   $Code = new BS\OtpInput('code', 'Code from the email', 6);
     *   $Code->Groups([3, 3])->Required();
     *
     * @see https://getbootstrap.com/docs/6.0/forms/otp-input/
     */
    class OtpInput extends \HTML\ClassDiv
    {
        use FieldHelp;

        private \HTML\Input $InputElement;
        private \HTML\Div $Box;
        private string $Name;

        public function __construct(string $Name, string $Label, int $Length = 6)
        {
            parent::__construct('otp-field');
            $this->Name = $Name;
            $this->InputElement = new \HTML\Input($Name);
            $ID = $this->InputElement->GetAttribute('id');
            $this->AddChild((new \HTML\Label($Label, $ID))->AddClass('form-label'));
            $this->AddChild($this->Box = new \HTML\ClassDiv('otp'));
            $this->Box->AddAttribute('data-bs-otp');
            $this->Box->AddChild($this->InputElement);
            $this->InputElement->AddClass('otp-input');
            $this->InputElement->AddAttribute('maxlength', $Length);
            $this->InputElement->AddAttribute('autocomplete', 'one-time-code');
            $this->InputElement->AddAttribute('inputmode', 'numeric');
            $this->InputElement->AddAttribute('pattern', '[0-9]{' . $Length . '}');
            $this->InputElement->AddAttribute('aria-label', $Label);
            $this->InputElement->AddAttribute('spellcheck', 'false');
        }

        /**
         * Letters and digits (or letters only), not just digits
         */
        public function Alphanumeric(bool $LettersOnly = false): static
        {
            $Length = (int)$this->InputElement->GetAttribute('maxlength');
            $this->Box->AddAttribute('data-bs-type', $LettersOnly ? 'alpha' : 'alphanumeric');
            $this->InputElement->AddAttribute('inputmode', 'text');
            $this->InputElement->AddAttribute('pattern', ($LettersOnly ? '[A-Za-z]' : '[A-Za-z0-9]') . '{' . $Length . '}');
            return $this;
        }

        /**
         * A dot in each box instead of the character
         */
        public function Mask(): static
        {
            $this->Box->AddAttribute('data-bs-mask', 'true');
            return $this;
        }

        /**
         * The boxes in groups, e.g. [3, 3]: 123 · 456
         */
        public function Groups(array $Sizes, string $Separator = '·'): static
        {
            $this->Box->AddAttribute('data-bs-groups', json_encode(array_values(array_map('intval', $Sizes))));
            $this->Box->AddAttribute('data-bs-separator', $Separator);
            return $this;
        }

        /**
         * The boxes joined into one field
         */
        public function Connected(): static
        {
            $this->Box->AddClass('otp-connected');
            return $this;
        }

        public function Value(string $Code): void
        {
            $this->InputElement->Value($Code);
        }

        public function ShowPostedValue(): void
        {
            $this->Value((string)(\Post::Get($this->Name) ?? ''));
        }

        public function InputElement(): \HTML\Input
        {
            return $this->InputElement;
        }

        protected function Field(): \HTML\Element
        {
            return $this->InputElement;
        }

        protected function MessageParent(): \HTML\Element
        {
            return $this; // under the boxes, not among them
        }
    }
