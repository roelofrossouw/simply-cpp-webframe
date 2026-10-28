<?php

    namespace HTML;

    /**
     * <h1> to <h6> Defines HTML headings
     *
     * @see https://www.w3schools.com/tags/tag_hn.asp
     */
    class Header extends Element
    {
        public function __construct(int $Level, Element|string $Content, ?string $ID = null)
        {
            parent::__construct('h' . $Level, $ID);
            $this->AddChild($Content);
        }
    }