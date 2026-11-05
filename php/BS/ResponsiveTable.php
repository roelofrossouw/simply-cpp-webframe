<?php

    namespace BS;

    /**
     * A table that scrolls sideways whenever it is wider than its place, on any screen
     *
     *   $Page->AddChild($Wrapper = new ResponsiveTable());
     *   $Table = $Wrapper->Table();
     *
     * Bootstrap's table-responsive-{size} scrolls only below that size, so above it a wide table was cut off by the
     * page's panel (.menu-target hides what overflows sideways); the wrapper is always table-responsive now.
     *
     * @see https://getbootstrap.com/docs/5.3/content/tables/#responsive-tables
     */
    class ResponsiveTable extends \HTML\Div
    {
        private \HTML\Table $Table;

        /**
         * @param ScreenSize $UpTo No longer used: a wide table scrolls on every screen (kept so callers that give it work)
         */
        public function __construct(ScreenSize $UpTo = ScreenSize::ExtraSmall)
        {
            parent::__construct();
            $this->AddClass('table-responsive');
            $this->AddChild($this->Table = new \HTML\Table());
        }

        public function Table(): \HTML\Table
        {
            return $this->Table;
        }
    }
