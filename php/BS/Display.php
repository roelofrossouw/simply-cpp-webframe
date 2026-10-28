<?php

    namespace BS;

    /**
     * Bootstrap Display Type
     *
     * @see https://getbootstrap.com/docs/5.3/utilities/display/
     */
    enum Display: string
    {
        case None = 'none';
        case Inline = 'inline';
        case InlineBlock = 'inline-block';
        case Block = 'block';
        case Grid = 'grid';
        case InlineGrid = 'inline-grid';
        case Table = 'table';
        case TableCell = 'table-cell';
        case TableRow = 'table-row';
        case Flex = 'flex';
        case InlineFlex = 'inline-flex';
    }