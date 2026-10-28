<?php

    namespace BS;

    /**
     * Switch
     *
     * Give it a Label(), shown next to it or (in a table, say) only for screen readers.
     *
     * @see https://getbootstrap.com/docs/5.3/forms/checks-radios/#switches
     */
    class SwitchInput extends \HTML\ClassDiv
    {
        private \HTML\Input $Input;

        public function __construct(bool $Checked = false, ?string $Label = null)
        {
            parent::__construct('form-check');
            $this->AddClass('form-switch');
            $this->AddChild($this->Input = new \HTML\Input('', \HTML\InputType::Checkbox));
            $this->Input->AddClass('form-check-input');
            $this->Input->AddAttribute('role', 'switch');
            $this->Input->Data('checkbox', true);
            if ($Checked)
            {
                $this->Input->AddAttribute('checked');
            }
            if ($Label !== null)
            {
                $this->Label($Label);
            }
        }

        /**
         * @param bool $Visible False to only label it for screen readers, e.g. when a table heading already says it
         */
        public function Label(string $Label, bool $Visible = true): static
        {
            if ($Visible)
            {
                $this->AddChild($Element = new \HTML\Label($Label, $this->Input->GetAttribute('id')));
                $Element->AddClass('form-check-label');
            }
            else
            {
                $this->Input->AddAttribute('aria-label', $Label);
            }
            return $this;
        }

        public function Data(string $Key, mixed $Value): static
        {
            $this->Input->Data($Key, $Value);
            return $this;
        }

        public function Checked(bool $IsChecked = true): void
        {
            if ($IsChecked)
            {
                $this->Input->AddAttribute('checked');
            }
        }

        public function Disabled(): static
        {
            $this->Input->AddAttribute('disabled');
            return $this;
        }

        public function OnChange(string $Script): static
        {
            $this->Input->OnChange($Script);
            return $this;
        }
    }