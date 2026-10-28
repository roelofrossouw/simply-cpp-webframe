<?php

    namespace BS;

    /**
     * Content that opens and closes when a button is clicked
     *
     *   $Grid->Col($Button = new Button('More details'));
     *   $Grid->Col($Details = new Collapse('...'));
     *   $Details->Toggle($Button);
     *
     * @see https://getbootstrap.com/docs/5.3/components/collapse/
     */
    class Collapse extends \HTML\Div
    {
        public function __construct(\HTML\Element|string|null $Content = null, bool $Open = false)
        {
            parent::__construct(uniqid('collapse_'));
            $this->AddClass('collapse');
            if ($Open)
            {
                $this->AddClass('show');
            }
            if ($Content !== null)
            {
                $this->AddChild($Content);
            }
        }

        /**
         * Make a button (or link) open and close this content
         */
        public function Toggle(\HTML\Element $Trigger): static
        {
            $ID = $this->GetAttribute('id');
            $Trigger->AddAttribute('data-bs-toggle', 'collapse');
            $Trigger->AddAttribute('data-bs-target', '#' . $ID);
            $Trigger->AddAttribute('aria-expanded', in_array('show', explode(' ', (string)$this->GetAttribute('class')), true) ? 'true' : 'false');
            $Trigger->AddAttribute('aria-controls', $ID);
            return $this;
        }

        /**
         * Open sideways instead of downwards
         */
        public function Horizontal(): static
        {
            $this->AddClass('collapse-horizontal');
            return $this;
        }
    }
