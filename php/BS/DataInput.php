<?php

    namespace BS;

    class DataInput extends \HTML\Input
    {
        public function __construct(?string $Value = null, \HTML\InputType $Type = \HTML\InputType::Text)
        {
            parent::__construct('', $Type);
            $this->AddClass('form-control');
            $this->AddClass('form-control-sm');
            if (!empty($Value))
            {
                $this->Value($Value);
            }

        }

        public function Size(int $Size)
        {
            $this->AddAttribute('size', $Size);
        }
    }