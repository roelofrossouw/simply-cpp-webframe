<?php

    namespace BS;

    class Menu extends \HTML\ClassDiv
    {
        private string $ID;
        private $Navbar;
        private $URLS = [];

        private \HTML\Div $ItemBar;
        private NavbarToggler $Toggler;
        private ?Navbar $Pinned = null;
        private array $Items = [];
        private Dismiss $OpenIn = Dismiss::None;
        private bool $LoadActive = false;

        public function __construct(\HTML\Element $Brand)
        {
            parent::__construct('navbar', $this->ID = uniqid('menu-'));
            $this->SetTag('nav');
            $this->AddChild($Fluid = new \HTML\ClassDiv('container-fluid'));

            // A real link home, so it works with the keyboard
            $Fluid->AddChild($BrandAnchor = new \HTML\Anchor($Brand, '/', IsLink: true));
            $BrandAnchor->AddClass('navbar-brand');

            $Fluid->AddChild($this->Toggler = new NavbarToggler($CollapseID = uniqid('collapse')));
            $Fluid->AddChild($this->ItemBar = new \HTML\Div($CollapseID));
            $this->ItemBar->AddClasses(['collapse', 'navbar-collapse']);
            $this->ItemBar->AddChild($this->Navbar = new Navbar());
        }

        public function __toString(): string
        {
            return parent::__toString() . $this->Scripts();
        }

        private function Scripts(): string
        {
            $NewURLs = json_encode($this->URLS);
            $Script = "setupMenu(\"{$this->ID}\", $NewURLs);";
            if ($this->LoadActive)
            {
                $Script .= " menuclick($('#{$this->ID} a.active').first());";
            }
            return new \HTML\Script($Script);
        }

        /**
         * Called by every item (including dropdown items), so menu wide settings apply to all of them
         */
        public function Register(BaseMenuItem $Item): void
        {
            $this->Items[] = $Item;
            $this->ApplyOpenIn($Item);
        }

        private function ApplyOpenIn(BaseMenuItem $Item): void
        {
            match ($this->OpenIn)
            {
                Dismiss::Modal => $Item->Modal(),
                Dismiss::OffCanvas => $Item->OffCanvas(),
                default => null,
            };
        }

        /**
         * Open all this menu's items in the popup, rather than in the menu's target. E.g. for a status bar.
         */
        public function Modal(): void
        {
            $this->SetOpenIn(Dismiss::Modal);
        }

        /**
         * Open all this menu's items in the side panel, rather than in the menu's target
         */
        public function OffCanvas(): void
        {
            $this->SetOpenIn(Dismiss::OffCanvas);
        }

        private function SetOpenIn(Dismiss $Panel): void
        {
            $this->OpenIn = $Panel;
            foreach ($this->Items as $Item)
            {
                $this->ApplyOpenIn($Item);
            }
        }

        /**
         * Colour the menu bar. Dark colours switch the menu to light text.
         */
        public function Color(Color $Color): static
        {
            $this->Format->Background($Color);
            return $this->AddAttribute('data-bs-theme', $Color->IsDark() ? 'dark' : 'light');
        }

        /**
         * Load the active item into the target as soon as the menu shows. For menus inside a page; the main
         * menu's active item is loaded by the app's main() already.
         */
        public function LoadActive(): void
        {
            $this->LoadActive = true;
        }

      public function Medium(): void
      {
        $this->AddClass('medium-menu');
      }

      public function Small(): void
      {
        $this->AddClass('small-menu');
      }

        public function AddItem(Icon $Icon, string $Caption, ?string $URL = null, ?string $ID = null): MenuItem
        {
            $Item = new MenuItem($this, $Icon, $Caption, $URL, $ID);
            $this->Navbar->AddMenuItem($Item);
            return $Item;
        }

        /**
         * An item that stays visible when the menu collapses on a narrow screen, next to the toggle button
         * (e.g. the signed in user in the status bar), instead of folding away with the other items
         *
         * @see https://getbootstrap.com/docs/5.3/components/navbar/#responsive-behaviors
         */
        public function AddPinnedItem(Icon $Icon, string $Caption, ?string $URL = null, ?string $ID = null): MenuItem
        {
            if ($this->Pinned === null)
            {
                // Before the toggle button, outside the part that collapses; always a row, pushed to the right
                $this->Toggler->PrependSibling($this->Pinned = new Navbar());
                $this->Pinned->AddClasses(['flex-row', 'ms-auto']);
            }
            $Item = new MenuItem($this, $Icon, $Caption, $URL, $ID);
            $this->Pinned->AddMenuItem($Item);
            return $Item;
        }

        public function AddURL(string $ItemID, ?string $URL): void
        {
            if (!empty($URL) && !empty($ItemID))
            {
                $this->URLS[$ItemID] = $URL;
            }
        }

        public function FixedBottom(): void
        {
            $this->AddClass('fixed-bottom');
            $this->AddClass('border-top');
        }

        public function FixedTop(): void
        {
            $this->AddClass('fixed-top');
            $this->AddClass('border-bottom');
        }

        /**
         * Add a target for menu items' links to display in.
         *
         * @return void
         */
        public function AddTarget(): void
        {
            $this->AddSibling($Target = new \HTML\ClassDiv('menu-target', "{$this->ID}-target"));
            // $Target->Format->Padding(\BS\Side::All, \BS\PadSize::Pad2);
        }

        public function RightAlign(): void
        {
            $this->ItemBar->AddClass('flex-grow-0');
        }

    }
