<?php

    namespace BS;

    class FontFormatter
    {
        private \HTML\Element $Element;

        public function __construct(\HTML\Element $Element)
        {
            $this->Element = $Element;
        }

        public function Weight(FontWeight $Weight): static
        {
            $this->Element->AddClass('fw-' . $Weight->value, 'fw');
            return $this;
        }

        public function Size(FontSize $Size): static
        {
            $this->Element->AddClass('fs-' . $Size->value, 'fs');
            // ->AddStyle('font-size', '24pt');
            return $this;
        }

    }