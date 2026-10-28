<?php

    namespace BS;

    /**
     * A multi-line text field with a floating label
     *
     * @see https://getbootstrap.com/docs/5.3/forms/floating-labels/#textareas
     */
    class TextArea extends \HTML\ClassDiv
    {
        use FieldHelp;

        private \HTML\TextArea $TextArea;

        /**
         * @param int $Height Height in pixels (floating labels need a fixed height rather than rows)
         */
        public function __construct(string $Name, string $Label, string $Value = '', int $Height = 100)
        {
            parent::__construct('form-floating');
            $this->AddChild($this->TextArea = new \HTML\TextArea($Value === '' ? null : $Value, uniqid('textarea_')));
            $this->TextArea->Name = $Name;
            $this->TextArea->Placeholder = $Label;
            $this->TextArea->AddClass('form-control');
            $this->TextArea->AddStyle('height', $Height . 'px');
            $this->AddChild(new \HTML\Label($Label, $this->TextArea->Id));
        }

        public function TextAreaElement(): \HTML\TextArea
        {
            return $this->TextArea;
        }

        protected function Field(): \HTML\Element
        {
            return $this->TextArea;
        }

        protected function MessageParent(): \HTML\Element
        {
            return $this;
        }
    }
