<?php

    namespace BS;

    class Row extends BaseRow
    {
        public function __construct(int $Columns = 12)
        {
            parent::__construct($Columns);
            $this->AddClass('row');
        }

    }