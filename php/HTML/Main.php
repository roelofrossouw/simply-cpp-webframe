<?php

    namespace HTML;

    /**
     * <main> Specifies the main content of a document
     *
     * @see https://www.w3schools.com/tags/tag_main.asp
     */
    class Main extends Element
    {
        public function __construct()
        {
            parent::__construct('main', 'main');
        }
    }