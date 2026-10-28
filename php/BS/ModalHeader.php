<?php

    namespace BS;

    class ModalHeader extends \HTML\ClassDiv
    {
        public function __construct($Title)
        {
            parent::__construct('modal-header');
            $this->AddChild(new ModalTitle($Title));
            // Loads the popup's page again (app.js refreshModal()), without repeating an action it was asked for
            $this->AddChild($Refresh = new \HTML\Button(Icon::ArrowClockwise->Element()));
            $Refresh->AddAttribute('type', 'button');
            // modal-link: the page comes back into the popup; data-skip-form: the form in it isn't posted
            $Refresh->AddClasses(['btn', 'btn-link', 'p-0', 'fs-5', 'lh-1', 'link-secondary', 'modal-refresh', 'modal-link', 'ms-auto', 'me-3']);
            $Refresh->AddAttribute('data-skip-form', 'true');
            $Refresh->AddAttribute('aria-label', 'Refresh');
            $Refresh->AddAttribute('title', 'Refresh');
            // Bootstrap pushes Close right with an automatic left margin; the refresh button does that now, so Close sits beside it
            $this->AddChild($Close = new CloseButton(DismissType: Dismiss::Modal));
            $Close->AddClass('ms-0');
        }
    }