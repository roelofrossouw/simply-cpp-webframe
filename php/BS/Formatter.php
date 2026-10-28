<?php

    namespace BS;

    /**
     * Applies Bootstrap utility classes to an element.
     *
     * Each class is added with a family (e.g. 'p-md' or 'col-lg'), so setting the same utility again for the
     * same side and screen size replaces the earlier value. Methods return $this to allow chaining.
     */
    class Formatter
    {
        public FontFormatter $Font;
        private \HTML\Element $Element;

        public function __construct(\HTML\Element $Element)
        {
            $this->Element = $Element;
            $this->Font = new FontFormatter($this->Element);
        }

        public function Color(Color $Color): static
        {
            $this->Element->AddClass($Color->For('text'), 'text-color');
            return $this;
        }

        public function Background(Color $Color): static
        {
            $this->Element->AddClass($Color->For('bg'), 'bg');
            return $this;
        }

        public function Center(
                Direction $Direction = Direction::All,
                ScreenSize $MinSize = ScreenSize::ExtraSmall): static
        {
            // In CSS a margin of auto centers the element
            $Family = 'm' . $Direction->Infix() . $MinSize->Infix();
            $this->Element->AddClass($Family . '-auto', $Family);
            return $this;
        }

        public function Width(int|Percent $Width): static
        {
            match (true)
            {
                $Width instanceof Percent => $this->Element->AddClass('w' . '-' . $Width->value, 'w'),
                is_int($Width) => $this->Element->AddStyle('width', $Width . 'px'),
            };
            return $this;
        }

        public function Height(int|Percent $Height): static
        {
            match (true)
            {
                $Height instanceof Percent => $this->Element->AddClass('h' . '-' . $Height->value, 'h'),
                is_int($Height) => $this->Element->AddStyle('height', $Height . 'px'),
            };
            return $this;
        }

        public function MaxWidth(int|Percent $Width): static
        {
            match (true)
            {
                $Width instanceof Percent => $this->Element->AddStyle('max-width', $Width->Style()),
                is_int($Width) => $this->Element->AddStyle('max-width', $Width . 'px'),
            };
            return $this;
        }

        public function MaxHeight(int|Percent $Width): static
        {
            match (true)
            {
                $Width instanceof Percent => $this->Element->AddStyle('max-height', $Width->Style()),
                is_int($Width) => $this->Element->AddStyle('max-height', $Width . 'px'),
            };
            return $this;
        }

        private function AddStyles(string $Style, array $Modifiers, string $Value): void
        {
            foreach ($Modifiers as $Modifier)
            {
                $this->Element->AddStyle($Style . $Modifier, $Value);
            }
        }

        public function HeadingLevel(int $Level): static
        {
            $this->Element->AddClass('h' . $Level, 'heading');
            return $this;
        }

        public function Float(Side $Side): static
        {
            $this->Element->AddClass('float' . $Side->FullInfix(), 'float');
            return $this;
        }

        public function Columns(int $Columns, ScreenSize $MinSize = ScreenSize::ExtraSmall): static
        {
            $Family = 'col' . $MinSize->Infix();
            $this->Element->AddClass($Family . '-' . $Columns, $Family);
            return $this;
        }

        /**
         * @param PadSize $Size None or Pad1 (Bootstrap only has flex-grow-0 and flex-grow-1)
         */
        public function FlexGrow(PadSize $Size = PadSize::Pad1): static
        {
            self::ZeroOrOne('flex-grow', $Size);
            $this->Element->AddClass('flex-grow-' . $Size->value, 'flex-grow');
            return $this;
        }

        public function NoShrink(): static
        {
            return $this->FlexShrink(PadSize::None);
        }

        /**
         * @param PadSize $Size None or Pad1 (Bootstrap only has flex-shrink-0 and flex-shrink-1)
         */
        public function FlexShrink(PadSize $Size = PadSize::Pad1): static
        {
            self::ZeroOrOne('flex-shrink', $Size);
            $this->Element->AddClass('flex-shrink-' . $Size->value, 'flex-shrink');
            return $this;
        }

        /**
         * @param Side $Side All, Horizontal or Vertical (Bootstrap only has g-*, gx-* and gy-*)
         */
        public function Gutter(Side $Side, int|PadSize $Size): static
        {
            if (!in_array($Side, [Side::All, Side::Horizontal, Side::Vertical], true) || $Size === PadSize::Automatic)
            {
                throw new \InvalidArgumentException('Gutters can only be set for all sides, horizontally or vertically, to a size of 0-5');
            }
            $Family = 'g' . $Side->Infix();
            match (true)
            {
                $Size instanceof PadSize => $this->Element->AddClass($Family . '-' . $Size->value, $Family),
                is_int($Size) => null, // No gutter for px size
            };
            return $this;
        }

        public function FullHeight(int|PadSize $Padding = PadSize::Pad2): static
        {
            $this->Height(Percent::P100);
            $this->Display(Display::Flex);
            $this->Element->AddClass('flex-column', 'flex-direction');
            $this->Overflow();
            return $this->Padding(Side::All, $Padding);
        }

        public function Overflow(Overflow $Overflow = Overflow::Hidden, Direction $Direction = Direction::All): static
        {
            // overflow-hidden, overflow-x-hidden, overflow-y-hidden
            $Family = 'overflow' . ($Direction == Direction::All ? '' : '-' . $Direction->Infix());
            $this->Element->AddClass($Family . '-' . $Overflow->value, $Family);
            return $this;
        }

        public function Margin(Side $Side, int|PadSize $Size, ScreenSize $MinSize = ScreenSize::ExtraSmall): static
        {
            $Family = 'm' . $Side->Infix() . $MinSize->Infix();
            match (true)
            {
                $Size instanceof PadSize => $this->Element->AddClass($Family . '-' . $Size->value, $Family),
                is_int($Size) => $this->AddStyles('margin', $Side->Modifiers(), $Size . 'px'),
            };
            return $this;
        }

        public function Padding(Side $Side, int|PadSize $Size, ScreenSize $MinSize = ScreenSize::ExtraSmall): static
        {
            if ($Size === PadSize::Automatic)
            {
                throw new \InvalidArgumentException('Bootstrap has no automatic padding, only automatic margins');
            }
            $Family = 'p' . $Side->Infix() . $MinSize->Infix();
            match (true)
            {
                $Size instanceof PadSize => $this->Element->AddClass($Family . '-' . $Size->value, $Family),
                is_int($Size) => $this->AddStyles('padding', $Side->Modifiers(), $Size . 'px'),
            };
            return $this;
        }

        private static function ZeroOrOne(string $Utility, PadSize $Size): void
        {
            if (!in_array($Size, [PadSize::None, PadSize::Pad1], true))
            {
                throw new \InvalidArgumentException("Bootstrap only has $Utility-0 and $Utility-1");
            }
        }

        public function HideUntil(ScreenSize $Size): static
        {
            $this->Display(Display::None);
            return $this->Display(Display::Block, $Size);
        }

        public function Display(Display $Display, ScreenSize $MinSize = ScreenSize::ExtraSmall): static
        {
            $Family = 'd' . $MinSize->Infix();
            $this->Element->AddClass($Family . '-' . $Display->value, $Family);
            return $this;
        }

        public function HideFrom(ScreenSize $Size): static
        {
            return $this->Display(Display::None, $Size);
        }

        public function Colspan(int $Columns): static
        {
            $this->Element->AddAttribute('colspan', $Columns);
            return $this;
        }

        // Borders, corners and shadows ------------------------------------------------------------------------

        /**
         * @param Side $Side All, Top, Right, Bottom or Left
         * @see https://getbootstrap.com/docs/5.3/utilities/borders/
         */
        public function Border(Side $Side = Side::All, ?Color $Color = null, ?int $Width = null): static
        {
            $this->Element->AddClass(self::BorderClass($Side), 'border' . $Side->Infix());
            if ($Color !== null)
            {
                $this->Element->AddClass($Color->For('border'), 'border-color');
            }
            if ($Width !== null)
            {
                self::OneOf('border width', $Width, [1, 2, 3, 4, 5]);
                $this->Element->AddClass('border-' . $Width, 'border-width');
            }
            return $this;
        }

        /**
         * Remove a border, e.g. the bottom border of a card header
         */
        public function NoBorder(Side $Side = Side::All): static
        {
            $this->Element->AddClass(self::BorderClass($Side) . '-0', 'border' . $Side->Infix());
            return $this;
        }

        private static function BorderClass(Side $Side): string
        {
            return match ($Side)
            {
                Side::All => 'border',
                Side::Top => 'border-top',
                Side::Right => 'border-end',
                Side::Bottom => 'border-bottom',
                Side::Left => 'border-start',
                default => throw new \InvalidArgumentException('Borders can be set for all sides or one side'),
            };
        }

        public function Rounded(Rounded $Size = Rounded::Regular): static
        {
            $this->Element->AddClass('rounded' . ($Size->value === '' ? '' : '-' . $Size->value), 'rounded');
            return $this;
        }

        public function Shadow(Shadow $Shadow = Shadow::Regular): static
        {
            $this->Element->AddClass('shadow' . ($Shadow->value === '' ? '' : '-' . $Shadow->value), 'shadow');
            return $this;
        }

        /**
         * @param int $Percent 0, 25, 50, 75 or 100
         */
        public function Opacity(int $Percent): static
        {
            self::OneOf('opacity', $Percent, [0, 25, 50, 75, 100]);
            $this->Element->AddClass('opacity-' . $Percent, 'opacity');
            return $this;
        }

        // Position ---------------------------------------------------------------------------------------------

        /**
         * @see https://getbootstrap.com/docs/5.3/utilities/position/
         */
        public function Position(Position $Position): static
        {
            $this->Element->AddClass('position-' . $Position->value, 'position');
            return $this;
        }

        /**
         * Place a positioned element, e.g. Place(Side::Top, 0)->Place(Side::Right, 0) for the top right corner
         *
         * @param int $Percent 0, 50 or 100
         */
        public function Place(Side $Side, int $Percent): static
        {
            self::OneOf('position offset', $Percent, [0, 50, 100]);
            $Edge = match ($Side)
            {
                Side::Top => 'top',
                Side::Bottom => 'bottom',
                Side::Left => 'start',
                Side::Right => 'end',
                default => throw new \InvalidArgumentException('Place an element from the top, bottom, left or right'),
            };
            $this->Element->AddClass("$Edge-$Percent", $Edge);
            return $this;
        }

        /**
         * Centre on its position, e.g. after Place(Side::Top, 50)->Place(Side::Left, 50)
         */
        public function TranslateMiddle(Direction $Direction = Direction::All): static
        {
            $this->Element->AddClass('translate-middle' . ($Direction == Direction::All ? '' : '-' . $Direction->Infix()), 'translate');
            return $this;
        }

        /**
         * @param int $Level -1, 0, 1, 2 or 3
         */
        public function ZIndex(int $Level): static
        {
            self::OneOf('z-index', $Level, [-1, 0, 1, 2, 3]);
            $this->Element->AddClass($Level < 0 ? 'z-n1' : 'z-' . $Level, 'z');
            return $this;
        }

        // Text ------------------------------------------------------------------------------------------------

        public function TextAlign(TextAlign $Align, ScreenSize $MinSize = ScreenSize::ExtraSmall): static
        {
            $Family = 'text-align' . $MinSize->Infix();
            $this->Element->AddClass('text' . $MinSize->Infix() . '-' . $Align->value, $Family);
            return $this;
        }

        public function TextWrap(bool $Wrap = true): static
        {
            $this->Element->AddClass($Wrap ? 'text-wrap' : 'text-nowrap', 'text-wrap');
            return $this;
        }

        /**
         * Break long words (e.g. URLs) so they don't overflow
         */
        public function TextBreak(): static
        {
            $this->Element->AddClass('text-break');
            return $this;
        }

        /**
         * Cut text off with an ellipsis instead of wrapping (the element must be block or inline-block)
         */
        public function Truncate(): static
        {
            $this->Element->AddClass('text-truncate');
            return $this;
        }

        public function TextTransform(TextTransform $Transform): static
        {
            $this->Element->AddClass('text-' . $Transform->value, 'text-transform');
            return $this;
        }

        public function TextDecoration(TextDecoration $Decoration): static
        {
            $this->Element->AddClass('text-decoration-' . $Decoration->value, 'text-decoration');
            return $this;
        }

        public function LineHeight(LineHeight $Height): static
        {
            $this->Element->AddClass('lh-' . $Height->value, 'lh');
            return $this;
        }

        public function Italic(bool $Italic = true): static
        {
            $this->Element->AddClass($Italic ? 'fst-italic' : 'fst-normal', 'fst');
            return $this;
        }

        public function Monospace(): static
        {
            $this->Element->AddClass('font-monospace');
            return $this;
        }

        /**
         * A paragraph that stands out, e.g. the first one of a page
         */
        public function Lead(): static
        {
            $this->Element->AddClass('lead');
            return $this;
        }

        /**
         * Bigger heading for a page or section title
         *
         * @param int $Level 1 (largest) to 6
         * @see https://getbootstrap.com/docs/5.3/content/typography/#display-headings
         */
        public function DisplayHeading(int $Level): static
        {
            self::OneOf('display heading level', $Level, [1, 2, 3, 4, 5, 6]);
            $this->Element->AddClass('display-' . $Level, 'display-heading');
            return $this;
        }

        /**
         * Light text on a theme coloured background, e.g. for a badge or a card
         *
         * @see https://getbootstrap.com/docs/5.3/helpers/color-background/
         */
        public function TextBackground(Color $Color): static
        {
            $this->Element->AddClass($Color->For('text-bg'), 'text-bg');
            return $this;
        }

        /**
         * @see https://getbootstrap.com/docs/5.3/helpers/colored-links/
         */
        public function LinkColor(Color $Color): static
        {
            $this->Element->AddClass($Color->For('link'), 'link-color');
            return $this;
        }

        // Flex ------------------------------------------------------------------------------------------------

        /**
         * @see https://getbootstrap.com/docs/5.3/utilities/flex/
         */
        public function FlexDirection(FlexDirection $Direction, ScreenSize $MinSize = ScreenSize::ExtraSmall): static
        {
            $this->Element->AddClass('flex' . $MinSize->Infix() . '-' . $Direction->value, 'flex-direction' . $MinSize->Infix());
            return $this;
        }

        public function JustifyContent(Justify $Justify, ScreenSize $MinSize = ScreenSize::ExtraSmall): static
        {
            $Family = 'justify-content' . $MinSize->Infix();
            $this->Element->AddClass($Family . '-' . $Justify->value, $Family);
            return $this;
        }

        public function AlignItems(Align $Align, ScreenSize $MinSize = ScreenSize::ExtraSmall): static
        {
            $Family = 'align-items' . $MinSize->Infix();
            $this->Element->AddClass($Family . '-' . $Align->value, $Family);
            return $this;
        }

        /**
         * Align one flex item differently from the others
         */
        public function AlignSelf(Align $Align, ScreenSize $MinSize = ScreenSize::ExtraSmall): static
        {
            $Family = 'align-self' . $MinSize->Infix();
            $this->Element->AddClass($Family . '-' . $Align->value, $Family);
            return $this;
        }

        public function FlexWrap(FlexWrap $Wrap, ScreenSize $MinSize = ScreenSize::ExtraSmall): static
        {
            $this->Element->AddClass('flex' . $MinSize->Infix() . '-' . $Wrap->value, 'flex-wrap' . $MinSize->Infix());
            return $this;
        }

        /**
         * @param int $Order 0 to 5, or -1 for first and 6 for last
         */
        public function Order(int $Order, ScreenSize $MinSize = ScreenSize::ExtraSmall): static
        {
            self::OneOf('order', $Order, [-1, 0, 1, 2, 3, 4, 5, 6]);
            $Family = 'order' . $MinSize->Infix();
            $this->Element->AddClass($Family . '-' . match ($Order) { -1 => 'first', 6 => 'last', default => $Order }, $Family);
            return $this;
        }

        /**
         * Space between flex or grid items
         *
         * @param Direction $Direction All, Vertical (between rows) or Horizontal (between columns)
         */
        public function Gap(PadSize $Size, Direction $Direction = Direction::All, ScreenSize $MinSize = ScreenSize::ExtraSmall): static
        {
            if ($Size === PadSize::Automatic)
            {
                throw new \InvalidArgumentException('Gaps are 0-5, not automatic');
            }
            $Family = match ($Direction)
            {
                Direction::All => 'gap',
                Direction::Vertical => 'row-gap',
                Direction::Horizontal => 'column-gap',
            } . $MinSize->Infix();
            $this->Element->AddClass($Family . '-' . $Size->value, $Family);
            return $this;
        }

        public function VerticalAlign(VerticalAlign $Align): static
        {
            $this->Element->AddClass('align-' . $Align->value, 'vertical-align');
            return $this;
        }

        // Images, lists and sizing ----------------------------------------------------------------------------

        public function ObjectFit(ObjectFit $Fit, ScreenSize $MinSize = ScreenSize::ExtraSmall): static
        {
            $Family = 'object-fit' . $MinSize->Infix();
            $this->Element->AddClass($Family . '-' . $Fit->value, $Family);
            return $this;
        }

        /**
         * Scale an image with its container, never wider than it
         *
         * @see https://getbootstrap.com/docs/5.3/content/images/
         */
        public function FluidImage(): static
        {
            $this->Element->AddClass('img-fluid');
            return $this;
        }

        public function Thumbnail(): static
        {
            $this->Element->AddClass('img-thumbnail');
            return $this;
        }

        /**
         * A list without bullets or indent
         */
        public function ListUnstyled(): static
        {
            $this->Element->AddClass('list-unstyled', 'list-style');
            return $this;
        }

        /**
         * A list on one line; give its items ListInlineItem()
         */
        public function ListInline(): static
        {
            $this->Element->AddClass('list-inline', 'list-style');
            return $this;
        }

        public function ListInlineItem(): static
        {
            $this->Element->AddClass('list-inline-item');
            return $this;
        }

        /**
         * The full width or height of the window
         */
        public function ViewportWidth(bool $Minimum = false): static
        {
            $this->Element->AddClass(($Minimum ? 'min-' : '') . 'vw-100', 'vw');
            return $this;
        }

        public function ViewportHeight(bool $Minimum = false): static
        {
            $this->Element->AddClass(($Minimum ? 'min-' : '') . 'vh-100', 'vh');
            return $this;
        }

        // Visibility and interaction --------------------------------------------------------------------------

        /**
         * Hide without removing it from the layout (unlike Display(Display::None))
         */
        public function Visible(bool $Visible = true): static
        {
            $this->Element->AddClass($Visible ? 'visible' : 'invisible', 'visibility');
            return $this;
        }

        /**
         * Only for screen readers, or (focusable) shown when it gets keyboard focus, e.g. "Skip to content"
         *
         * @see https://getbootstrap.com/docs/5.3/helpers/visually-hidden/
         */
        public function VisuallyHidden(bool $Focusable = false): static
        {
            $this->Element->AddClass($Focusable ? 'visually-hidden-focusable' : 'visually-hidden', 'visually-hidden');
            return $this;
        }

        public function UserSelect(UserSelect $Select): static
        {
            $this->Element->AddClass('user-select-' . $Select->value, 'user-select');
            return $this;
        }

        /**
         * Whether the element reacts to the mouse (pe-auto / pe-none)
         */
        public function PointerEvents(bool $Enabled): static
        {
            $this->Element->AddClass($Enabled ? 'pe-auto' : 'pe-none', 'pointer-events');
            return $this;
        }

        /**
         * Make a link cover its nearest positioned parent (e.g. a card), so the whole card is clickable
         *
         * @see https://getbootstrap.com/docs/5.3/helpers/stretched-link/
         */
        public function StretchedLink(): static
        {
            $this->Element->AddClass('stretched-link');
            return $this;
        }

        /**
         * @see https://getbootstrap.com/docs/5.3/helpers/focus-ring/
         */
        public function FocusRing(?Color $Color = null): static
        {
            $this->Element->AddClass('focus-ring');
            if ($Color !== null)
            {
                $this->Element->AddClass($Color->For('focus-ring'), 'focus-ring-color');
            }
            return $this;
        }

        /**
         * Contain floated children
         */
        public function Clearfix(): static
        {
            $this->Element->AddClass('clearfix');
            return $this;
        }

        private static function OneOf(string $What, int $Value, array $Allowed): void
        {
            if (!in_array($Value, $Allowed, true))
            {
                throw new \InvalidArgumentException("Bootstrap has no $What $Value, use one of " . implode(', ', $Allowed));
            }
        }
    }
