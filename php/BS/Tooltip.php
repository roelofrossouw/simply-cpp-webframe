<?php

    namespace BS;

    /**
     * A short text shown when hovering over or focusing an element
     *
     *   Tooltip::Add($Button, 'Reload the list');
     *
     * app.js starts them after each page load.
     *
     * @see https://getbootstrap.com/docs/5.3/components/tooltips/
     */
    final class Tooltip
    {
        public static function Add(\HTML\Element $Element, string $Text, Placement $Placement = Placement::Top): \HTML\Element
        {
            self::CheckToggle($Element, 'tooltip');
            $Element->AddAttribute('data-bs-toggle', 'tooltip');
            $Element->AddAttribute('data-bs-title', $Text);
            $Element->AddAttribute('data-bs-placement', $Placement->value);
            return $Element;
        }

        /**
         * Bootstrap reads one data-bs-toggle per element; wrap a dropdown or collapse button in a span instead
         */
        public static function CheckToggle(\HTML\Element $Element, string $Toggle): void
        {
            if ($Element->HasAttribute('data-bs-toggle') && $Element->GetAttribute('data-bs-toggle') !== $Toggle)
            {
                throw new \InvalidArgumentException("This element already toggles a {$Element->GetAttribute('data-bs-toggle')}, wrap it in a span for the $Toggle");
            }
        }
    }
