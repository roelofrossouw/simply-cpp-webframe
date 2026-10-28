<?php

    namespace HTML;

    /**
     * <body> Defines the document's body
     *
     * @see https://www.w3schools.com/tags/tag_body.asp
     */
    class Body extends Element
    {
        public function __construct()
        {
            parent::__construct('body');
        }
    }