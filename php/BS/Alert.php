<?php

    namespace BS;

    /**
     * An alert with a heading and content; with an icon, the icon stands in a column of its own beside them, so the text
     * stays a block next to it
     *
     * @see AlertSuccess, AlertInfo, AlertWarning, AlertError, which add an icon and colour
     */
    class Alert extends AlertBox
    {
        private \HTML\Element $Header;
        private \HTML\Element $Content;
        // Beside the icon: where the heading, the content and anything added later go
        private ?\HTML\Div $Text = null;

        public function __construct(\HTML\Element|string $Header, \HTML\Element|string $Content, ?Icon $Icon = null)
        {
            parent::__construct(null, PadSize::Pad4);
            if ($Icon !== null)
            {
                $this->AddClasses(['d-flex', 'align-items-start', 'gap-3']);
                parent::AddChild($Symbol = new IconElement($Icon));
                $Symbol->Format->Font->Size(FontSize::Size1);
                $Symbol->AddClasses(['flex-shrink-0', 'lh-1']);
                parent::AddChild($this->Text = new \HTML\Div());
                $this->Text->AddClass('flex-grow-1');
            }
            $this->AddChild($this->Header = $Header instanceof \HTML\Element ? $Header : new \HTML\Header($Icon === null ? 1 : 2, $Header));
            if ($this->Header instanceof \HTML\Header)
            {
                $this->Header->AddClass('alert-heading');
            }
            $this->AddChild($this->Content = $Content instanceof \HTML\Element ? $Content : new \HTML\Paragraph($Content));
            if ($Icon !== null)
            {
                $this->Content->AddClass('mb-0');
            }
        }

        public function AddChild(\HTML\Element|string $Child): \HTML\Element
        {
            return $this->Text === null ? parent::AddChild($Child) : $this->Text->AddChild($Child);
        }

        public function Header(): \HTML\Element
        {
            return $this->Header;
        }
    }
