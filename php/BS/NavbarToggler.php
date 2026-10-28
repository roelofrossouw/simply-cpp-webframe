<?php

    namespace BS;

    class NavbarToggler extends \HTML\Element
    {
        public function __construct(string $MenuID)
        {
            parent::__construct('button');
            $this->AddClass('navbar-toggler');
            $this->AddChild(new \HTML\Span())->AddClass('navbar-toggler-icon');
            $this->AddAttribute('type', "button");
            $this->AddAttribute('data-bs-toggle', "collapse");
            $this->AddAttribute('data-bs-target', "#{$MenuID}");
            $this->AddAttribute('aria-controls', "{$MenuID}");
            $this->AddAttribute('aria-expanded', "false");
            $this->AddAttribute('aria-label', 'Toggle navigation');
        }
    }