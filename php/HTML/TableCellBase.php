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

        /**
         * Wrap the text onto as many lines as it takes. A table's body cells are one line otherwise, cut off with an
         * ellipsis and the full text in a popover. On a column's heading cell (in the table's header), the whole
         * column wraps.
         */
        public function Wrap(): static
        {
            return $this->AddClass('cell-wrap');
        }
    }