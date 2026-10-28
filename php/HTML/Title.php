<?php

    namespace HTML;

    /**
     * <title> Defines a title for the document
     *
     * @see https://www.w3schools.com/tags/tag_title.asp
     */
    class Title extends Element
    {
        // Force content
        public function __construct(string $Content)
        {
            parent::__construct('title');
            $this->AddChild(new Text($Content));
        }
    }