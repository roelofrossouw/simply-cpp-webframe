<?php

    namespace BS;

    use HTML\Div;

    class Container extends Div
    {
        public function __construct(ScreenSize $Size = ScreenSize::ExtraSmall)
        {
            parent::__construct();
            $this->AddClass('container' . $Size->Infix());
        }
    }