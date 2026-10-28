<?php

    namespace BS;

    /**
     * A button with a dropdown menu of actions
     *
     * Bootstrap's markup: a .dropdown wrapper holding the toggle button and the menu side by side.
     *
     * @see https://getbootstrap.com/docs/5.3/components/dropdowns/#single-button
     */
    class DropDownButton extends \HTML\ClassDiv
    {
        private DropdownToggle $Toggle;
        private DropdownMenu $Menu;

        public function __construct(?string $Caption = null, ?Icon $Icon = null)
        {
            parent::__construct('dropdown');
            parent::AddChild($this->Toggle = new DropdownToggle($Caption, $Icon));
            parent::AddChild($this->Menu = new DropdownMenu());
        }

        public function Toggle(): DropdownToggle
        {
            return $this->Toggle;
        }

        public function Color(Color $Color): static
        {
            $this->Toggle->Color($Color);
            return $this;
        }

        /**
         * Add an action to the menu, e.g. ->AddItem('Dark', Icon::MoonStars)->OnClick('setTheme("dark");')
         */
        public function AddItem(string $Caption, ?Icon $Icon = null): \HTML\Button
        {
            $Item = new \HTML\Button();
            $Item->AddAttribute('type', 'button');
            $Item->AddClass('dropdown-item');
            if (!empty($Icon))
            {
                $Item->AddChild($Icon->Element());
            }
            $Item->AddChild($Caption);
            $this->Menu->AddChild(new \HTML\ListItem($Item));
            return $Item;
        }

        public function AlignRight(): static
        {
            $this->Menu->AlignRight();
            return $this;
        }
    }
