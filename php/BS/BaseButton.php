<?php

    namespace BS;

    use HTML\Element;

    abstract class BaseButton extends Element
    {
        public function __construct(?string $Caption = null, ?Icon $Icon = null)
        {
            parent::__construct('button');
            $this->AddAttribute('type', 'button');
            $this->AddClass('btn');
            if (!empty($Icon))
            {
                $this->AddChild($Icon->Element());
            }
            if (!empty($Caption))
            {
                $this->AddChild(new \HTML\Text($Caption));
            }
        }

        public function Color(Color $Color): static
        {
            return $this->AddClass($Color->For('btn'), 'btn-color');
        }

        public function DismissType(Dismiss $DismissType)
        {
            $this->AddAttribute('data-bs-dismiss', $DismissType->value);
        }



    }