<?php

    namespace BS;

    /**
     * Items stacked under each other, or side by side, with space between them
     *
     * @see https://getbootstrap.com/docs/5.3/helpers/stacks/
     */
    class Stack extends \HTML\Div
    {
        public function __construct(bool $Horizontal = false, PadSize $Gap = PadSize::Pad2)
        {
            parent::__construct();
            $this->AddClass($Horizontal ? 'hstack' : 'vstack');
            $this->Format->Gap($Gap);
        }
    }
