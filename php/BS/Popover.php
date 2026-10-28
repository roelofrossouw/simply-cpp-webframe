<?php

    namespace BS;

    /**
     * A small box with a title and text, shown when an element is clicked
     *
     *   Popover::Add($Button, 'Status', 'The container is stopped since Monday.');
     *
     * app.js starts them after each page load.
     *
     * @see https://getbootstrap.com/docs/5.3/components/popovers/
     */
    final class Popover
    {
        /**
         * @param bool $CloseOnNextClick Close when the user clicks anywhere else (the element needs to be focusable)
         * @param bool $Html             $Content is HTML, e.g. lines joined with <br>. Escape what goes into it
         *                               (htmlspecialchars); Bootstrap also strips tags outside its allow list.
         */
        public static function Add(\HTML\Element $Element, string $Title, string $Content, Placement $Placement = Placement::Top,
                                   bool $CloseOnNextClick = true, bool $Html = false): \HTML\Element
        {
            Tooltip::CheckToggle($Element, 'popover');
            $Element->AddAttribute('data-bs-toggle', 'popover');
            $Element->AddAttribute('data-bs-title', $Title);
            $Element->AddAttribute('data-bs-content', $Content);
            $Element->AddAttribute('data-bs-placement', $Placement->value);
            if ($Html)
            {
                $Element->AddAttribute('data-bs-html', 'true');
            }
            if ($CloseOnNextClick)
            {
                $Element->AddAttribute('data-bs-trigger', 'focus');
                if (!$Element->HasAttribute('tabindex'))
                {
                    $Element->TabIndex(0);
                }
            }
            return $Element;
        }
    }
