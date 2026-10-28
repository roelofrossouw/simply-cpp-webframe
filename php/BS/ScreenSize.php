<?php

    namespace BS;

    /**
     * Bootstrap Screen Sizes
     *
     * @see https://getbootstrap.com/docs/5.3/layout/breakpoints/
     */
    enum ScreenSize
    {
        function Infix(): string
        {
            return match ($this)
            {
                self::ExtraSmall => '',
                self::Small => '-sm',
                self::Medium => '-md',
                self::Large => '-lg',
                self::ExtraLarge => '-xl',
                self::ExtraExtraLarge => '-xxl',
            };
        }

        public function ModalClasses(): array
        {
            return match ($this)
            {
                self::ExtraSmall => ['modal-sm'],  // Bootstrap's smallest dialog, also on phones
                self::Small => ['modal-sm', 'modal-fullscreen-sm-down'],
                self::Medium => ['modal-fullscreen-sm-down'],
                self::Large => ['modal-lg', 'modal-fullscreen-md-down'],
                self::ExtraLarge, self::ExtraExtraLarge => ['modal-xl', 'modal-fullscreen-lg-down'],
            };
        }

        public function Width(): int
        {
            return match ($this)
            {
                self::ExtraSmall => 0,
                self::Small => 576,
                self::Medium => 768,
                self::Large => 992,
                self::ExtraLarge => 1200,
                self::ExtraExtraLarge => 1400,
            };
        }

        case ExtraSmall;  // <576px
        case Small;  //	≥576px
        case Medium;  // ≥768px
        case Large;  //	≥992px
        case ExtraLarge;  // ≥1200px
        case ExtraExtraLarge; // ≥1400px
    }