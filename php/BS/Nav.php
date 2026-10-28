<?php

    namespace BS;

    use HTML\Anchor;
    use HTML\Div;
    use HTML\Element;

    class Nav extends Element
    {
        private Div $Fluid;

        public function __construct(string $ID, bool $Small = false)
        {
            parent::__construct('nav', ID: $ID);
            if ($Small)
            {
                $this->AddClass('small-menu');
            }
            $this->Fluid = new Div();
            $this->Fluid->AddClass('container-fluid');
            $this->AddChild($this->Fluid);
        }

        public function Brand(Element|string $Brand)
        {
            $this->Fluid->AddChild($Brand = new Anchor($Brand, '/', IsLink: true));
            $Brand->AddClass('navbar-brand');
        }

        public function ItemBar(bool $RightAlign): Element
        {
            $CollapseID = uniqid('c');
            $this->Fluid->AddChild(new NavbarToggler($CollapseID));
            $Items = $this->Fluid->AddChild(new Div($CollapseID));

            $Items->AddClasses(['collapse', 'navbar-collapse']);
            if ($RightAlign)
            {
                $Items->AddClass('flex-grow-0');
            }
            return $Items;
        }
    }