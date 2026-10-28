<?php

    namespace BS;

    /**
     * A link with an icon, e.g. "Documentation ->"
     *
     * @see https://getbootstrap.com/docs/5.3/helpers/icon-link/
     */
    class IconLink extends \HTML\Anchor
    {
        /**
         * @param bool $IconAfter Icon after the text instead of before
         * @param bool $Animate   Nudge the icon on hover
         */
        public function __construct(string $Caption, Icon $Icon, string $URL, bool $IconAfter = false, bool $Animate = false)
        {
            parent::__construct($IconAfter ? $Caption : $Icon->Element(), $URL);
            $this->AddChild($IconAfter ? $Icon->Element() : $Caption);
            $this->AddClass('icon-link');
            if ($Animate)
            {
                $this->AddClass('icon-link-hover');
            }
        }
    }
