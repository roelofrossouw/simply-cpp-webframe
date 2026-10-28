<?php

    namespace BS;

    use HTML\Element;

    /**
     * Accordion: stacked panels that open one at a time
     *
     * @see https://getbootstrap.com/docs/5.3/components/accordion/
     */
    class Accordion extends \HTML\ClassDiv
    {
        private string $ID;
        private bool $AlwaysOpen = false;
        private array $Panels = [];

        public function __construct(bool $Flush = false)
        {
            parent::__construct('accordion', $this->ID = uniqid('accordion_'));
            if ($Flush)
            {
                $this->AddClass('accordion-flush'); // No outer borders or rounded corners
            }
        }

        /**
         * Let several panels be open at the same time
         */
        public function AlwaysOpen(): static
        {
            $this->AlwaysOpen = true;
            foreach ($this->Panels as $Panel)
            {
                $Panel->RemoveAttribute('data-bs-parent');
            }
            return $this;
        }

        /**
         * @return \HTML\Div The panel's body, to add more content to
         */
        public function AddItem(string $Title, Element|string $Content, bool $Open = false): \HTML\Div
        {
            $PanelID = uniqid('panel_');
            parent::AddChild($Item = new \HTML\ClassDiv('accordion-item'));

            $Button = new \HTML\Button($Title);
            $Button->AddAttribute('type', 'button');
            $Button->AddClass('accordion-button');
            if (!$Open)
            {
                $Button->AddClass('collapsed');
            }
            $Button->AddAttribute('data-bs-toggle', 'collapse');
            $Button->AddAttribute('data-bs-target', '#' . $PanelID);
            $Button->AddAttribute('aria-expanded', $Open ? 'true' : 'false');
            $Button->AddAttribute('aria-controls', $PanelID);
            $Item->AddChild((new \HTML\Header(2, $Button))->AddClass('accordion-header'));

            $Item->AddChild($Panel = new \HTML\Div($PanelID));
            $Panel->AddClasses(['accordion-collapse', 'collapse']);
            if ($Open)
            {
                $Panel->AddClass('show');
            }
            if (!$this->AlwaysOpen)
            {
                $Panel->AddAttribute('data-bs-parent', '#' . $this->ID);
            }
            $this->Panels[] = $Panel;
            $Panel->AddChild($Body = new \HTML\ClassDiv('accordion-body'));
            $Body->AddChild($Content);
            return $Body;
        }

        public function AddChild(Element|string $Child): Element
        {
            throw new \InvalidArgumentException('Add panels to an accordion with AddItem()');
        }
    }
