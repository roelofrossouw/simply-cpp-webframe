<?php

    namespace BS;

    /**
     * Bootstrap colours
     *
     * Not every colour exists for every kind of class: buttons, alerts, table rows and coloured backgrounds with
     * text (text-bg-*) only come in the eight theme colours, and backgrounds have no -emphasis or -50 variants.
     * Use For() to get a class, it throws for a combination Bootstrap doesn't have.
     *
     * @see https://getbootstrap.com/docs/5.3/customize/color/
     * @see https://getbootstrap.com/docs/5.3/utilities/colors/
     * @see https://getbootstrap.com/docs/5.3/utilities/background/
     */
    enum Color: string
    {
        /**
         * The class for this colour with a prefix, e.g. Color::Danger->For('btn') = 'btn-danger'
         *
         * @param string $Prefix One of text, bg, btn, btn-outline, alert, table, text-bg, border, link, focus-ring,
         *                       list-group-item, progress-bar (the latter two take bg)
         * @throws \InvalidArgumentException when Bootstrap has no such class
         */
        public function For(string $Prefix): string
        {
            $Valid = match ($Prefix)
            {
                'text' => !$this->IsSubtle() && $this !== self::Transparent,
                'bg' => $this->IsTheme() || $this->IsSubtle()
                    || in_array($this, [self::Body, self::BodySecondary, self::BodyTertiary, self::Black, self::White, self::Transparent], true),
                'border' => $this->IsTheme() || $this->IsSubtle() || in_array($this, [self::Black, self::White], true),
                'link' => $this->IsTheme() || $this === self::BodyEmphasis,
                'btn', 'btn-outline', 'alert', 'table', 'text-bg', 'focus-ring', 'list-group-item' => $this->IsTheme(),
                default => throw new \InvalidArgumentException("Unknown colour class prefix '$Prefix'"),
            };
            if (!$Valid)
            {
                throw new \InvalidArgumentException("Bootstrap has no $Prefix-{$this->value} class");
            }
            return $Prefix . '-' . $this->value;
        }

        /**
         * The eight theme colours (primary, secondary, success, danger, warning, info, light, dark)
         */
        public function IsTheme(): bool
        {
            return in_array($this, [self::Primary, self::Secondary, self::Success, self::Danger, self::Warning, self::Info, self::Light, self::Dark], true);
        }

        /**
         * The lighter background and border tones of the theme colours, e.g. bg-primary-subtle
         */
        public function IsSubtle(): bool
        {
            return str_ends_with($this->value, '-subtle');
        }

        /**
         * Theme colours dark enough to need light text on top
         */
        public function IsDark(): bool
        {
            return in_array($this, [self::Primary, self::Secondary, self::Success, self::Danger, self::Dark, self::Black], true);
        }

        case Primary = 'primary';
        case PrimaryEmphasis = 'primary-emphasis';
        case Secondary = 'secondary';
        case SecondaryEmphasis = 'secondary-emphasis';
        case Success = 'success';
        case SuccessEmphasis = 'success-emphasis';
        case Danger = 'danger';
        case DangerEmphasis = 'danger-emphasis';
        case Warning = 'warning';
        case WarningEmphasis = 'warning-emphasis';
        case Info = 'info';
        case InfoEmphasis = 'info-emphasis';
        case Light = 'light';
        case LightEmphasis = 'light-emphasis';
        case Dark = 'dark';
        case DarkEmphasis = 'dark-emphasis';
        case Body = 'body';
        case BodyEmphasis = 'body-emphasis';
        case BodySecondary = 'body-secondary';
        case BodyTertiary = 'body-tertiary';
        case Black = 'black';
        case White = 'white';
        case Black50 = 'black-50';
        case White50 = 'white-50';
        case PrimarySubtle = 'primary-subtle';
        case SecondarySubtle = 'secondary-subtle';
        case SuccessSubtle = 'success-subtle';
        case DangerSubtle = 'danger-subtle';
        case WarningSubtle = 'warning-subtle';
        case InfoSubtle = 'info-subtle';
        case LightSubtle = 'light-subtle';
        case DarkSubtle = 'dark-subtle';
        case Transparent = 'transparent';

    }