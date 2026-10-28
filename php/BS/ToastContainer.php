<?php

    namespace BS;

    class ToastContainer extends \HTML\ClassDiv
    {
        public function __construct(string $ID)
        {
            parent::__construct('toast-container', $ID);
            $this->AddClasses(['position-fixed', 'bottom-0', 'end-0']);
            $this->Format->Padding(Side::Bottom, PadSize::Pad5);
            $this->Format->Padding(Side::Right, PadSize::Pad2);
            $this->Format->MaxHeight(Percent::P50);
        }
    }
