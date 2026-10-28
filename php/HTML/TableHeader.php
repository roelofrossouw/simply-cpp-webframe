<?php

    namespace HTML;

    /**
     * <thead> Groups the header content in a table
     *
     * @see https://www.w3schools.com/tags/tag_thead.asp
     */
    class TableHeader extends TableSection
    {
        public function __construct()
        {
            parent::__construct('thead');
            $this->AllowEmpty = true;
        }
    }