<?php

    namespace BS;

    class AlertHeader extends IconElement
    {
        public function __construct(Icon $IconType, string $Header)
        {
            parent::__construct($IconType);
            $this->Format->Font->Size(FontSize::Size1);
            $this->Format->Padding(Side::Right, PadSize::Pad3);
            $this->Format->Float(Side::Left);
            $this->AddSibling((new \HTML\Header(2, $Header))->AddClass('alert-heading'));
        }
    }