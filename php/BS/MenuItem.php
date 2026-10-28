<?php

    namespace BS;

    class MenuItem extends BaseMenuItem
    {
        public function __construct(Menu $Menu, Icon $Icon, string $Caption, ?string $URL = null, ?string $ID = null)
        {
            parent::__construct($Menu, $Icon, $Caption, $URL, $ID);
            $this->AddClass('nav-item');
        }

    }