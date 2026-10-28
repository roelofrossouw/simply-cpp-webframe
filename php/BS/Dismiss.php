<?php

    namespace BS;

    enum Dismiss: string
    {
        case None = '';
        case Modal = 'modal';
        case OffCanvas = 'offcanvas';
        case Toast = 'toast';
    }