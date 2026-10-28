<?php

    class Page
    {
        private static ?Framework $Page = null;

        private static function Init(HTML\Element|string|null $Content = null): bool
        {
            if (empty(self::$Page))
            {
                self::$Page = new Framework($Content);
                return true;
            }
            return false;
        }

        public static function AddContent(HTML\Element|string $Content): void
        {
            if (!self::Init($Content))
            {
                self::$Page->AddContent($Content);
            }
        }

        public static function Show(): void
        {
            echo self::$Page;
            self::$Page = null;
        }

        public static function AddScript(string $string)
        {
            self::Init();
            self::$Page->AddScript($string);
        }

        /**
         * The site's start page (its index.php, after Config::Init(true, true)): js/app.js, which loads the menu and the
         * home page into it, and a message while it does
         */
        public static function Landing(): void
        {
            self::AddScript(Config::FrameworkURL('js/app.js'));
            $Alert = new BS\Alert(Config::AppName() . ' Loading...', 'This should only take a second, if nothing happens, try to reload the page.'
                . ' Also please check your Internet connection and browser settings...');
            $Alert->Color(BS\Color::Light); // Show a slight background
            $Alert->AddChild(new BS\PlaceHolder([12, 6, 3, 8, 2]));
            self::AddContent($Alert);
        }
    }