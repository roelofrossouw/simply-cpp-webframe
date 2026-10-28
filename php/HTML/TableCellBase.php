<?php

    namespace HTML;

    abstract class TableCellBase extends Element
    {
        public function __construct(bool $IsHeader = false, string|Element|null $Content = null)
        {
            parent::__construct($IsHeader ? 'th' : 'td');
            // Not empty(): a value of '0' must still show
            if ($Content !== null && $Content !== '')
            {
                $this->AddChild($Content);
            }
        }
    }