<?php

    namespace BS;

    class PlaceHolder extends \HTML\Span
    {
        public function __construct(array $Spacers = [1, 2, 3])
        {
            parent::__construct();
            $this->AddClass('placeholder-glow');
            foreach ($Spacers as $Space)
            {
                $this->AddChild(new \HTML\Span())->AddClass('placeholder col-' . $Space);
            }
        }
    }