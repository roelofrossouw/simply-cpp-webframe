<?php

    namespace BS;

    /**
     * Alert Box
     *
     * A plain coloured alert container without a header, to fill with any content.
     *
     * @see https://getbootstrap.com/docs/5.3/components/alerts/
     */
    class AlertBox extends \HTML\ClassDiv
    {
        private ?Color $Color = null;

        public function __construct(?Color $Color = Color::Secondary, PadSize $Padding = PadSize::Pad2)
        {
            parent::__construct('alert');
            $this->AddAttribute('role', 'alert');
            $this->Format->Padding(Side::All, $Padding);
            if ($Color !== null)
            {
                $this->Color($Color);
            }
        }

        public function Color(Color $Color): static
        {
            $this->Color = $Color;
            $this->AddClass($Color->For('alert'), 'alert-color');
            return $this;
        }

        public function GetColor(): ?Color
        {
            return $this->Color;
        }
    }
