<?php

    namespace BS;

    /**
     * Direction of flex items
     *
     * @see https://getbootstrap.com/docs/5.3/utilities/flex/#direction
     */
    enum FlexDirection: string
    {
        case Row = 'row';
        case RowReverse = 'row-reverse';
        case Column = 'column';
        case ColumnReverse = 'column-reverse';
    }
