<?php

    namespace HTML;

    /**
     * <span> Defines a section in a document
     *
     * @see https://www.w3schools.com/tags/tag_span.asp
     */
    class Span extends Element
    {
        public function __construct(string $Content = '', ?string $ID = null)
        {
            parent::__construct('span', $ID);
            $this->AddChild(new Text($Content));
        }
    }