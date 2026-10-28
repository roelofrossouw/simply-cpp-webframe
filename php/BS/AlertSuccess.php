<?php

    namespace BS;

    class AlertSuccess extends Alert
    {
        /**
         * @param Icon $Icon Another icon that suits the message better, e.g. Icon::Envelope for "we emailed you"
         */
        public function __construct(\HTML\Element|string $Header, \HTML\Element|string $Content, Icon $Icon = Icon::CheckCircle)
        {
            parent::__construct($Header, $Content, $Icon);
            $this->Color(Color::Success);
        }
    }
