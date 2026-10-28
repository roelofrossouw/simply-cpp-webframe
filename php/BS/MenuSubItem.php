<?php

    namespace BS;

    /**
     * An item in a menu's dropdown
     *
     * Its own AddItem() opens a further level. Bootstrap 5 doesn't support nested dropdowns, app.js handles them.
     *
     * @see https://getbootstrap.com/docs/5.3/components/dropdowns/
     */
    class MenuSubItem extends BaseMenuItem
    {
        const string LINK_CLASS = 'dropdown-item';

        public function __construct(Menu $Menu, Icon $Icon, string $Caption, ?string $URL = null, ?string $ID = null)
        {
            parent::__construct($Menu, $Icon, $Caption, $URL, $ID);
        }

        protected function InitializeSubmenu(): void
        {
            parent::InitializeSubmenu();
            // TODO: Dropend only for large screen...
            // $this->AddClass('dropend');
        }
    }