<?php

    namespace HTML;

    class Raw extends Element
    {
        private string $Content;

        public function __construct(string $Content = '')
        {
            $this->Content = $Content;
            parent::__construct('');
        }

        protected function OwnString(): string
        {
            return $this->Content . parent::Contents();
        }
    }