<?php

    namespace BS;

    use HTML\ClassDiv;
    use HTML\Event;

    /**
     * Floating captioned inputs
     */
    class Input extends ClassDiv
    {
        use FieldHelp;

        private \HTML\Input $InputElement;
        private \HTML\Div $Floater;
        private $Name;

        public function __construct(string $Name, string $Label, \HTML\InputType $Type = \HTML\InputType::Text, ?string $Prefix = null, ?string $Suffix = null)
        {
            $this->Name = $Name;
            parent::__construct('input');
            // No has-validation: Bootstrap then expects the messages as the group's last child, but
            // Feedback() puts them inside .form-floating, so the input kept its rounded corners
            // against a prefix or suffix. Without it, Bootstrap squares them as in any input group.
            $this->AddChild($Group = new ClassDiv('input-group'));

            if (!empty($Prefix))
            {
                $Tag = $Group->AddChild(new \HTML\Span($Prefix));
                $Tag->AddClass('input-group-text');
            }

            $this->Floater = $Floater = $Group->AddChild(new \HTML\Div());
            $Floater->AddClass('form-floating');
            $Floater->AddChild($this->InputElement = new \HTML\Input($Name, $Type, $Label));
            $this->InputElement->AddClasses(['form-control']);

            if (!empty($Suffix))
            {
                $Tag = $Group->AddChild(new \HTML\Span($Suffix));
                $Tag->AddClass('input-group-text');
            }
        }

        public function InputElement(): \HTML\Input
        {
            return $this->InputElement;
        }

        protected function Field(): \HTML\Element
        {
            return $this->InputElement;
        }

        protected function MessageParent(): \HTML\Element
        {
            return $this->Floater;
        }


        public function ShowPostedValue(): void
        {
            $this->Value(\Post::Get($this->Name));
        }

        public function Value(mixed $Value): void
        {
            $this->InputElement->Value($Value);
        }

        public function PostOnEnter(array $Query = []): void
        {
            $jHref = json_encode(\Server::Page($Query));
            $Script = <<<JS
            if (e.which == 13) { // Enter pressed
                loadPage.call(this, $jHref);
            }
            e.preventDefault();
JS;
            $this->Trigger(Event::KeyUp, $Script);
        }


    }