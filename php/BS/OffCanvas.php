<?php

    namespace BS;

    class OffCanvas extends \HTML\ClassDiv
    {
        private OffCanvasBody $Body;

        public function __construct(string $Header)
        {
            parent::__construct('offcanvas-header');
            parent::AddChild((new \HTML\Header(3, $Header, uniqid('title_')))->AddClass('offcanvas-title'));
            parent::AddChild(new CloseButton(DismissType: Dismiss::OffCanvas));
            $this->AddSibling($this->Body = new OffCanvasBody());
        }

        public function AddChild(\HTML\Element|string $Child): \HTML\Element
        {
            return $this->Body->AddChild($Child);
        }

    }