<?php

    namespace BS;

    use HTML\UnorderedList;

    class BaseNavbar extends UnorderedList
    {
        public function __construct($NavType)
        {
            parent::__construct();
            $this->AddClass($NavType);
        }

        public function AddMenuItem(MenuItem $Item): void
        {
            $this->AddChild($Item);
        }
    }