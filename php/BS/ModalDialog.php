<?php

    namespace BS;

    use HTML\
    {ClassDiv, Div, Element, Header};

    class ModalDialog extends Div
    {
        private Div $Body;
        private ModalFooter $Footer;

        public function __construct($Title, \BS\ScreenSize $Size = \BS\ScreenSize::ExtraLarge)
        {
            parent::__construct();
            $this->AddClasses(['modal-dialog', 'modal-dialog-scrollable']);

            $this->AddClasses($Size->ModalClasses());

            parent::AddChild($Content = new ClassDiv('modal-content'));
            $Content->AddChild(new ModalHeader($Title));
            $Content->AddChild($this->Body = new ClassDiv('modal-body'));
            $Content->AddChild($this->Footer = new ModalFooter());
        }

        /**
         * Add a button to the footer, next to Close. A footer button posts the form in the popup's body, like a
         * button inside the form would (app.js loadPage()). Call Modal() on it to show the response in the popup,
         * and SkipForm() on buttons that shouldn't post the form.
         */
        public function AddButton(BaseButton $Button): BaseButton
        {
            return $this->Footer->AddButton($Button);
        }

        /**
         * What the refresh button next to Close loads, when it isn't the popup's own page with its action left out:
         * e.g. the job an action just started
         */
        public function RefreshURL(string $URL): static
        {
            $this->AddAttribute('data-refresh-url', $URL);
            return $this;
        }

        public function AddChild(Element|string $Child): Element
        {
            return $this->Body->AddChild($Child);
        }

        public function AddHeader(string $Content, int $Level = 5): Header
        {
            return $this->Body->AddHeader($Content, $Level);
        }

        public function AddParagraph(Element|string $Content): \HTML\Paragraph
        {
            return $this->Body->AddParagraph($Content);
        }
    }