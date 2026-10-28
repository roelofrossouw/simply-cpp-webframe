<?php

    namespace BS;

    /**
     * A progress bar
     *
     * @see https://getbootstrap.com/docs/5.3/components/progress/
     */
    class Progress extends \HTML\ClassDiv
    {
        private \HTML\Div $Bar;

        /**
         * @param string $Label What it measures, for screen readers
         * @param bool   $ShowValue Show the percentage in the bar
         */
        public function __construct(int|float $Value, int|float $Max = 100, string $Label = 'Progress', bool $ShowValue = false)
        {
            parent::__construct('progress');
            $Percent = $Max > 0 ? max(0, min(100, round($Value / $Max * 100))) : 0;
            $this->AddAttribute('role', 'progressbar');
            $this->AddAttribute('aria-label', $Label);
            $this->AddAttribute('aria-valuenow', $Value);
            $this->AddAttribute('aria-valuemin', 0);
            $this->AddAttribute('aria-valuemax', $Max);
            $this->AddChild($this->Bar = new \HTML\ClassDiv('progress-bar'));
            $this->Bar->AddStyle('width', $Percent . '%');
            if ($ShowValue)
            {
                $this->Bar->AddChild($Percent . '%');
            }
        }

        public function Color(Color $Color): static
        {
            $this->Bar->Format->Background($Color);
            return $this;
        }

        public function Striped(bool $Animated = false): static
        {
            $this->Bar->AddClass('progress-bar-striped');
            if ($Animated)
            {
                $this->Bar->AddClass('progress-bar-animated');
            }
            return $this;
        }

        /**
         * Height of the bar in pixels (Bootstrap's default is 16)
         */
        public function Height(int $Pixels): static
        {
            $this->AddStyle('height', $Pixels . 'px');
            return $this;
        }
    }
