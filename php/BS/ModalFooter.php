<?php

    namespace BS;

    class ModalFooter extends \HTML\ClassDiv
    {
        private CloseButton $Close;

        public function __construct()
        {
            parent::__construct('modal-footer');
            $this->Format->Padding(Side::All, PadSize::None);
            $this->AddChild($this->Close = new CloseButton(Dismiss::Modal, 'Close'));
        }

        /**
         * Add a button next to Close, e.g. a form's Save button (see ModalDialog::AddButton())
         */
        public function AddButton(BaseButton $Button): BaseButton
        {
            $this->AddChild($Button);
            return $Button;
        }

        protected function Contents(): string
        {
            // Close stays last, after the buttons added later
            $Buttons = array_filter($this->Children, fn($Child) => $Child !== $this->Close);
            return implode(' ', [...$Buttons, $this->Close]);
        }
    }
