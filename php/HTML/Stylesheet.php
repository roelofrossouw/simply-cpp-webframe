<?php

    namespace HTML;

    class Stylesheet extends Element
    {
        public function __construct(?string $Href = null)
        {
            parent::__construct('link');
            $this->addAttribute('rel', 'stylesheet');
            $this->AddAttribute('type', 'text/css');
            $this->addAttribute('href', $Href);
        }
    }