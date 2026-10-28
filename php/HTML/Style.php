<?php

    namespace HTML;

    /**
     * <style> Defines style information for a document
     *
     * @property string $Media media query
     * @property string $Type text/css
     * @see https://www.w3schools.com/tags/tag_style.asp
     */
    class Style extends Element
    {
        public function __construct()
        {
            parent::__construct('style');
        }
    }