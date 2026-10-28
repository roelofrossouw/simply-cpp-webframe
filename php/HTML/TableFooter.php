<?php

    namespace HTML;

    /**
     * <tfoot> Groups the footer content in a table
     *
     * @see https://www.w3schools.com/tags/tag_tfoot.asp
     */
    class TableFooter extends TableSection
    {
        public function __construct()
        {
            parent::__construct('tfoot');
        }
    }