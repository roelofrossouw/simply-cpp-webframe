<?php

    namespace HTML;

    /**
     * <b> Defines bold text
     *
     * @see https://www.w3schools.com/tags/tag_b.asp
     */
    class Bold extends Element
    {

        public function __construct(string $Content)
        {
            parent::__construct('b');
            $this->AddChild(new Text($Content));
        }
    }