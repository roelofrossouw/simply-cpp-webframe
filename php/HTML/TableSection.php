<?php

    namespace HTML;

    class TableSection extends Element
    {
        public function __construct($Part = 'tbody')
        {
            parent::__construct($Part);
        }

        public function AddRow(): TableRow
        {
            $this->AddChild($NewRow = new TableRow());
            return $NewRow;
        }
    }