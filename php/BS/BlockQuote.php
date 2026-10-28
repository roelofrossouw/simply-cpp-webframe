<?php

    namespace BS;

    /**
     * A quotation, with who said it and where
     *
     * @see https://getbootstrap.com/docs/5.3/content/typography/#blockquotes
     */
    class BlockQuote extends \HTML\Figure
    {
        /**
         * @param string|null $Source Who said it, e.g. 'Someone famous'
         * @param string|null $Work   Where, e.g. the title of a book (shown in italics)
         */
        public function __construct(\HTML\Element|string $Quote, ?string $Source = null, ?string $Work = null)
        {
            parent::__construct();
            $this->AddChild($Block = new \HTML\BlockQuote());
            $Block->AddClass('blockquote');
            $Block->AddChild($Quote instanceof \HTML\Element ? $Quote : new \HTML\Paragraph($Quote));
            if ($Source !== null || $Work !== null)
            {
                $this->AddChild($Caption = new \HTML\FigureCaption());
                $Caption->AddClass('blockquote-footer');
                if ($Source !== null)
                {
                    $Caption->AddChild($Source . ($Work !== null ? ' in' : ''));
                }
                if ($Work !== null)
                {
                    $Caption->AddChild((new \HTML\Citation($Work))->AddAttribute('title', $Work));
                }
            }
        }
    }
