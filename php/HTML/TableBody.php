<?php

    namespace HTML;

    /**
     * <tbody> Groups the body content in a table
     *
     * @see https://www.w3schools.com/tags/tag_tbody.asp
     */
    class TableBody extends TableSection
    {
        public function __construct()
        {
            parent::__construct('tbody');
        }
    }