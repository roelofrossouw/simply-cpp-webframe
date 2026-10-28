<?php

    namespace HTML;

    /**
     * <th> Defines a header cell in a table
     *
     * @property string $Abbr text
     * @property string|int $ColSpan number
     * @property string $Headers id
     * @property string|int $RowSpan number
     * @property string $Scope col|colgroup|row|rowgroup
     * @see https://www.w3schools.com/tags/tag_th.asp
     */
    class TableHeadingCell extends TableCellBase
    {
        public function __construct(string|Element|null $Content = null)
        {
            parent::__construct(true, $Content);
        }

        /**
         * Sort the table on $Field when the heading is clicked (?_order= and ?_desc=, see DB::Sort()),
         * with an arrow on the column it's sorted on now
         */
        public function Sort(string $Field): static
        {
            // Clicking the column it's sorted on turns the order round
            $this->Action(\Server::Page(['_order' => $Field,
                '_desc' => empty(\QueryString::Get('_desc')) || \QueryString::Get('_order') != $Field]));
            $this->AddClass('sortable');
            if (\QueryString::Get('_order') === $Field)
            {
                $Descending = !empty(\QueryString::Get('_desc'));
                $this->AddAttribute('aria-sort', $Descending ? 'descending' : 'ascending');
                $this->AddChild(($Descending ? \BS\Icon::SortDown : \BS\Icon::SortUp)->Element()->AddClass('ms-1'));
            }
            return $this;
        }
    }