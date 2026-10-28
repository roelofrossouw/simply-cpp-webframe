<?php

    namespace BS;

    class AlertWarning extends Alert
    {
        public function __construct($Header, $Content)
        {
            parent::__construct(new AlertHeader(Icon::ExclamationCircle, $Header), $Content);
            $this->Color(Color::Warning);
        }
    }