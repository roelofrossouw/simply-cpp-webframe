<?php

    namespace BS;

    class AlertInfo extends Alert
    {
        public function __construct($Header, $Content)
        {
            parent::__construct(new AlertHeader(Icon::ShieldExclamation, $Header), $Content);
            $this->Color(Color::Info);
        }
    }