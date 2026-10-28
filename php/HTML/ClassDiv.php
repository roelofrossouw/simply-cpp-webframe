<?php

    namespace HTML;

    class ClassDiv extends Div
    {
        public function __construct(string $Class, ?string $ID = null)
        {
            parent::__construct($ID);
            $this->AddClass($Class);
        }

    }