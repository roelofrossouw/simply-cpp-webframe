<?php

    namespace BS;

    /**
     * Bootstrap Side (To apply margin or padding)
     *
     * @see https://getbootstrap.com/docs/5.3/utilities/spacing/
     */
    enum Side
    {
        function Infix(): string
        {
            return match ($this)
            {
                self::All => '',
                /** Use Start (s) instead of Left (l) to handle RTL reading direction
                 *
                 * @see https://getbootstrap.com/docs/5.3/getting-started/rtl/
                 * */
                self::Left => 's',
                /** Use End (e) instead of Right (r) to handle RTL reading direction
                 *
                 * @see https://getbootstrap.com/docs/5.3/getting-started/rtl/
                 * */
                self::Right => 'e',
                self::Top => 't',
                self::Bottom => 'b',
                self::Vertical => 'y',
                self::Horizontal => 'x',
            };
        }

        public function FullInfix(): string
        {
            return match ($this)
            {
                self::Left => '-start',
                self::Right => '-end',
                default => throw new \Exception('Only Left and Right has full infixes'),
            };
        }

        public function Modifiers(): array
        {
            return match ($this)
            {
                self::All, self::Bottom, self::Left, self::Right, self::Top => [$this->Modifier()],
                self::Vertical => [self::Top->Modifier(), self::Bottom->Modifier()],
                self::Horizontal => [self::Left->Modifier(), self::Right->Modifier()],
            };
        }

        public function Modifier(): string
        {
            return match ($this)
            {
                self::All => '',
                self::Left => '-left',
                self::Right => '-right',
                self::Top => '-top',
                self::Bottom => '-bottom',
                self::Vertical => throw new \Exception('No CSS Modifier for vertical, split into top and bottom'),
                self::Horizontal => throw new \Exception('No CSS Modifier for horizontal, split into left and right'),
            };
        }

        case All;
        case Left;
        case Right;
        case Top;
        case Bottom;
        case Vertical;
        case Horizontal;
    }