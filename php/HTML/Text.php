<?php

    namespace HTML;

    class Text extends Raw
    {
        public function __construct(string $Content = '')
        {
            parent::__construct(nl2br(self::Encode($Content)));
        }

        private static function Encode($Content)
        {
            $Flags = ENT_SUBSTITUTE | ENT_HTML5;
            $Encoding = 'UTF-8';
            return htmlspecialchars($Content, $Flags, $Encoding, false);
        }
    }