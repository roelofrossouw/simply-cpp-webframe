<?php

    namespace BS;

    class DropdownMenu extends BaseNavbar
    {
        public function __construct()
        {
            parent::__construct('dropdown-menu');
        }

        /**
         * @see https://getbootstrap.com/docs/5.3/components/dropdowns/#menu-alignment
         */
        public function AlignRight(): void
        {
            $this->AddClass('dropdown-menu-end');
        }
    }