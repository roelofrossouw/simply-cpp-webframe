<?php

    namespace BS;

    /**
     * Where the page sits in the app, e.g. Home / Servers / Containers
     *
     * @see https://getbootstrap.com/docs/5.3/components/breadcrumb/
     */
    class Breadcrumb extends \HTML\Nav
    {
        private \HTML\OrderedList $List;

        public function __construct(string $Label = 'Breadcrumb')
        {
            parent::__construct();
            $this->AddAttribute('aria-label', $Label);
            parent::AddChild($this->List = new \HTML\OrderedList());
            $this->List->AddClass('breadcrumb');
        }

        /**
         * @param string|null $URL Where the item links to, null for the current page (the last item)
         */
        public function AddItem(string $Caption, ?string $URL = null): \HTML\ListItem
        {
            $this->List->AddChild($Item = new \HTML\ListItem($URL === null ? $Caption : new \HTML\Anchor($Caption, $URL)));
            $Item->AddClass('breadcrumb-item');
            if ($URL === null)
            {
                $Item->AddClass('active');
                $Item->AddAttribute('aria-current', 'page');
            }
            return $Item;
        }
    }
