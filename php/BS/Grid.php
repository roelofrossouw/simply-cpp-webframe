<?php

    namespace BS;

    use HTML\
    {ClassDiv, Div, Element};

    class Grid extends ClassDiv
    {
        private BaseRow $CurrentRow;
        private PadSize $Spacing;
        private int $Columns;

        public function __construct(int $Columns = 12, PadSize $Spacing = PadSize::Pad2)
        {
            $this->Columns = $Columns;
            $this->Spacing = $Spacing;
            parent::__construct('container-fluid');
            $this->Format->Gutter(Side::Horizontal, PadSize::None);
            $this->Format->Padding(Side::Bottom, $this->Spacing);
        }

        public function Columns(int $Columns, ScreenSize $MinSize = ScreenSize::ExtraSmall): void
        {
            $this->CurrentRow()->Columns($Columns, $MinSize);
        }

        private function CurrentRow(): BaseRow
        {
            if (empty($this->CurrentRow))
            {
                $this->NewRow($this->Columns);
            }
            return $this->CurrentRow;
        }

        public function NewRow(int $Columns = 12): void
        {
            $this->Columns = $Columns;
            parent::AddChild($this->CurrentRow = new Row($Columns));
            $this->CurrentRow->Format->Gutter(Side::All, $this->Spacing);
            $this->CurrentRow->Format->Padding(Side::Top, $this->Spacing);
        }

        public function AddChild(string|Element $Child): Element
        {
            return $this->Col($Child);
        }

        public function Col(string|Element $Child, int $Width = 0): Element
        {
            $Column = $this->CurrentRow() instanceof FlexRow ? new Div() : new Col($Width);
            $this->CurrentRow()->AddChild($Column);
            if ($Child instanceof BaseButton)
            {
                $Child->Format->Width(Percent::P100);
            }
            elseif ($Child instanceof DropDownButton)
            {
                $Child->Toggle()->Format->Width(Percent::P100);
            }
            $Column->AddChild($Child instanceof Element ? $Child : new Div(Content: $Child));
            return $Column;
        }

        public function RowFormat(): Formatter
        {
            return $this->CurrentRow()->Format;
        }

        public function NewFlexRow(): void
        {
            parent::AddChild($this->CurrentRow = new FlexRow());
            $this->CurrentRow->Format->Gutter(Side::All, $this->Spacing);
            $this->CurrentRow->Format->Padding(Side::Top, $this->Spacing);
        }

    }