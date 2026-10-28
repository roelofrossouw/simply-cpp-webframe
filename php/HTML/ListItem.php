<?php

    namespace HTML;

    /**
     * <li> Defines a list item
     *
     * @property string|int $Value number
     * @see https://www.w3schools.com/tags/tag_li.asp
     */
    class ListItem extends Element
    {
        public function __construct(string|Element|null $Content = null)
        {
            parent::__construct('li');
            if (!empty($Content))
            {
                $this->AddChild($Content);
            }
        }
    }