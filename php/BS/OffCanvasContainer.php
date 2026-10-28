<?php

    namespace BS;

    class OffCanvasContainer extends Popup
    {
        // ID is required, to be able to reference later.
        public function __construct(string $ID, bool $HasDefaultFocus = false)
        {
            parent::__construct($ID, $HasDefaultFocus);
            $this->AddClasses(['offcanvas', 'offcanvas-start']);
        }
    }