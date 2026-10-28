<?php

    namespace BS;

    use HTML\Div;

    class Col extends Div
    {
        public function __construct(int $Span = 0)
        {
            parent::__construct();
            if ($Span > 0)
            {
                $this->Format->Columns($Span);
            }
            else
            {
                $this->AddClass('col', 'col'); // Same family as Format->Columns(), so it can be replaced
            }
        }
    }