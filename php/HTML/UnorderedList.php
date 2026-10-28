<?php

    namespace HTML;

    /**
     * <ul> Defines an unordered list
     *
     * @see https://www.w3schools.com/tags/tag_ul.asp
     */
    class UnorderedList extends Element
    {
        public function __construct()
        {
            parent::__construct('ul');
        }

        public function AddItem(Element|string $Content): ListItem
        {
            $this->AddChild($Item = new ListItem($Content));
            return $Item;
        }
    }