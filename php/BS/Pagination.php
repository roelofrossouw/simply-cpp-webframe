<?php

    namespace BS;

    /**
     * Page numbers for a long list, with previous and next
     *
     *   new Pagination($Page, $Pages, '/app/orders.php?page={page}')
     *
     * Shows the first and last page and two either side of the current one, with … for the gaps.
     *
     * @see https://getbootstrap.com/docs/5.3/components/pagination/
     */
    class Pagination extends \HTML\Nav
    {
        private \HTML\UnorderedList $List;
        private \Closure $URL;

        /**
         * @param string|callable $URL Link for a page, with {page} for its number, or a function(int $Page): string
         */
        public function __construct(int $Current, int $Pages, string|callable $URL, string $Label = 'Pages', ?Size $Size = null)
        {
            parent::__construct();
            $this->AddAttribute('aria-label', $Label);
            parent::AddChild($this->List = new \HTML\UnorderedList());
            $this->List->AddClass('pagination');
            if ($Size !== null)
            {
                $this->List->AddClass('pagination-' . $Size->value);
            }
            $this->URL = is_callable($URL) ? \Closure::fromCallable($URL) : fn(int $Page) => str_replace('{page}', $Page, $URL);

            $Pages = max(1, $Pages);
            $Current = min(max(1, $Current), $Pages);
            $this->Item('Previous', $Current - 1, Disabled: $Current == 1, Label: 'Previous');
            $Shown = array_unique(array_filter([1, $Current - 2, $Current - 1, $Current, $Current + 1, $Current + 2, $Pages], fn($Page) => $Page >= 1 && $Page <= $Pages));
            sort($Shown);
            $Previous = 0;
            foreach ($Shown as $Page)
            {
                if ($Page > $Previous + 1)
                {
                    $this->Item('…', 0, Disabled: true);
                }
                $this->Item((string)$Page, $Page, Active: $Page == $Current);
                $Previous = $Page;
            }
            $this->Item('Next', $Current + 1, Disabled: $Current == $Pages, Label: 'Next');
        }

        private function Item(string $Caption, int $Page, bool $Disabled = false, bool $Active = false, ?string $Label = null): void
        {
            $this->List->AddChild($Item = new \HTML\ListItem());
            $Item->AddClass('page-item');
            if ($Disabled || $Active)
            {
                // Not a link: the current page, or nowhere to go
                $Item->AddClass($Active ? 'active' : 'disabled');
                $Item->AddChild($Link = new \HTML\Span($Caption));
                if ($Active)
                {
                    $Link->AddAttribute('aria-current', 'page');
                }
            }
            else
            {
                $Item->AddChild($Link = new \HTML\Anchor($Caption, ($this->URL)($Page)));
            }
            $Link->AddClass('page-link');
            if ($Label !== null)
            {
                $Link->AddAttribute('aria-label', $Label);
            }
        }
    }
