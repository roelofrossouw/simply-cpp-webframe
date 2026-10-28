<?php

    namespace HTML;

    /**
     * <p> Defines a paragraph
     *
     * @see https://www.w3schools.com/tags/tag_p.asp
     */
    class Paragraph extends Element
    {
        public function __construct(Element|string $Content, ?string $ID = null)
        {
            parent::__construct('p', $ID);
            $this->AddChild($Content);
        }

        public function AddParagraph(string $Content)
        {
            $this->AddSibling(new Paragraph($Content));
        }

        public function AddLine(string $Content)
        {
            $this->AddChild(new LineBreak());
            $this->AddChild(new Text($Content));
        }

        /**
         * Utility function as a shortcut to add an Anchor Element inline.
         *
         * @param string $Content
         * @param string $Href
         * @param bool   $IsLink
         * @return $this
         * @see \HTML\Anchor::__construct
         * @see \HTML\Anchor
         */
        public function AddAnchor(string $Content, string $Href, bool $IsLink = false): self
        {
            $this->AddChild(new Anchor($Content, $Href, IsLink: $IsLink));
            return $this;
        }

        /**
         * Make this a lead paragraph. It stands out from regular paragraphs.
         *
         * @return void
         * @see https://getbootstrap.com/docs/5.3/content/typography/#lead
         */
        public function Lead()
        {
            $this->AddClass('lead');
        }

    }