<?php

    namespace BS;

    enum Percent: string
    {
        case P25 = '25';
        case P50 = '50';
        case P75 = '75';
        case P100 = '100';
        case Automatic = 'auto';

        public function Style()
        {
            return match ($this)
            {
                self::P25 => '25%',
                self::P50 => '50%',
                self::P75 => '75%',
                self::P100 => '100%',
                self::Automatic => 'auto',
            };
        }
    }