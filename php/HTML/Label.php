<?php

    namespace HTML;

    /**
     * <label> Defines a label for an <input> element
     *
     * @property string $For id
     * @property string $Form id
     * @see https://www.w3schools.com/tags/tag_label.asp
     */
    class Label extends Element
    {
        public function __construct(string $Label, ?string $ForElement = null)
        {
            parent::__construct('label');
            $this->AddAttribute("for", $ForElement);
            $this->AddChild(new Text($Label));
        }
    }