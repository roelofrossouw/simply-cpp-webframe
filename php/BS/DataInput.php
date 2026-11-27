<?php

    namespace BS;

    class DataInput extends \HTML\Input
    {
        public function __construct(?string $Value = null, \HTML\InputType $Type = \HTML\InputType::Text)
        {
            parent::__construct('', $Type);
            $this->AddClass('form-control');
            $this->AddClass('form-control-sm');
            // These save on change, so neither the browser nor a password manager may fill them in (2026-11-27: an
            // admin's email address was filled into a menu name and saved at once)
            $this->AddAttribute('autocomplete', 'off');
            $this->AddAttribute('data-1p-ignore', 'true');
            $this->AddAttribute('data-lpignore', 'true');
            $this->AddAttribute('data-bwignore', 'true');
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