<?php

    namespace BS;

    use HTML\Div;

    class FluidContainer extends Div
    {
        public function __construct()
        {
            parent::__construct();
            $this->AddClass('container-fluid');
        }
    }