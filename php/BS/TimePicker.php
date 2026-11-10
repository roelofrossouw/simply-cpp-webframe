<?php

    namespace BS;

    /**
     * A time field with a picker that opens under it: a column of hours and one of minutes (and seconds, and AM/PM for
     * a 12-hour clock) to scroll and click, with Now, Clear and Done, like CoreUI's time picker, done by js/app.js.
     * The field shows the time as the page's language writes it, or as a 12-hour clock with TwelveHour(); what the
     * form posts is in a hidden field of the given name, always 24-hour: HH:MM, or HH:MM:SS with Seconds(). A time
     * typed in the field counts too (14:30, 2:30 pm).
     *
     *   $Start = new BS\TimePicker('start', 'Starts at', '08:00');
     *   $Start->Step(15)->Min('07:00')->Max('18:00')->Required();
     *
     * @see https://coreui.io/bootstrap/docs/forms/time-picker/
     */
    class TimePicker extends Input
    {
        private \HTML\Input $ValueInput;

        public function __construct(string $Name, string $Label, ?string $Value = null)
        {
            parent::__construct($Name, $Label, Suffix: Icon::Clock->Element());
            $this->AddClass('time-picker-field');
            $Field = $this->InputElement();
            $Field->RemoveAttribute('name');
            $ID = $Field->GetAttribute('id') ?? uniqid('time_');
            $Field->AddAttribute('id', $ID);
            $Field->AddAttribute('data-bs-toggle', 'timepicker');
            $Field->AddAttribute('autocomplete', 'off');
            $Field->AddAttribute('data-bs-value-input', '#' . $ID . '_value');
            $Field->AddSibling($this->ValueInput = new \HTML\Input($Name, \HTML\InputType::Hidden));
            $this->ValueInput->AddAttribute('id', $ID . '_value');
            $this->Value($Value);
        }

        /**
         * The time, HH:MM or HH:MM:SS (24-hour)
         */
        public function Value(mixed $Value): void
        {
            $this->ValueInput->Value((string)$Value);
            parent::Value((string)$Value); // written out by the script
        }

        /**
         * The hidden field the form posts (BS\DateTimePicker combines two of them into one)
         */
        public function ValueElement(): \HTML\Input
        {
            return $this->ValueInput;
        }

        public function ShowPostedValue(): void
        {
            $this->Value(\Post::Get($this->ValueInput->GetAttribute('name')));
        }

        /**
         * The minutes in steps of this many (5, 15 ...)
         */
        public function Step(int $Minutes): static
        {
            $this->InputElement()->AddAttribute('data-bs-minute-step', $Minutes);
            return $this;
        }

        /**
         * Seconds too: posted as HH:MM:SS
         */
        public function Seconds(): static
        {
            $this->InputElement()->AddAttribute('data-bs-seconds', 'true');
            return $this;
        }

        /**
         * A 12-hour clock with AM and PM (still posted as 24-hour); without it the page's language decides
         */
        public function TwelveHour(bool $Twelve = true): static
        {
            $this->InputElement()->AddAttribute('data-bs-hour12', $Twelve ? 'true' : 'false');
            return $this;
        }

        /**
         * The earliest time that can be chosen, HH:MM
         */
        public function Min(string $Time): static
        {
            $this->InputElement()->AddAttribute('data-bs-time-min', $Time);
            return $this;
        }

        /**
         * The latest time that can be chosen, HH:MM
         */
        public function Max(string $Time): static
        {
            $this->InputElement()->AddAttribute('data-bs-time-max', $Time);
            return $this;
        }
    }
