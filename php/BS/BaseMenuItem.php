<?php

    namespace BS;

    class BaseMenuItem extends \HTML\ListItem
    {
        // The link's class: nav-link in the menu bar, dropdown-item in a dropdown
        const string LINK_CLASS = 'nav-link';

        protected \HTML\Anchor $Anchor;
        protected ?DropdownMenu $DropdownMenu = null;
        private Menu $Menu;
        private string $Caption;
        private \HTML\Span $CaptionElement;

        public function __construct(Menu $Menu, Icon $Icon, string $Caption, ?string $URL = null, ?string $ID = null)
        {
            $ItemID = $ID ?? uniqid('mi');
            parent::__construct();
            $this->Caption = $Caption;
            $this->CaptionElement = new \HTML\Span($Caption);
            $this->AddChild($this->Anchor = new \HTML\Anchor($Icon->Element()->AddSibling($this->CaptionElement), ID: $ItemID));
            $this->Anchor->AddClasses([static::LINK_CLASS, 'text-nowrap']);
            $this->Anchor->AddAttribute('role', 'button'); // So we can have a hand cursor, even when href is missing
            $this->Anchor->TabIndex(0); // Without an href the link isn't reachable with the keyboard otherwise

            $this->Menu = $Menu;
            $this->Menu->AddURL($ItemID, $URL);
            $this->Menu->Register($this);
        }

        /**
         * Only show the icon. The caption stays available as a tooltip and for screen readers.
         */
        public function IconOnly(): static
        {
            $this->CaptionElement->AddClass('visually-hidden');
            $this->Anchor->AddAttribute('title', $this->Caption);
            return $this;
        }

        public function Active(): static
        {
            $this->Anchor->AddClass('active');
            $this->Anchor->AddAttribute('aria-current', 'page');
            return $this;
        }

        public function Modal(): static
        {
            $this->Anchor->AddClass('modal-link');
            return $this;
        }

        public function OffCanvas(): static
        {
            $this->Anchor->AddClass('oc-link');
            return $this;
        }

        public function AddItem(Icon $Icon, string $Caption, ?string $URL = null, ?string $ID = null): MenuSubItem
        {
            $this->InitializeSubmenu();
            $this->DropdownMenu->AddChild($Item = new MenuSubItem($this->Menu, $Icon, $Caption, $URL, $ID));
            return $Item;
        }

        protected function InitializeSubmenu(): void
        {
            if (empty($this->DropdownMenu))
            {
                $this->AddClass('dropdown');
                $this->AddChild($this->DropdownMenu = new DropdownMenu());
                $this->Anchor->AddClass('dropdown-toggle');
                $this->Anchor->AddAttribute('data-bs-toggle', 'dropdown');
                $this->Anchor->AddAttribute('aria-expanded', 'false');
                // Don't auto close when an item is clicked, so there can be more levels. app.js menuclick() closes them.
                $this->Anchor->AddAttribute('data-bs-auto-close', 'outside');
            }
        }

        public function Submenu(): DropdownMenu
        {
            $this->InitializeSubmenu();
            return $this->DropdownMenu;
        }

    }