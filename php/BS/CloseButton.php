<?php

    namespace BS;


    class CloseButton extends BaseButton
    {
        public function __construct(Dismiss $DismissType = Dismiss::None, ?string $Caption = null)
        {
            parent::__construct();
            if (empty($Caption))
            {
                // Bootstrap's close icon is .btn-close on its own, without the .btn styling
                $this->RemoveClass('btn');
                $this->AddClass('btn-close');
                $this->AddAttribute('aria-label', 'Close');
            }
            else
            {
                $this->AddChild($Caption);
                $this->Color(Color::Secondary);
            }
            if ($DismissType != Dismiss::None)
            {
                $this->AddAttribute('data-bs-dismiss', $DismissType->value);
            }
        }
    }