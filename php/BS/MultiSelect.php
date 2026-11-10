<?php

    namespace BS;

    /**
     * A list to choose several things from, with a search: what's chosen shows as chips in the field (or as "3
     * selected"), the list opens under it with Select all and Clear, groups of options, and a search that narrows it as
     * you type. Like CoreUI's multi-select, done by js/app.js on a plain <select multiple>, which is what the form
     * posts: name[] (Post::Get('name') is an array). Single(): one choice, still with the search (a combobox).
     * Source(url): the options come from the server as you type, for a long list (users, servers ...). Creatable():
     * what's typed may be added as a value of its own, as tags (Bootstrap 6's chip input).
     *
     *   $Tags = new BS\MultiSelect('tags', 'Tags', ['a' => 'Alpha', 'b' => 'Beta'], ['a']);
     *   $Cities = new BS\MultiSelect('cities', 'Cities', ['Gauteng' => ['jhb' => 'Johannesburg', 'pta' => 'Pretoria'],
     *                                                    'Western Cape' => ['cpt' => 'Cape Town']]);
     *   $Cities->Max(2)->Required();
     *   $Owner = new BS\MultiSelect('owner', 'Owner', [], [42]);
     *   $Owner->Source('/users.php?action=search')->Single();  // GET ...&q=jo answers [{"value": 42, "text": "Jo"}]
     *   $Labels = new BS\MultiSelect('labels', 'Labels', ['urgent' => 'Urgent']);
     *   $Labels->Creatable();                                   // "invoice" typed and Enter: posted as labels[]=invoice
     *
     * @see https://coreui.io/bootstrap/docs/forms/multi-select/
     */
    class MultiSelect extends \HTML\ClassDiv
    {
        use FieldHelp;

        private \HTML\Select $SelectElement;
        private string $Name;
        private bool $Open = false; // values not among the options are kept (Source, Creatable)
        private array $Values = [];  // the values chosen, as last given

        /**
         * @param array $Options Value => caption; a caption => [value => caption] is a group of options
         * @param array $Values  The values chosen at first
         */
        public function __construct(string $Name, string $Label, array $Options, array $Values = [], ?string $Placeholder = null)
        {
            parent::__construct('form-floating');
            $this->AddClass('multi-select-field');
            $this->Name = $Name;
            $this->AddChild($this->SelectElement = new \HTML\Select(ID: uniqid('select_')));
            $this->SelectElement->Name = $Name . '[]';
            $this->SelectElement->AddAttribute('multiple');
            $this->SelectElement->AddClasses(['form-select', 'multi-select-source']);
            $this->SelectElement->AddAttribute('data-bs-toggle', 'multi-select');
            if ($Placeholder !== null)
            {
                $this->SelectElement->AddAttribute('data-bs-placeholder', $Placeholder);
            }
            $Chosen = $this->Values = array_map('strval', $Values);
            foreach ($Options as $Key => $Caption)
            {
                if (is_array($Caption))
                {
                    $this->SelectElement->AddChild($Group = new \HTML\OptionGroup());
                    $Group->Label = (string)$Key;
                    foreach ($Caption as $Value => $Text)
                    {
                        $Group->AddChild($this->Option((string)$Value, (string)$Text, $Chosen));
                    }
                }
                else
                {
                    $this->SelectElement->AddChild($this->Option((string)$Key, (string)$Caption, $Chosen));
                }
            }
            $this->AddChild(new \HTML\Label($Label, $this->SelectElement->Id));
        }

        private function Option(string $Value, string $Caption, array $Chosen): \HTML\Option
        {
            $Option = new \HTML\Option($Caption);
            $Option->Value = $Value;
            $Option->Selected = in_array($Value, $Chosen, true);
            return $Option;
        }

        /**
         * One choice only, with the search: a combobox. Posts name[] with one value, as Post::Get('name')[0].
         */
        public function Single(): static
        {
            $this->SelectElement->RemoveAttribute('multiple');
            $this->SelectElement->AddAttribute('data-bs-single', 'true');
            return $this;
        }

        /**
         * Search the server as you type: GET url with q=the text typed (and &, or ?, as the URL needs), answered with
         * JSON [{"value": ..., "text": ...}], at most a page's worth. The options given here, and those chosen, show
         * before anything is typed; give the chosen ones' captions as options so they don't show as their values.
         */
        public function Source(string $URL, int $MinLength = 1): static
        {
            $this->Open = true;
            $this->Value($this->Values); // those chosen already that aren't among the options
            $this->SelectElement->AddAttribute('data-bs-source', $URL);
            $this->SelectElement->AddAttribute('data-bs-source-min-length', $MinLength);
            return $this;
        }

        /**
         * What's typed may be added as a value of its own (Enter, or "Add ..." in the list): free-text tags
         */
        public function Creatable(): static
        {
            $this->Open = true;
            $this->Value($this->Values);
            $this->SelectElement->AddAttribute('data-bs-creatable', 'true');
            return $this;
        }

        /**
         * At most this many can be chosen
         */
        public function Max(int $Count): static
        {
            $this->SelectElement->AddAttribute('data-bs-max', $Count);
            return $this;
        }

        /**
         * Show "3 selected" in the field instead of a chip for each
         */
        public function Counter(): static
        {
            $this->SelectElement->AddAttribute('data-bs-selection', 'counter');
            return $this;
        }

        /**
         * Without the search field (for a short list)
         */
        public function NoSearch(): static
        {
            $this->SelectElement->AddAttribute('data-bs-search', 'false');
            return $this;
        }

        /**
         * Without Select all
         */
        public function NoSelectAll(): static
        {
            $this->SelectElement->AddAttribute('data-bs-select-all', 'false');
            return $this;
        }

        /**
         * These values can't be chosen (they stay as they are)
         */
        public function Disable(array $Values): static
        {
            $Values = array_map('strval', $Values);
            foreach ($this->Options() as $Option)
            {
                if (in_array($Option->Value, $Values, true))
                {
                    $Option->Disabled = true;
                }
            }
            return $this;
        }

        public function SelectElement(): \HTML\Select
        {
            return $this->SelectElement;
        }

        /**
         * The values chosen, e.g. those posted
         */
        public function Value(array $Values): void
        {
            $Values = $this->Values = array_map('strval', $Values);
            $Known = [];
            foreach ($this->Options() as $Option)
            {
                $Option->Selected = in_array($Option->Value, $Values, true);
                $Known[] = $Option->Value;
            }
            // With Source() or Creatable() a value may not be among the options: it's kept, captioned as itself
            if ($this->Open)
            {
                foreach (array_diff($Values, $Known) as $Value)
                {
                    if ($Value !== '')
                    {
                        $this->SelectElement->AddChild($this->Option($Value, $Value, [$Value]));
                    }
                }
            }
        }

        public function ShowPostedValue(): void
        {
            $this->Value((array)(\Post::Get($this->Name) ?? []));
        }

        /** @return \HTML\Option[] */
        private function Options(): array
        {
            $Options = [];
            foreach ($this->SelectElement->Children() as $Child)
            {
                if ($Child instanceof \HTML\OptionGroup)
                {
                    array_push($Options, ...array_filter($Child->Children(), fn($Option) => $Option instanceof \HTML\Option));
                }
                elseif ($Child instanceof \HTML\Option)
                {
                    $Options[] = $Child;
                }
            }
            return $Options;
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
