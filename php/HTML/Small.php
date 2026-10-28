<?php

    namespace HTML;

    /**
     * <small> Defines smaller text
     *
     * @see https://www.w3schools.com/tags/tag_small.asp
     */
    class Small extends Element
    {
        public function __construct(Element|string $Content, ?string $ID = null)
        {
            parent::__construct('small', $ID);
            $this->AddChild($Content);
        }
    }