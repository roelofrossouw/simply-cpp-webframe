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
         * Open the button's page in a popup on top of the popup the button is in (the page should return a
         * ModalDialog), e.g. an Add or Edit from a list in a popup. The popup it came from is kept as it is (its form
         * as filled in, its tab); the new one gets a back arrow, and closing it (Close, the back arrow, Esc, or a save
         * whose notice leaves it empty) shows the one it came from again. Popups can go on top of each other as deep
         * as needed. The form in the popup it came from isn't posted.
         *
         * $RefreshOnReturn: when the new popup saved something (it showed a Toast::Notice), the popup it came from is
         * loaded again on the way back, so it shows the change. False keeps it exactly as it was.
         *
         * A button that isn't in a popup opens the popup as Modal() does.
         */
        public function Push(bool $RefreshOnReturn = true): static
        {
            $this->Modal();
            $this->AddClass('modal-push');
            $this->SkipForm();
            return $this->AddAttribute('data-refresh-parent', $RefreshOnReturn ? 'true' : 'false');
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
