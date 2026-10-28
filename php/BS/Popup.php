<?php

    namespace BS;

    use HTML\Div;

    abstract class Popup extends Div
    {
        // ID is required, to be able to reference later.
        public function __construct(string $ID, bool $HasDefaultFocus = false)
        {
            parent::__construct(ID: $ID);
            if (!$HasDefaultFocus)
            {
                $this->AddAttribute('tabindex', '-1');
            }
        }
    }