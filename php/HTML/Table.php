<?php

    namespace HTML;

    /**
     * <table> Defines a table
     *
     * @see https://www.w3schools.com/tags/tag_table.asp
     */
    class Table extends Element
    {
        private Caption $Caption;
        private TableHeader $Header;
        private TableBody $Body;
        private TableFooter $Footer;
        private bool $HasColumnHeadings = false;

        public function __construct()
        {
            parent::__construct('table');
            $this->AddClass('table');
            $this->AddChild($this->Caption = new Caption());
            $this->AddChild($this->Header = new TableHeader());
            $this->AddChild($this->Body = new TableBody());
            $this->AddChild($this->Footer = new TableFooter());
        }

        /**
         * Show alternating rows in a shaded background.
         *
         * @link https://getbootstrap.com/docs/4.0/content/tables/#striped-rows
         * @return void
         */
        public function Striped(): void
        {
            $this->AddClass('table-striped');
        }

        /**
         * Highlight the row under the mouse
         *
         * @see https://getbootstrap.com/docs/5.3/content/tables/#hoverable-rows
         */
        public function Hover(): static
        {
            return $this->AddClass('table-hover');
        }

        /**
         * Borders around every cell, or none at all
         */
        public function Bordered(bool $Bordered = true): static
        {
            return $this->AddClass($Bordered ? 'table-bordered' : 'table-borderless', 'table-borders');
        }

        /**
         * Half the cell padding, for dense lists
         */
        public function Small(): static
        {
            return $this->AddClass('table-sm');
        }

        /**
         * Put the caption above the table instead of under it
         */
        public function CaptionTop(): static
        {
            return $this->AddClass('caption-top');
        }

        /**
         * Colour the whole table, e.g. Color::Dark for a dark table
         */
        public function Color(\BS\Color $Color): static
        {
            return $this->AddClass($Color->For('table'), 'table-color');
        }

        public function Caption(string $Caption): void
        {
            $this->Caption->AddChild(new Text($Caption));
        }

        /**
         * Fill the table from rows (e.g. DB::Rows()), using the first row's keys as the column headings
         *
         * @param array $Rows        Rows of column => value
         * @param bool  $RowHeading  Show each row's first column as the heading for that row
         * @param array $SortFields  Columns the user can sort on by clicking the heading (see DB::Sort())
         * @return $this
         */
        public function AddRows(array $Rows, bool $RowHeading = false, array $SortFields = []): static
        {
            if (empty($Rows))
            {
                return $this;
            }
            if (!$this->HasColumnHeadings)
            {
                $this->HasColumnHeadings = true;
                $Headings = $this->Header->AddRow();
                foreach (array_keys(reset($Rows)) as $Column)
                {
                    $Cell = $Headings->AddHeadingCell(ucfirst(str_replace('_', ' ', $Column)));
                    $Cell->AddAttribute('scope', 'col');
                    if (in_array($Column, $SortFields, true))
                    {
                        $Cell->Sort($Column);
                    }
                }
            }
            foreach ($Rows as $Row)
            {
                $TableRow = $this->Body->AddRow();
                $IsHeading = $RowHeading;
                foreach ($Row as $Value)
                {
                    $Value = $Value === null ? null : (string)$Value;
                    $IsHeading ? $TableRow->AddRowHeadingCell($Value) : $TableRow->AddCell($Value);
                    $IsHeading = false;
                }
            }
            return $this;
        }

        /**
         * @return TableHeader
         */
        public function Header(): TableHeader
        {
            return $this->Header;
        }

        /**
         * Return a reference to the table body object
         *
         * @return TableBody
         */
        public function Body(): TableBody
        {
            return $this->Body;
        }

        public function Footer(): TableFooter
        {
            return $this->Footer;
        }

    }