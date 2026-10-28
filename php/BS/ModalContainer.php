<?php

    namespace BS;

    class ModalContainer extends Popup
    {
        // ID is required, to be able to reference later.
        public function __construct(string $ID, bool $HasDefaultFocus = false)
        {
            parent::__construct($ID, $HasDefaultFocus);
            $this->AddClasses(['modal', 'fade']);
            // app.js points aria-labelledby at the loaded dialog's title
            $this->AddAttribute('aria-hidden', 'true');
        }
    }