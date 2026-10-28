<?php

    namespace BS;

    abstract class BaseRow extends \HTML\Div
    {
        public function __construct(int $Columns = 12)
        {
            parent::__construct();
            $this->Columns($Columns);
        }

        public function Columns(int $Columns, ScreenSize $MinSize = ScreenSize::ExtraSmall): void
        {
            if ($Columns > 0 && $Columns < 12)
            {
                $Family = 'row-cols' . $MinSize->Infix();
                $this->AddClass($Family . '-' . $Columns, $Family);
            }
        }
    }