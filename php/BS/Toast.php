<?php

    namespace BS;

    class Toast extends \HTML\ClassDiv
    {
        private \HTML\ClassDiv $Body;
        private \HTML\Strong $Heading;
        private ?CloseButton $Close = null;
        private ?Color $Color = null;

        public function __construct(?string $Heading = null, ?string $Body = null)
        {
            parent::__construct('toast');
            $this->AddAttribute('role', 'alert');
            $this->AddAttribute('aria-live', 'assertive');
            $this->AddAttribute('aria-atomic', 'true');
            if (!empty($Heading))
            {
                $this->Heading($Heading);
            }
            if (!empty($Body))
            {
                $this->AddChild($Body);
            }
        }

        public function Heading(string $Content): void
        {
            if (empty($this->Heading))
            {
                parent::AddChild($Header = new \HTML\ClassDiv('toast-header'));
                $Header->AddChild($Icon = Icon::ExclamationSquareFill->Element());
                $Icon->Format->Margin(Side::Right, PadSize::Pad2);
                $Header->AddChild($this->Heading = new \HTML\Strong($Content));
                $this->Heading->Format->Margin(Side::Right, PadSize::Automatic);
                $Header->AddChild($Subheading = new \HTML\Small('just now'));
                $Header->AddChild($this->Close = new CloseButton(Dismiss::Toast));
                $this->StyleClose();
            }
            else
            {
                $this->Heading->AddChild($Content);
            }
        }

        public function AddChild(\HTML\Element|string $Child): \HTML\Element
        {
            if (empty($this->Body))
            {
                parent::AddChild($this->Body = new \HTML\ClassDiv('toast-body'));
            }
            return $this->Body->AddChild($Child);
        }

        /**
         * @see https://getbootstrap.com/docs/5.3/components/toasts/#color-schemes
         */
        public function Color(Color $Color): static
        {
            $this->Color = $Color;
            $this->AddClass($Color->For('text-bg'), 'text-bg');
            $this->AddClass('border-0');
            $this->StyleClose();
            return $this;
        }

        // A dark background needs the light close button
        private function StyleClose(): void
        {
            if ($this->Close !== null && $this->Color !== null)
            {
                $this->Close->AddAttribute('data-bs-theme', $this->Color->IsDark() ? 'dark' : 'light');
            }
        }
    }
