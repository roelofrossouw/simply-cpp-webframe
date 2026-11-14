<?php

    namespace BS;

    /**
     * Where a form of several steps is: numbered steps with their names, the current one highlighted and the ones before
     * it ticked, over a thin progress bar. On a phone the names make way for "Step 2 of 4: Cover". No script: each step
     * is a page (or a post) of its own, which shows the stepper with its own step current.
     *
     *   $Page->AddChild(new BS\Stepper(['Your details', 'Firearm', 'Cover', 'Confirm'], Current: 2));
     *   $Stepper->Links(['/apply.php?step=1', '/apply.php?step=2']); // steps done may be gone back to
     */
    class Stepper extends \HTML\ClassDiv
    {
        private array $Steps;
        private int $Current;
        private array $Links = [];
        private bool $Built = false;

        /**
         * @param string[] $Steps The steps' names, in order
         * @param int $Current The step shown now, from 1
         */
        public function __construct(array $Steps, int $Current = 1)
        {
            parent::__construct('stepper');
            $this->Steps = array_values(array_map('strval', $Steps));
            $this->Current = max(1, min(count($this->Steps), $Current));
        }

        /**
         * Links for the steps before the current one, by their order (from 1, or a list from the first step): a step
         * done can be opened again
         */
        public function Links(array $URLs): static
        {
            $this->Links = array_is_list($URLs) ? array_combine(range(1, count($URLs)), $URLs) : $URLs;
            return $this;
        }

        // The steps are made when it's shown, so Links() may come after the constructor
        protected function OwnString(): string
        {
            if (!$this->Built)
            {
                $this->Build();
                $this->Built = true;
            }
            return parent::OwnString();
        }

        private function Build(): void
        {
            $Count = count($this->Steps);
            $Name = $this->Steps[$this->Current - 1] ?? '';
            // On a phone: which step, and its name
            $this->AddChild($Small = new \HTML\Div(Content: "Step {$this->Current} of $Count: $Name"));
            $Small->AddClasses(['stepper-summary', 'd-sm-none', 'small', 'fw-semibold', 'mb-1']);
            $this->AddChild($List = new \HTML\OrderedList());
            $List->AddClasses(['stepper-steps', 'list-unstyled', 'd-flex', 'mb-2']);
            $List->AddAttribute('aria-label', 'Progress');
            foreach ($this->Steps as $Index => $Step)
            {
                $Number = $Index + 1;
                $State = $Number < $this->Current ? 'done' : ($Number == $this->Current ? 'current' : 'todo');
                $List->AddChild($Item = new \HTML\ListItem());
                $Item->AddClasses(['stepper-step', "stepper-$State"]);
                if ($State == 'current')
                {
                    $Item->AddAttribute('aria-current', 'step');
                }
                $Marker = new \HTML\Span($State == 'done' ? '' : (string)$Number);
                $Marker->AddClass('stepper-marker');
                if ($State == 'done')
                {
                    $Marker->AddChild($Tick = new \HTML\Italic());
                    $Tick->AddClasses(['bi', Icon::Check2->value]);
                    $Tick->AddAttribute('aria-hidden', 'true');
                }
                $Label = new \HTML\Span($Step);
                $Label->AddClasses(['stepper-label', 'd-none', 'd-sm-inline']);
                $Hidden = new \HTML\Span($State == 'done' ? ' (done)' : ($State == 'current' ? ' (now)' : ''));
                $Hidden->AddClass('visually-hidden');
                $Content = $State == 'done' && isset($this->Links[$Number]) ? new \HTML\Anchor(null, (string)$this->Links[$Number]) : new \HTML\Span();
                $Content->AddClass('stepper-content');
                $Content->AddChild($Marker);
                $Content->AddChild($Label);
                $Content->AddChild($Hidden);
                $Item->AddChild($Content);
            }
            $this->AddChild((new Progress($this->Current, $Count, 'Steps done'))->Height(4));
        }
    }
