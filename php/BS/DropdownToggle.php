<?php

    namespace BS;

    /**
     * The button that opens a dropdown menu
     *
     * @see https://getbootstrap.com/docs/5.3/components/dropdowns/
     */
    class DropdownToggle extends BaseButton
    {
        public function __construct(?string $Caption = null, ?Icon $Icon = null)
        {
            parent::__construct($Caption, $Icon);
            $this->AddClass('dropdown-toggle');
            $this->AddAttribute('data-bs-toggle', 'dropdown');
            $this->AddAttribute('aria-expanded', 'false');
        }
    }
