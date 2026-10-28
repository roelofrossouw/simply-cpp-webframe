<?php

    namespace BS;

    class AlertWarning extends Alert
    {
        /**
         * @param Icon $Icon Another icon that suits the message better, e.g. Icon::Envelope for "we emailed you"
         */
        public function __construct(\HTML\Element|string $Header, \HTML\Element|string $Content, Icon $Icon = Icon::ExclamationCircle)
        {
            parent::__construct($Header, $Content, $Icon);
            $this->Color(Color::Warning);
        }
    }
