<?php

    namespace BS;

    /**
     * A table that scrolls sideways on screens too narrow for it
     *
     *   $Page->AddChild($Wrapper = new ResponsiveTable());
     *   $Table = $Wrapper->Table();
     *
     * @see https://getbootstrap.com/docs/5.3/content/tables/#responsive-tables
     */
    class ResponsiveTable extends \HTML\Div
    {
        private \HTML\Table $Table;

        /**
         * @param ScreenSize $UpTo Scroll below this screen size, ExtraSmall to always allow scrolling
         */
        public function __construct(ScreenSize $UpTo = ScreenSize::ExtraSmall)
        {
            parent::__construct();
            $this->AddClass('table-responsive' . $UpTo->Infix());
            $this->AddChild($this->Table = new \HTML\Table());
        }

        public function Table(): \HTML\Table
        {
            return $this->Table;
        }
    }
