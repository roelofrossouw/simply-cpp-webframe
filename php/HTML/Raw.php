<?php

    namespace HTML;

    // Text written out as it is; a Closure gives it when the page is written (e.g. a script that names its element's id,
    // which may still change)
    class Raw extends Element
    {
        private string|\Closure $Content;

        public function __construct(string|\Closure $Content = '')
        {
            $this->Content = $Content;
            parent::__construct('');
        }

        protected function OwnString(): string
        {
            return (is_string($this->Content) ? $this->Content : (string)($this->Content)()) . parent::Contents();
        }
    }