<?php

    namespace BS;

    /**
     * A date and a time in one field: a DatePicker and a TimePicker side by side, posting one value, YYYY-MM-DD HH:MM
     * (HH:MM:SS with Seconds()). js/app.js joins the two; a date without a time posts the date at 00:00, no date posts
     * nothing. A value given as YYYY-MM-DD HH:MM or YYYY-MM-DDTHH:MM (a datetime-local's) is taken.
     *
     *   $Meeting = new BS\DateTimePicker('meeting', 'Meeting', '2026-11-30 09:30');
     *   $Meeting->Step(15)->Required();
     */
    class DateTimePicker extends \HTML\ClassDiv
    {
        use FieldHelp;

        private DatePicker $Date;
        private TimePicker $Time;
        private \HTML\Input $ValueInput;
        private string $Name;

        public function __construct(string $Name, string $Label, ?string $Value = null, string $TimeLabel = 'Time')
        {
            parent::__construct('date-time-picker');
            $this->Name = $Name;
            $this->AddAttribute('data-bs-toggle', 'datetimepicker');
            $this->AddChild($Row = new \HTML\ClassDiv('date-time-picker-fields'));
            $Row->AddChild($this->Date = new DatePicker($Name . '_date', $Label));
            $Row->AddChild($this->Time = new TimePicker($Name . '_time', $TimeLabel));
            $this->Date->AddClass('date-time-picker-date');
            $this->Time->AddClass('date-time-picker-time');
            $this->AddChild($this->ValueInput = new \HTML\Input($Name, \HTML\InputType::Hidden));
            $this->ValueInput->AddClass('date-time-picker-value');
            $this->Value($Value);
        }

        /**
         * YYYY-MM-DD HH:MM (or HH:MM:SS, or with a T between them)
         */
        public function Value(mixed $Value): void
        {
            $Text = trim((string)$Value);
            $Date = $Time = '';
            if (preg_match('~^(\d{4}-\d{2}-\d{2})(?:[ T](\d{2}:\d{2}(?::\d{2})?))?$~', $Text, $Parts))
            {
                $Date = $Parts[1];
                $Time = $Parts[2] ?? '';
            }
            $this->Date->Value($Date);
            $this->Time->Value($Time);
            $this->ValueInput->Value($Date === '' ? '' : $Date . ' ' . ($Time === '' ? '00:00' : $Time));
        }

        public function ShowPostedValue(): void
        {
            $this->Value(\Post::Get($this->Name));
        }

        /**
         * The minutes in steps of this many
         */
        public function Step(int $Minutes): static
        {
            $this->Time->Step($Minutes);
            return $this;
        }

        /**
         * Seconds too: posted as YYYY-MM-DD HH:MM:SS
         */
        public function Seconds(): static
        {
            $this->Time->Seconds();
            return $this;
        }

        /**
         * A 12-hour clock in the field (still posted 24-hour)
         */
        public function TwelveHour(bool $Twelve = true): static
        {
            $this->Time->TwelveHour($Twelve);
            return $this;
        }

        /**
         * The first and last dates that can be chosen, YYYY-MM-DD
         */
        public function Min(string $Date): static
        {
            $this->Date->Min($Date);
            return $this;
        }

        public function Max(string $Date): static
        {
            $this->Date->Max($Date);
            return $this;
        }

        /**
         * Both the date and the time must be given
         */
        public function Required(): static
        {
            $this->Date->Required();
            $this->Time->Required();
            return $this;
        }

        public function Disabled(): static
        {
            $this->Date->Disabled();
            $this->Time->Disabled();
            return $this;
        }

        public function DatePicker(): DatePicker
        {
            return $this->Date;
        }

        public function TimePicker(): TimePicker
        {
            return $this->Time;
        }

        protected function Field(): \HTML\Element
        {
            return $this->Date->InputElement();
        }

        protected function MessageParent(): \HTML\Element
        {
            return $this;
        }
    }
