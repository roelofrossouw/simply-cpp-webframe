<?php

    namespace BS;

    use HTML\Element;

    /**
     * A list of items in bordered rows, e.g. a summary of settings
     *
     * For rows that are links or buttons, use ActionList.
     *
     * @see https://getbootstrap.com/docs/5.3/components/list-group/
     */
    class ListGroup extends \HTML\Element
    {
        public function __construct(bool $Flush = false, bool $Numbered = false)
        {
            parent::__construct($Numbered ? 'ol' : 'ul');
            $this->AddClass('list-group');
            if ($Flush)
            {
                $this->AddClass('list-group-flush'); // No outer borders, e.g. inside a card
            }
            if ($Numbered)
            {
                $this->AddClass('list-group-numbered');
            }
        }

        /**
         * Items side by side (from $MinSize up) instead of stacked
         */
        public function Horizontal(ScreenSize $MinSize = ScreenSize::ExtraSmall): static
        {
            $this->AddClass('list-group-horizontal' . $MinSize->Infix(), 'list-group-horizontal');
            return $this;
        }

        public function AddItem(Element|string $Content, ?Color $Color = null, bool $Active = false): \HTML\ListItem
        {
            $this->AddChild($Item = new \HTML\ListItem($Content));
            $Item->AddClass('list-group-item');
            if ($Color !== null)
            {
                $Item->AddClass($Color->For('list-group-item'), 'list-group-item-color');
            }
            if ($Active)
            {
                $Item->AddClass('active');
                $Item->AddAttribute('aria-current', 'true');
            }
            return $Item;
        }
    }
