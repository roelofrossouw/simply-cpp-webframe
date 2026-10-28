<?php

    namespace HTML;

    /**
     * <caption> Defines a table caption
     *
     * @see https://www.w3schools.com/tags/tag_caption.asp
     */
    class Caption extends TableSection
    {
        public function __construct()
        {
            parent::__construct('caption');
            $this->AllowEmpty = true;
        }
    }