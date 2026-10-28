<?php

    namespace HTML;

    /**
     * <td> Defines a cell in a table
     *
     * @property string|int $ColSpan number
     * @property string $Headers id
     * @property string|int $RowSpan number
     * @see https://www.w3schools.com/tags/tag_td.asp
     */
    class TableCell extends TableCellBase
    {
        public function __construct(string|Element|null $Content = null)
        {
            parent::__construct(false, $Content);
        }
    }