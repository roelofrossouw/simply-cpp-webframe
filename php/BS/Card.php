<?php

    namespace BS;

    use HTML\Element;
    use HTML\Header;
    use HTML\Paragraph;

    /**
     * Card
     *
     * Bootstrap’s cards provide a flexible and extensible content container with multiple variants and options.
     * This implementation makes a card with a header and body.
     * It is possible to add more body sections, or a footer.
     *
     * @see https://getbootstrap.com/docs/5.3/components/card/
     */
    class Card extends \HTML\ClassDiv
    {
        // ID is required, to be able to reference later.
        private ?\HTML\Div $Header = null;
        private ?\HTML\Div $Body = null;
        private ?\HTML\Div $Footer = null;

        public function __construct(PadSize $Margin = PadSize::Pad2)
        {
            parent::__construct('card');
            $this->Format->Margin(Side::All, $Margin);
        }

        public function AddHeader(string $Content, int $Level = 2): Header
        {
            return $this->Header()->AddHeader($Content, $Level);
        }

        private function Header(): \HTML\Div
        {
            if (empty($this->Header))
            {
                parent::AddChild($this->Header = new \HTML\ClassDiv('card-header'));
            }
            return $this->Header;
        }


        public function AddChild(Element|string $Child): Element
        {
            return $this->Body()->AddChild($Child);
        }

        public function Body(): \HTML\Div
        {
            if (empty($this->Body))
            {
                parent::AddChild($this->Body = new \HTML\ClassDiv('card-body'));
            }
            return $this->Body;
        }

        public function AddImage(\HTML\Image $Image): Element
        {
            $Image->AddClass('card-img-top');
            return parent::PrependChild($Image);
        }

        public function AddTitle(string $Child): Element
        {
            $Title = new \HTML\Header(5, $Child);
            // $Title->AddClass('card-title');
            return $this->Body()->AddChild($Title);
        }

        public function AddHeaderElement(Element|string $Content): void
        {
            $this->Header()->AddChild($Content);
        }

        public function AddParagraph(\HTML\Paragraph|string $Content): Paragraph
        {
            return $this->Body()->AddParagraph($Content);
        }

        public function AddFooter(Element|string $Child): Element
        {
            return $this->Footer()->AddChild($Child);
        }

        private function Footer(): \HTML\Div
        {
            if (empty($this->Footer))
            {
                parent::AddChild($this->Footer = new \HTML\ClassDiv('card-footer'));
            }
            return $this->Footer;
        }
    }