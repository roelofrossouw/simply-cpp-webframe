<?php

    namespace BS;

    /**
     * A button that submits its form
     *
     * @see https://getbootstrap.com/docs/5.3/components/buttons/
     */
    class Submit extends BaseButton
    {
        public function __construct(?string $Caption = null, ?Icon $Icon = null)
        {
            parent::__construct($Caption, $Icon);
            $this->AddAttribute('type', 'submit');
            $this->Color(Color::Primary);
        }
    }
