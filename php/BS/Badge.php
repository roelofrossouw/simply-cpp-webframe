<?php

    namespace BS;

    /**
     * A small count or label, e.g. next to a heading or in a button
     *
     * @see https://getbootstrap.com/docs/5.3/components/badge/
     */
    class Badge extends \HTML\Span
    {
        public function __construct(string $Text, Color $Color = Color::Secondary)
        {
            parent::__construct($Text);
            $this->AddClass('badge');
            $this->Format->TextBackground($Color);
        }

        /**
         * Fully rounded
         */
        public function Pill(): static
        {
            $this->Format->Rounded(Rounded::Pill);
            return $this;
        }
    }
