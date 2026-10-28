<?php

    namespace BS;


    use HTML\Event;

    class Button extends BaseButton
    {
        public function __construct(?string $Caption = null, ?Icon $Icon = null, ?string $Href = null)
        {
            parent::__construct($Caption, $Icon);
            if (!empty($Href))
            {
                // In the app pages load with a script, so it stays a <button>. Elsewhere it's a real link,
                // which Bootstrap wants marked with role="button".
                if (!\Config::AppPage())
                {
                    $this->RemoveAttribute('type');
                    $this->SetTag('a');
                    $this->AddAttribute('role', 'button');
                }
                $this->Action($Href);
            }
            $this->Color(Color::Primary);
        }

        /**
         * Link to another site or page outside the app (e.g. in an email), which makes it an <a>
         */
        public function Link(string $URL): static
        {
            if ($this->Tag() == 'button')
            {
                $this->RemoveAttribute('type');
                $this->SetTag('a');
                $this->AddAttribute('role', 'button');
            }
            return parent::Link($URL);
        }

        public function SkipForm(): void
        {
            $this->AddAttribute('data-skip-form', 'true');
        }

        /**
         * Open the button's page in the popup (The page should return a ModalDialog)
         */
        public function Modal(): static
        {
            return $this->AddClass('modal-link');
        }

        /**
         * Open the button's page in the side panel (The page should return an OffCanvas)
         */
        public function OffCanvas(): static
        {
            return $this->AddClass('oc-link');
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
