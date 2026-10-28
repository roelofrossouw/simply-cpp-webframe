<?php

    namespace BS;

    /**
     * A loading indicator
     *
     * @see https://getbootstrap.com/docs/5.3/components/spinners/
     */
    class Spinner extends \HTML\Div
    {
        /**
         * @param bool   $Grow  A pulsing dot instead of a spinning ring
         * @param string $Label Read out by screen readers
         */
        public function __construct(?Color $Color = null, bool $Grow = false, bool $Small = false, string $Label = 'Loading...')
        {
            parent::__construct();
            $Type = $Grow ? 'spinner-grow' : 'spinner-border';
            $this->AddClass($Type);
            if ($Small)
            {
                $this->AddClass($Type . '-sm');
            }
            if ($Color !== null)
            {
                $this->Format->Color($Color);
            }
            $this->AddAttribute('role', 'status');
            $this->AddChild((new \HTML\Span($Label))->AddClass('visually-hidden'));
        }
    }
