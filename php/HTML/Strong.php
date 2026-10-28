<?php

    namespace HTML;

    /**
     * <strong> Defines important text
     *
     * @see https://www.w3schools.com/tags/tag_strong.asp
     */
    class Strong extends Element
    {
        public function __construct(Element|string $Content, ?string $ID = null)
        {
            parent::__construct('strong', $ID);
            $this->AddChild($Content);
        }
    }