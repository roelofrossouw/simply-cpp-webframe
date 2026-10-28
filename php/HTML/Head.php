<?php

    namespace HTML;

    /**
     * <head> Contains metadata/information for the document
     *
     * @see https://www.w3schools.com/tags/tag_head.asp
     */
    class Head extends Element
    {
        public function __construct()
        {
            parent::__construct('head');
        }
    }