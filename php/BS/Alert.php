<?php

    namespace BS;

    class Alert extends AlertBox
    {
        private \HTML\Element $Header;
        private \HTML\Element $Content;

        public function __construct(\HTML\Element|string $Header, \HTML\Element|string $Content)
        {
            parent::__construct(null, PadSize::Pad4);
            // $this->Format->Margin(Side::All, PadSize::Pad4);
            $this->AddChild($this->Header = $Header instanceof \HTML\Element ? $Header : new \HTML\Header(1, $Header));
            if ($this->Header instanceof \HTML\Header)
            {
                $this->Header->AddClass('alert-heading');
            }
            $this->AddChild($this->Content = $Content instanceof \HTML\Element ? $Content : new \HTML\Paragraph($Content));
        }

        public function Header(): \HTML\Element
        {
            return $this->Header;
        }

    }