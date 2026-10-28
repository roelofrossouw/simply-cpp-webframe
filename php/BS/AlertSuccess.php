<?php

    namespace BS;

    class AlertSuccess extends Alert
    {
        public function __construct($Header, $Content)
        {
            parent::__construct(new AlertHeader(Icon::ExclamationDiamond, $Header), $Content);
            $this->Color(Color::Success);
        }
    }