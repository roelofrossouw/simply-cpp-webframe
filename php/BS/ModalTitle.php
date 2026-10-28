<?php

    namespace BS;

    class ModalTitle extends \HTML\Header
    {
        public function __construct($Title)
        {
            parent::__construct(4, $Title, uniqid('title_'));
            $this->AddClass('modal-title');
        }
    }