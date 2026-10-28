<?php

    namespace HTML;

    /**
     * <input> Defines an input control
     *
     * @property string $Accept audio/*|video/*|image/*|(other)
     * @property string $Alt text
     * @property string $AutoComplete on|off
     * @property bool $AutoFocus
     * @property bool $Checked
     * @property string $DirName inputname.dir
     * @property bool $Disabled
     * @property string $Form id
     * @property string $FormAction url
     * @property string $FormEnctype application/x-www-form-urlencoded|multipart/form-data|text/plain
     * @property string $FormMethod get|post
     * @property bool $FormNoValidate
     * @property string $FormTarget _blank|_self|_parent|_top|(other)
     * @property string|int $Height pixels
     * @property string $List id
     * @property string $Max number or date
     * @property string|int $MaxLength number
     * @property string $Min number or date
     * @property string|int $MinLength number
     * @property bool $Multiple
     * @property string $Name text
     * @property string $Pattern regexp
     * @property string $Placeholder text
     * @property string $PopoverTarget id
     * @property string $PopoverTargetAction hide|show|toggle
     * @property bool $ReadOnly
     * @property bool $Required
     * @property string|int $Size number
     * @property string $Src url
     * @property string $Step any|(other)
     * @property string $Type button|checkbox|color|date|datetime-local|email|file|hidden|image|month|number|password|radio|range|reset|search|submit|tel|text|time|url|week
     * @property string $Value text
     * @property string|int $Width pixels
     * @see https://www.w3schools.com/tags/tag_input.asp
     */
    class Input extends Element
    {
        public function __construct(string $Name, InputType $Type = InputType::Text, ?string $Label = null)
        {
            parent::__construct('input');
            $ID = uniqid('input_');
            $this->AddAttribute("id", $ID);
            $this->AddAttribute("name", $Name);
            if ($Type != InputType::Text)
            {
                $this->AddAttribute("type", $Type->value);
            }

            if (!empty($Label))
            {
                $this->AddAttribute('placeholder', $Label);
                $this->AddSibling(new \HTML\Label($Label, $ID));
            }
        }

        public function Type($Type): void
        {
            $this->AddAttribute("type", $Type);
        }

        public function Value(mixed $Value): void
        {
            $this->AddAttribute('value', $Value);
        }

    }