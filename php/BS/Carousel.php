<?php

    namespace BS;

    /**
     * A slideshow of images, with previous/next controls and indicators
     *
     * @see https://getbootstrap.com/docs/5.3/components/carousel/
     */
    class Carousel extends \HTML\ClassDiv
    {
        private string $ID;
        private \HTML\Div $Indicators;
        private \HTML\Div $Inner;
        private int $Slides = 0;

        public function __construct(string $Label = 'Slides')
        {
            parent::__construct('carousel', $this->ID = uniqid('carousel_'));
            $this->AddClass('slide');
            $this->AddAttribute('aria-label', $Label);
            parent::AddChild($this->Indicators = new \HTML\ClassDiv('carousel-indicators'));
            parent::AddChild($this->Inner = new \HTML\ClassDiv('carousel-inner'));
            parent::AddChild($this->Control('prev', 'Previous'));
            parent::AddChild($this->Control('next', 'Next'));
        }

        private function Control(string $Direction, string $Label): \HTML\Button
        {
            $Button = new \HTML\Button();
            $Button->AddAttribute('type', 'button');
            $Button->AddClass('carousel-control-' . $Direction);
            $Button->AddAttribute('data-bs-target', '#' . $this->ID);
            $Button->AddAttribute('data-bs-slide', $Direction);
            $Button->AddChild((new \HTML\Span())->AddClass('carousel-control-' . $Direction . '-icon')->AddAttribute('aria-hidden', 'true'));
            $Button->AddChild((new \HTML\Span($Label))->AddClass('visually-hidden'));
            return $Button;
        }

        /**
         * @param string|null $Title Caption over the slide (shown from medium screens up)
         */
        public function AddSlide(string $Src, string $Alt, ?string $Title = null, ?string $Text = null): \HTML\Div
        {
            $First = $this->Slides++ == 0;

            $this->Indicators->AddChild($Dot = new \HTML\Button());
            $Dot->AddAttribute('type', 'button');
            $Dot->AddAttribute('data-bs-target', '#' . $this->ID);
            $Dot->AddAttribute('data-bs-slide-to', $this->Slides - 1);
            $Dot->AddAttribute('aria-label', 'Slide ' . $this->Slides);

            $this->Inner->AddChild($Slide = new \HTML\ClassDiv('carousel-item'));
            if ($First)
            {
                $Dot->AddClass('active');
                $Dot->AddAttribute('aria-current', 'true');
                $Slide->AddClass('active');
            }
            $Slide->AddChild($Image = new \HTML\Image($Src, $Alt));
            $Image->AddClasses(['d-block', 'w-100']);
            if ($Title !== null || $Text !== null)
            {
                $Slide->AddChild($Caption = new \HTML\ClassDiv('carousel-caption'));
                $Caption->Format->HideUntil(ScreenSize::Medium);
                if ($Title !== null)
                {
                    $Caption->AddChild(new \HTML\Header(5, $Title));
                }
                if ($Text !== null)
                {
                    $Caption->AddChild(new \HTML\Paragraph($Text));
                }
            }
            return $Slide;
        }

        /**
         * Move to the next slide by itself every $Milliseconds
         */
        public function AutoPlay(int $Milliseconds = 5000): static
        {
            $this->AddAttribute('data-bs-ride', 'carousel');
            $this->AddAttribute('data-bs-interval', $Milliseconds);
            return $this;
        }

        /**
         * Fade between slides instead of sliding
         */
        public function Fade(): static
        {
            $this->AddClass('carousel-fade');
            return $this;
        }

        public function AddChild(\HTML\Element|string $Child): \HTML\Element
        {
            throw new \InvalidArgumentException('Add slides to a carousel with AddSlide()');
        }
    }
