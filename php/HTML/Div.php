<?php

    namespace HTML;

    /**
     * <div> Defines a section in a document
     *
     * @see https://www.w3schools.com/tags/tag_div.asp
     */
    class Div extends Element
    {
        public function __construct(?string $ID = null, ?string $Content = null)
        {
            parent::__construct('div', $ID);
            if (!empty($Content))
            {
                $this->AddChild($Content);
            }
        }

        public function AddHeader(string $Content, int $Level = 3): Header
        {
            $this->AddChild($Header = new Header($Level, $Content));
            return $Header;
        }

        public function AddParagraph(\HTML\Paragraph|string $Content): Paragraph
        {
            $this->AddChild($Paragraph = $Content instanceof Paragraph ? $Content : new Paragraph($Content));
            return $Paragraph;
        }
    }