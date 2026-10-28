<?php

    namespace HTML;

    enum Event: string
    {
        case Click = 'click';
        case KeyPress = 'keypress';
        case Submit = 'submit';
        case Load = 'load';
        case DoubleClick = 'dblclick';
        case KeyDown = 'keydown';
        case Change = 'change';
        case Resize = 'resize';
        case MouseEnter = 'mouseenter';
        case KeyUp = 'keyup';
        case Focus = 'focus';
        case Scroll = 'scroll';
        case MouseLeave = 'mouseleave';
        case Blur = 'blur';
        case Unload = 'unload';

    }