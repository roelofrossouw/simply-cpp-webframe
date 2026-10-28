<?php

    namespace BS;

    class FlexRow extends BaseRow
    {
        public function __construct()
        {
            parent::__construct();
            $this->AddClass('flex-row');
            $this->Format->Display(\BS\Display::Flex);
        }

    }