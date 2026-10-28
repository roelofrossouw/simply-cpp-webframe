<?php

    namespace BS;

    /**
     * A list group whose rows are links or buttons
     *
     * @see https://getbootstrap.com/docs/5.3/components/list-group/#links-and-buttons
     */
    class ActionList extends \HTML\ClassDiv
    {
        public function __construct(bool $Flush = false)
        {
            parent::__construct('list-group');
            if ($Flush)
            {
                $this->AddClass('list-group-flush');
            }
        }

        public function AddLink(string $Caption, string $URL, ?Icon $Icon = null, ?Color $Color = null): \HTML\Anchor
        {
            $this->AddChild($Link = new \HTML\Anchor($Icon === null ? $Caption : $Icon->Element()->AddSibling($Caption), $URL));
            return $this->Style($Link, $Color);
        }

        /**
         * A row that runs a script, e.g. ->AddButton('Refresh')->OnClick('...')
         */
        public function AddButton(string $Caption, ?Icon $Icon = null, ?Color $Color = null): \HTML\Button
        {
            $this->AddChild($Button = new \HTML\Button($Icon === null ? $Caption : $Icon->Element()->AddSibling($Caption)));
            $Button->AddAttribute('type', 'button');
            return $this->Style($Button, $Color);
        }

        private function Style(\HTML\Element $Row, ?Color $Color): \HTML\Element
        {
            $Row->AddClasses(['list-group-item', 'list-group-item-action']);
            if ($Color !== null)
            {
                $Row->AddClass($Color->For('list-group-item'), 'list-group-item-color');
            }
            return $Row;
        }
    }
