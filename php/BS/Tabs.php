<?php

    namespace BS;

    use HTML\Element;

    /**
     * Tabs (or pills) that each show their own pane
     *
     *   $Tabs = new Tabs();
     *   $Tabs->AddTab('Overview', $Overview);
     *   $Details = $Tabs->AddTab('Details', 'Loading...');
     *
     * @see https://getbootstrap.com/docs/5.3/components/navs-tabs/#javascript-behavior
     */
    class Tabs extends \HTML\Div
    {
        private \HTML\UnorderedList $Nav;
        private NavOverflow $Wrapper;
        private \HTML\Div $Panes;
        private string $Toggle;
        private int $Count = 0;

        public function __construct(bool $Pills = false)
        {
            parent::__construct();
            $this->Toggle = $Pills ? 'pill' : 'tab';
            parent::AddChild($this->Wrapper = new NavOverflow());
            $this->Wrapper->AddChild($this->Nav = new \HTML\UnorderedList());
            $this->Nav->AddClasses(['nav', $Pills ? 'nav-pills' : 'nav-tabs']);
            $this->Nav->AddAttribute('role', 'tablist');
            parent::AddChild($this->Panes = new \HTML\ClassDiv('tab-content'));
            $this->Panes->Format->Padding(Side::Top, PadSize::Pad3);
        }

        /**
         * Tabs share the full width, each as wide as its caption (Fill) or all equally wide (Justified)
         */
        public function Fill(bool $Justified = false): static
        {
            $this->Nav->AddClass($Justified ? 'nav-justified' : 'nav-fill', 'nav-width');
            return $this;
        }

        /**
         * Tabs that don't fit go into a More menu at the end of the row, last first, instead of wrapping onto
         * another line (Bootstrap 6's nav overflow, see BS\NavOverflow). Tabs added with $Keep stay.
         *
         * @param string|false $More The More button's caption; false: only its icon
         * @param int $Threshold At least this many tabs stay visible
         * @param ScreenSize|int|null $CollapseBelow Narrower than this, every tab but those kept goes into More
         */
        public function Overflow(string|false $More = 'More', int $Threshold = 0, ScreenSize|int|null $CollapseBelow = null): static
        {
            $this->Wrapper->Enable($More, $Threshold, $CollapseBelow);
            return $this;
        }

        /**
         * @param bool $Active Show this tab first (the first tab is shown when none is chosen)
         * @param bool $Keep With Overflow(): the tab never goes into the More menu
         * @return \HTML\Div The tab's pane
         */
        public function AddTab(string $Caption, Element|string $Content, bool $Active = false, ?Icon $Icon = null, bool $Keep = false): \HTML\Div
        {
            // The first tab is active unless another one asks to be. Count separately: in "$Active || $this->Count++"
            // the count would be skipped for an active tab, and the next tab would take over.
            $Active = $Active || $this->Count == 0;
            $this->Count++;
            if ($Active)
            {
                // Only one tab can be active
                foreach ($this->Nav->Children() as $Item)
                {
                    $Item->Children()[0]->RemoveClass('active')->AddAttribute('aria-selected', 'false');
                }
                foreach ($this->Panes->Children() as $Pane)
                {
                    $Pane->RemoveClass('show')->RemoveClass('active');
                }
            }
            $ID = uniqid('tab_');

            $Button = new \HTML\Button($Icon === null ? $Caption : $Icon->Element()->AddSibling($Caption), $ID . '_tab');
            $Button->AddAttribute('type', 'button');
            $Button->AddClass('nav-link');
            $Button->AddAttribute('data-bs-toggle', $this->Toggle);
            $Button->AddAttribute('data-bs-target', '#' . $ID);
            $Button->AddAttribute('role', 'tab');
            $Button->AddAttribute('aria-controls', $ID);
            $Button->AddAttribute('aria-selected', $Active ? 'true' : 'false');
            // Which tab it is, the same however its count changes ('Files (9)' is Files), so the page can open on it
            // again when a popup goes back to it or refreshes (rememberTab() in js/app.js)
            $Button->AddAttribute('data-tab-key', preg_replace('/\s*\(\d+\)$/', '', $Caption));
            $this->Nav->AddChild($Item = new \HTML\ListItem($Button));
            $Item->AddClass('nav-item');
            $Item->AddAttribute('role', 'presentation');
            if ($Keep)
            {
                $Item->AddClass('nav-overflow-keep');
            }

            $this->Panes->AddChild($Pane = new \HTML\Div($ID));
            $Pane->AddClasses(['tab-pane', 'fade']);
            $Pane->AddAttribute('role', 'tabpanel');
            $Pane->AddAttribute('aria-labelledby', $ID . '_tab');
            $Pane->TabIndex(0);
            if ($Active)
            {
                $Button->AddClass('active');
                $Pane->AddClasses(['show', 'active']);
            }
            $Pane->AddChild($Content);
            return $Pane;
        }

        public function AddChild(Element|string $Child): Element
        {
            throw new \InvalidArgumentException('Add tabs with AddTab()');
        }
    }
