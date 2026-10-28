<?php

    namespace BS;

    use HTML\Element;

    class IconElement extends Element
    {

        public function __construct(Icon $IconType)
        {
            parent::__construct('i');
            $this->AddClasses(['bi', $IconType->value]);
            // Decorative, screen readers read the caption next to it
            $this->AddAttribute('aria-hidden', 'true');
        }
    }