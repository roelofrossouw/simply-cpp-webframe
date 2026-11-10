<?php

    namespace BS;

    /**
     * A date field with a calendar that opens under it: Bootstrap 6's Datepicker, done by js/app.js on Bootstrap 5.3
     * with the same library (Vanilla Calendar Pro) and the same data-bs-* attributes. The field shows the dates
     * written out in the browser's language; what the form posts is in a hidden field of the given name, as
     * YYYY-MM-DD: one date, several separated by commas (DateSelection::Multiple), or the first and last day of a
     * range (DateSelection::Range, "2026-06-10,2026-06-18"). A date typed as YYYY-MM-DD is taken too.
     *
     *   $Due = new BS\DatePicker('due', 'Due date', '2026-11-30');
     *   $Due->Min('2026-11-01')->Required();
     *   $Period = new BS\DatePicker('period', 'Period', Selection: BS\DateSelection::Range);
     *   $Period->Months(2);
     *
     * @see https://getbootstrap.com/docs/6.0/forms/datepicker/
     */
    class DatePicker extends Input
    {
        private \HTML\Input $ValueInput;
        private array $Calendar = [];

        public function __construct(string $Name, string $Label, ?string $Value = null, DateSelection $Selection = DateSelection::Single)
        {
            parent::__construct($Name, $Label, Suffix: Icon::Calendar3->Element());
            $Field = $this->InputElement();
            $Field->RemoveAttribute('name');
            $ID = $Field->GetAttribute('id') ?? uniqid('date_');
            $Field->AddAttribute('id', $ID);
            $Field->AddAttribute('data-bs-toggle', 'datepicker');
            $Field->AddAttribute('autocomplete', 'off');
            $Field->AddAttribute('data-bs-value-input', '#' . $ID . '_value');
            if ($Selection !== DateSelection::Single)
            {
                $Field->AddAttribute('data-bs-selection-mode', $Selection->value);
            }
            $Field->AddSibling($this->ValueInput = new \HTML\Input($Name, \HTML\InputType::Hidden));
            $this->ValueInput->AddAttribute('id', $ID . '_value');
            $this->Value($Value);
        }

        /**
         * The dates, YYYY-MM-DD, several separated by commas (or an array of them)
         */
        public function Value(mixed $Value): void
        {
            $Dates = is_array($Value) ? implode(',', $Value) : (string)$Value;
            $this->ValueInput->Value($Dates);
            // Written out by the script; without it the field shows them as they are
            parent::Value(str_replace(',', ', ', $Dates));
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
         * The first date that can be chosen, YYYY-MM-DD
         */
        public function Min(string $Date): static
        {
            $this->InputElement()->AddAttribute('data-bs-date-min', $Date);
            return $this;
        }

        /**
         * The last date that can be chosen, YYYY-MM-DD
         */
        public function Max(string $Date): static
        {
            $this->InputElement()->AddAttribute('data-bs-date-max', $Date);
            return $this;
        }

        /**
         * Show this many months side by side (handy for a range)
         */
        public function Months(int $Count): static
        {
            $this->InputElement()->AddAttribute('data-bs-display-months-count', $Count);
            return $this;
        }

        /**
         * The week starts on this day: 0 Sunday, 1 Monday (the default) ...
         */
        public function FirstWeekday(int $Day): static
        {
            $this->InputElement()->AddAttribute('data-bs-first-weekday', $Day);
            return $this;
        }

        /**
         * Days before today can't be chosen
         */
        public function NoPast(): static
        {
            return $this->Calendar(['disableDatesPast' => true]);
        }

        /**
         * Saturdays and Sundays can't be chosen
         */
        public function NoWeekends(): static
        {
            return $this->Calendar(['disableWeekdays' => [0, 6]]);
        }

        /**
         * These dates (YYYY-MM-DD) can't be chosen
         */
        public function Disable(array $Dates): static
        {
            return $this->Calendar(['disableDates' => array_values($Dates)]);
        }

        /**
         * Any other Vanilla Calendar Pro option, as Bootstrap 6's vcpOptions
         *
         * @see https://vanilla-calendar.pro/docs/reference/settings
         */
        public function Calendar(array $Options): static
        {
            $this->Calendar = array_merge($this->Calendar, $Options);
            $this->InputElement()->AddAttribute('data-bs-vcp-options', json_encode($this->Calendar));
            return $this;
        }
    }
