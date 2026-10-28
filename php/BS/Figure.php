<?php

    namespace BS;

    /**
     * An image with a caption
     *
     * @see https://getbootstrap.com/docs/5.3/content/figures/
     */
    class Figure extends \HTML\Figure
    {
        public function __construct(string $Src, string $Alt, ?string $Caption = null)
        {
            parent::__construct();
            $this->AddClass('figure');
            $this->AddChild($Image = new \HTML\Image($Src, $Alt));
            $Image->AddClasses(['figure-img', 'img-fluid', 'rounded']);
            if ($Caption !== null)
            {
                $this->AddChild((new \HTML\FigureCaption($Caption))->AddClass('figure-caption'));
            }
        }
    }
