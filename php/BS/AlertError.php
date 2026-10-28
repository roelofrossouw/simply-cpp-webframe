<?php

    namespace BS;

    class AlertError extends Alert
    {
        public function __construct($Header, $Content)
        {
            parent::__construct(new AlertHeader(Icon::ExclamationTriangle, $Header), $Content);
            $this->Color(Color::Danger);
        }
    }