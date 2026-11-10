<?php

    namespace BS;

    /**
     * A password field with a button that shows or hides what's typed, and with Strength() a meter under it, as
     * Bootstrap 6's password Strength has it (.strength[data-bs-strength] with four segments, levels weak, fair, good
     * and strong, scored as Bootstrap scores them), done by js/app.js on Bootstrap 5.3. The meter only advises: the
     * page that takes the password checks it.
     *
     *   $Password = new BS\PasswordInput('password', 'Password', 'new-password');
     *   $Password->Strength(10)->Required();
     *
     * @see https://getbootstrap.com/docs/6.0/forms/password-strength/
     */
    class PasswordInput extends Input
    {
        public function __construct(string $Name, string $Label, string $AutoComplete = 'current-password')
        {
            parent::__construct($Name, $Label, \HTML\InputType::Password);
            $this->AddClass('password-field');
            $Field = $this->InputElement();
            $Field->AddAttribute('autocomplete', $AutoComplete);
            $Toggle = new \HTML\Button(Icon::Eye->Element());
            $Toggle->AddAttribute('type', 'button');
            $Toggle->AddClasses(['btn', 'btn-outline-secondary', 'password-toggle']);
            $Toggle->AddAttribute('data-bs-toggle', 'password');
            $Toggle->AddAttribute('data-bs-target', '#' . $Field->GetAttribute('id'));
            $Toggle->AddAttribute('aria-label', 'Show the password');
            $Toggle->AddAttribute('aria-pressed', 'false');
            $this->Group()->AddChild($Toggle);
        }

        /**
         * A strength meter under the field. MinLength: the length a password needs (the meter's first point, and the
         * field's minlength).
         *
         * @param array $Messages The words for each level: ['weak' => ..., 'fair' => ..., 'good' => ..., 'strong' => ...]
         */
        public function Strength(int $MinLength = 8, array $Messages = []): static
        {
            $Field = $this->InputElement();
            $Field->AddAttribute('minlength', $MinLength);
            $Meter = new \HTML\ClassDiv('strength');
            $Meter->AddAttribute('data-bs-strength');
            $Meter->AddAttribute('data-bs-input', '#' . $Field->GetAttribute('id'));
            $Meter->AddAttribute('data-bs-min-length', $MinLength);
            if ($Messages)
            {
                $Meter->AddAttribute('data-bs-messages', json_encode($Messages));
            }
            $Meter->AddAttribute('aria-hidden', 'true');
            for ($Segment = 0; $Segment < 4; $Segment++)
            {
                $Meter->AddChild(new \HTML\ClassDiv('strength-segment'));
            }
            $this->AddChild($Meter);
            $Text = $this->AddChild(new \HTML\Span());
            $Text->AddClasses(['strength-text', 'form-text']);
            $Text->AddAttribute('aria-live', 'polite');
            return $this;
        }
    }
