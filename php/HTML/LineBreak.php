<?php

    namespace HTML;

    /**
     * <br> Defines a single line break
     *
     * @see https://www.w3schools.com/tags/tag_br.asp
     */
    class LineBreak extends Element
    {
        public function __construct()
        {
            parent::__construct('br');
        }
    }