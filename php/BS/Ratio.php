<?php

    namespace BS;

    /**
     * Keeps embedded content (a video, map or iframe) at an aspect ratio as it scales
     *
     * @see https://getbootstrap.com/docs/5.3/helpers/ratio/
     */
    class Ratio extends \HTML\ClassDiv
    {
        public function __construct(\HTML\Element $Content, AspectRatio $Ratio = AspectRatio::SixteenByNine)
        {
            parent::__construct('ratio');
            $this->AddClass('ratio-' . $Ratio->value);
            $this->AddChild($Content);
        }
    }
