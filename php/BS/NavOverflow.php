<?php

    namespace BS;

    /**
     * The wrapper around a nav (.nav, .navbar-nav) that moves the items that don't fit into a More menu at its end,
     * last first, and back as room comes: Bootstrap 6's nav overflow ("Priority+"), done by js/app.js on Bootstrap 5.3
     * with the same markup. It watches its own width, not the window's, so it works in a popup or a side panel too.
     * BS\Tabs::Overflow() and BS\Menu::Overflow() use it; for a nav of your own:
     *
     *   $Wrapper = new BS\NavOverflow();
     *   $Wrapper->AddChild($Nav);   // a ul.nav
     *   $Wrapper->Enable();
     *
     * Until Enable() the wrapper takes no part in the layout (display: contents).
     *
     * @see https://getbootstrap.com/docs/6.0/components/nav-overflow/
     */
    class NavOverflow extends \HTML\ClassDiv
    {
        public function __construct()
        {
            parent::__construct('nav-wrap');
        }

        /**
         * @param string|false $More The More button's caption; false: only its icon (named "More" for screen readers)
         * @param int $Threshold At least this many items stay visible
         * @param ScreenSize|int|null $CollapseBelow Narrower than this (the screen size's breakpoint, or pixels), every
         *                                          item goes into More but those kept (Keep(), .nav-overflow-keep)
         */
        public function Enable(string|false $More = 'More', int $Threshold = 0, ScreenSize|int|null $CollapseBelow = null): static
        {
            $this->AddClass('nav-overflow');
            $this->AddAttribute('data-bs-toggle', 'nav-overflow');
            if ($More !== 'More')
            {
                $this->AddAttribute('data-bs-more-text', $More === false ? '' : $More);
            }
            if ($Threshold > 0)
            {
                $this->AddAttribute('data-bs-threshold', $Threshold);
            }
            $Below = $CollapseBelow instanceof ScreenSize ? $CollapseBelow->Width() : $CollapseBelow;
            if (!empty($Below))
            {
                $this->AddAttribute('data-bs-collapse-below', $Below);
            }
            return $this;
        }
    }
