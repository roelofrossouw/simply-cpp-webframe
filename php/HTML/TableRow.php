<?php

    namespace HTML;

    use BS\Color;

    /**
     * <tr> Defines a row in a table
     *
     * @see https://www.w3schools.com/tags/tag_tr.asp
     */
    class TableRow extends Element
    {
        public function __construct()
        {
            parent::__construct('tr');
        }

        public function AddCell(string|Element|null $Content): TableCell
        {
            $this->AddChild($NewCell = new TableCell($Content));
            return $NewCell;
        }

        public function AddHeadingCell(string|Element $Content): TableHeadingCell
        {
            $this->AddChild($NewCell = new TableHeadingCell($Content));
            return $NewCell;
        }

        /**
         * Add a heading for this row, e.g. the record's name in the first column
         *
         * @link https://getbootstrap.com/docs/5.3/content/tables/#overview
         */
        public function AddRowHeadingCell(string|Element|null $Content): TableHeadingCell
        {
            $this->AddChild($NewCell = new TableHeadingCell($Content));
            $NewCell->AddAttribute('scope', 'row');
            return $NewCell;
        }

        /**
         * Set the row to not scroll off the screen
         *
         * @link https://getbootstrap.com/docs/4.0/utilities/position/#sticky-top
         * @return void
         */
        public function StickyTop(): void
        {
            $this->AddClass('sticky-top');
        }

        public function StickyBottom(): void
        {
            $this->AddClass('sticky-bottom');
        }

        public function Color(Color $Color): static
        {
            return $this->AddClass($Color->For('table'), 'table-color');
        }
    }